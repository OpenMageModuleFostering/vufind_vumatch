<?php

Class Vufind_Vumatch_Block_Widget extends Mage_Core_Block_Template
{
	protected $_checkout = null;
	protected $_quote = null;
	protected $_template = "vufind/vumatch/widget.phtml";
	protected $helper = null;

	public function _construct()
	{
		parent::_construct();
		$this->helper = Mage::helper('vumatch');
	}

	protected function _toHtml()
	{
		$model = Mage::getModel('vumatch/abstract');
		if (!$model->isActive()) {
            return '';
        }
        
        if (!$this->hasData('widget_name'))
        	$this->setWidgetName($this->_getTemplate());

		return parent::_toHtml();	
	} 

	protected function _getTemplate() 
	{
		switch($this->helper->getCurrentPage()) {
			case "catalog_product_view":
				return "catalog_product_view";
				break;
				
			default:
				return "cms_index_index";
				break;
		}
	}

    private function getProduct()
    {
        if (!$this->hasData('product')) {
            $this->setData('product', Mage::registry('product'));
        }
        return $this->getData('product');
    }

	public function getHtmlCode()
	{
		$product = $this->getProduct();
		$productId = $product->getId();

		$api = Mage::getSingleton('vumatch/api');
		$ret = $api->getSimilarProducts($productId);

		$html = '';
		$total = 0;

		if($ret['success'] == false) {
			return $html;
		}

		$recommendations = $ret['message'];
		$recommendations = json_decode($recommendations, true);
		foreach($recommendations as $recommendation) {
			$productSku = $recommendation['id'];
			$product = Mage::getModel('catalog/product')->load($product->getIdBySku($productSku));
			if(!empty($product)) {
				
				$visibility = $product->getVisibility();
				if ($visibility != 4) {
					continue;
				}
				//if($total < 1){ continue; }
				$url = $product->getProductUrl();

				$html .= '<div style="text-align: center; display: inline-block;width:140px;vertical-align:top;">';
				$html .= '<a class="product-image" href="' . $url . '"><img width="135" src="' . 
					$product->getImageUrl() . '" />' . $product->getName();
				$html .= '</a></div>&nbsp;';
				
				$total = $total + 1;
				if ($total == 7) break;
			}
		}

		if($html == '') $html = 'No recommendations for now';
		return $html;
	}
}


?>

