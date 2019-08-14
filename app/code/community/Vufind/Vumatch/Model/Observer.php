<?php

Class Vufind_Vumatch_Model_Observer extends Vufind_Vumatch_Model_Abstract
{
	protected function _construct()
	{
		parent::_construct();
	}
	

	//for logging once a product recommendation is viewed...
	public function eventPostDispatchProductView($observer)
	{
		if (!$this->isActive()) return;
		$currentProduct = $this->getCurrentProduct();
		$sku = $currentProduct->getSku();
		$imageUrl = Mage::getModel('catalog/product_media_config')->getMediaUrl($currentProduct->getImage());
		$customerId = $this->getCustomerId();

		$catalogName = ""; //combined
		$category = ""; //single
		$cats = $currentProduct->getCategoryIds();
		$catsCounter = 1;
		$catsLength = sizeof($cats);
		foreach ($cats as $category_id) {
			$_cat = Mage::getModel('catalog/category')->load($category_id) ;
			$category = $_cat->getName();
			if(strlen($category) > 0 && ($catsCounter == 1  || $catsCounter == $catsLength)) {
				$catalogName .= $category;
				if($catsCounter < $catsLength) $catalogName .="_";
			}
			$catsCounter += 1;
		} 
		$catalogName = preg_replace('/\%/','',$catalogName);
		$catalogName = preg_replace('/\@/','',$catalogName);
		$catalogName = preg_replace('/\&/','_and_',$catalogName);
		$catalogName = preg_replace('/\s[\s]+/','',$catalogName);
		$catalogName = preg_replace('/[\s\W]+/','',$catalogName);
		$catalogName = preg_replace('/^[\-]+/','',$catalogName);
		$catalogName = preg_replace('/[\-]+$/','',$catalogName);
		$catalogName = strtolower($catalogName);

		$api = Mage::getSingleton('vumatch/api');
		$result = $api->sendView($sku, $customerId, $catalogName, $imageUrl);

		return $this;
	}

	private function getPackageTheme($termDB)
	{
		$coreResource = Mage::getSingleton('core/resource');
		$read = $coreResource->getConnection('core_read');
		$config_data = $coreResource->getTableName ('core_config_data');
	
		$termDB = '"'.$termDB.'"';
		$sql = "SELECT value FROM $config_data WHERE path=$termDB";
		$resultDB = $read->fetchAll($sql);
			
		if (count($resultDB) == 0){
			$result = 'default';
		} else{
			$result = $resultDB[0]['value'];
			if ($result == '' || $result == null ){
				$result = 'default';
			}
		}
		return $result;
	}

	private function getDirectory($package, $theme, $type)
	{
		$absPath = Mage::getBaseDir() . '/app/design/frontend/';
	
		if ($package=='' || !file_exists($absPath.$package) ) {
			$package = 'default';
		}
		if ($theme=='' || !file_exists($absPath.$package.$theme)){
			$theme = 'default';
		}
	
		$dirFinalPT = $absPath.$package.'/'.$theme;
	
		if (!file_exists($dirFinalPT)) {
			// It is not possible to locate the right directory
			Mage::getConfig()->saveConfig('vumatch/setup/packagetheme','Not possible to locate the right package/theme in '.$dirFinalPT);
			return;
		}
	
		$dirFinalPTtype = $dirFinalPT.'/'.$type;
	
		if (!file_exists($dirFinalPTtype)) {
			mkdir($dirFinalPTtype, 0766, true);
		}
		
		if ($type=="template") {
			if (!file_exists($dirFinalPTtype.'/vufind')) {
				mkdir($dirFinalPTtype."/vufind", 0766, true);
			}
			if (!file_exists($dirFinalPTtype.'/vufind/vumatch')) {
				mkdir($dirFinalPTtype."/vufind/vumatch", 0766, true);
			}
		}		
		
		if (!file_exists($dirFinalPTtype)) {
			Mage::getConfig()->saveConfig('vumatch/setup/packagetheme', $type . ' folder is not available in ' . $dirFinalPT);
			return;
		}

		return $dirFinalPTtype;
	}

	private function getHandlerUpdate($handler) {
		$structural = 'footer';
		$position   = 'before';
		$block      = '-';

		$xml='<'.$handler.'>
			<reference name="'.$structural.'">
				<block type="vumatch/widget" name="vumatch.widget" '.$position.'="'.$block.'"/>
			</reference>
		</'.$handler.'>';
		
		return $xml;
	}

	public function eventPostDispatch($observer)
	{
		$cont = $observer->getEvent()->getControllerAction();

		if ($cont->getRequest()->getParam('section') !== 'vumatch') {
			return $this;
		}

		//first see if catalog sync is requested and then upload it
		if (Mage::getStoreConfigFlag('vumatch/account/sync_catalog')) {
			$products = Mage::getModel('catalog/product')->getCollection();
			$products->addAttributeToSelect('sku');
			$products->addAttributeToSelect('type_id');

			 $catalogCSV = "sku,category,name,price,url\n";
			 $counter = 0;
			foreach($products as $prod) {
				try{
					$product = Mage::getModel('catalog/product')->load($prod->getId());
					$visibility = $product->getVisibility();
					if ($visibility != 4) {
						continue;
					}
					$category = ""; //single category
					$catalogName = ""; //combined
					$cats = $product->getCategoryIds();
					$catsCounter = 1;
					$catsLength = sizeof($cats);
					foreach ($cats as $category_id) {
						$_cat = Mage::getModel('catalog/category')->load($category_id) ;
						$category = $_cat->getName();
						if(strlen($category) > 0 && ($catsCounter == 1  || $catsCounter == $catsLength)) {
							$catalogName .= $category;
							if($catsCounter < $catsLength) $catalogName .="_";
						}
						$catsCounter += 1;
					} 
					$catalogName = preg_replace('/\%/','',$catalogName);
					$catalogName = preg_replace('/\@/','',$catalogName);
					$catalogName = preg_replace('/\&/','_and_',$catalogName);
					$catalogName = preg_replace('/\s[\s]+/','',$catalogName);
					$catalogName = preg_replace('/[\s\W]+/','',$catalogName);
					$catalogName = preg_replace('/^[\-]+/','',$catalogName);
					$catalogName = preg_replace('/[\-]+$/','',$catalogName);
					$catalogName = strtolower($catalogName);


					$sku = $product->getSku();
					$name = $product->getName();
					$price = $product->getPrice();
					$imageUrl = Mage::getModel('catalog/product_media_config')->getMediaUrl($product->getImage());
					if(strlen($imageUrl) < 1) continue;
					if(strlen($catalogName) < 1) continue;
					$catalogCSV .= $sku.",".$catalogName.",".$name.",".$price.",".$imageUrl."\n";
					$counter += 1;
				} catch(Exception $e) {
					Mage::log($e->getMessage());
					continue;
				}
			}

			$csvPath = Mage::getBaseDir()."/app/code/community/Vufind/Vumatch/Helper/catalog.csv";
			$file  = fopen($csvPath, 'w');
			fwrite($file, $catalogCSV);
			fclose($file);
			chmod($csvPath,0755);

			$customer_id = $this->getCustomerId();
			$api_key = $this->getApiKey();
			$api_token = $this->getApiToken();

			$headers = array("Content-Type:multipart/form-data"); // cURL headers for file uploading
		    	$postfields = array("catalog" => "@$csvPath", "customer_id" => $customer_id, "api_key" => $api_key, "api_token" => $api_token);
		    	$ch = curl_init();
		    	$options = array(
		        CURLOPT_URL => "http://api7.vufind.com/api/magento/upload",
		        CURLOPT_HEADER => true,
		        CURLOPT_POST => 1,
		        CURLOPT_HTTPHEADER => $headers,
		        CURLOPT_POSTFIELDS => $postfields,
		        CURLOPT_RETURNTRANSFER => true,
		        CURLOPT_USERAGENT => "Magento VuMatch Visual Recommendations"
		    ); // cURL options

		    curl_setopt_array($ch, $options);
		    $res = curl_exec($ch);

			$config = Mage::getModel('core/config');
			$config->saveConfig('vumatch/account/sync_catalog', '0');
			Mage::app()->getConfig()->reinit();
		}
	}

	public function eventPreDispatch($observer)
	{
		$cont = $observer->getEvent()->getControllerAction();

		if ($cont->getRequest()->getParam('section') !== 'vumatch') {
			return $this;
		}

		$htmlCode = $this->getHtmlCode();
        if($htmlCode == '') {
            return $this;
        }


		$package  = $this->getPackageTheme('design/package/name');
		$theme    = $this->getPackageTheme('design/theme/default');

		// layout
		$dir      = $this->getDirectory($package, $theme, 'layout');
		$filename = $dir . '/vufind_vumatch.xml';
		$file     = fopen($filename, 'w');

		$txt  = '<?xml version="1.0"?>';
		$txt .= '<layout version="1.0.0">';
		$txt .= $this->getHandlerUpdate('catalog_product_view');
		$txt .= '</layout>';

		fwrite($file, $txt);
		fclose($file);
		chmod($filename,0755);

		// template
		$dir      = $this->getDirectory($package, $theme, 'template');
		$filename = $dir . '/vufind/vumatch/widget.phtml';
		$file     = fopen($filename, 'w');

		$txt  = '<div class="vufind-vumatch">';
		$txt .= '<h2>You May Also Like</h2>';
		$txt .= '<?php echo $htmlCode ?>';
		$txt .= '<p align="right">Powered by <a href="http://deepvu.co">DeepVu</a></p><br/><br/></div>';

		fwrite($file, $txt);
		fclose($file);
		chmod($filename,0755);

		return $this;
	}
}

?>

