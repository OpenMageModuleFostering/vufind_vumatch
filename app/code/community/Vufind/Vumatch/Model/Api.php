<?php

Class Vufind_Vumatch_Model_Api extends Vufind_Vumatch_Model_Abstract
{		
	public function sendView($sku, $customerId, $category, $imageUrl)
	{
		$url = "http://api7.vufind.com/api/magento/log?uid=$customerId&sku=$sku&url=$imageUrl&c=$category";
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    	curl_setopt($curl, CURLOPT_USERAGENT, "Magento VuMatch Visual Recommendations");
    	$res = curl_exec($curl);
		return $res;
	}

	public function getSimilarProducts($productId)
	{
		$product = Mage::getModel('catalog/product')->load($productId);
		$imageUrl = Mage::getModel('catalog/product_media_config')->getMediaUrl($product->getImage());
		$catalogName = "";
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

		$url = "http://api9.vufind.com/vumatch/vumatch.php?app_key=".$this->getApiKey()."&token=".$this->getApiToken()."&customer_id=".$this->getCustomerId()."&cat=".$catalogName."&url=".$imageUrl;
		return $this->makeCall($url, 'GET');
	}
	
	private function makeCall($url)
	{
		$ret = array('success' => true);
		try {
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
			
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    		curl_setopt($curl, CURLOPT_USERAGENT, "Magento VuMatch Visual Recommendations");
    	
    		$res = curl_exec($curl);
    		$res = json_decode($res, true);
    		
    		if($res["Status"] == false) {
    			$ret['success'] = false;
				$ret['message'] = $res["Error"]["Message"];
				return $ret;
    		}

    		$ret['message'] = $res['Data']['VufindRecommends'];
    		
    		curl_close($curl);
		} catch(Exception $e) {
			$ret['success'] = false;
			$ret['message'] = $e->getMessage();
		}
		
    	return $ret;
	}
}

?>

