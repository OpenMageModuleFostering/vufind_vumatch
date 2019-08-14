<?php

Class Vufind_Vumatch_Model_Abstract extends Mage_Core_Model_Abstract
{

	public function getVersion()
	{
		$version = Mage::getConfig()->getNode('modules')->Vufind_Vumatch->version;
		return $version;
		
	}
	
	public function getVufindId() 
	{
		if (!$this->hasData('vufind_id')) {
			$session = Mage::getSingleton("customer/session");
			
			if ($session->isLoggedIn()) {
				if (($email = $session->getCustomer()->getEmail()) != '') {
					$this->setTrouvusId($email);
				}else{
					$this->setTrouvusId($session->getCustomerId());
				}
			} else {
				$cookieName = 'vufind_usr';
				$cookieVal = Mage::app()->getFrontController()->getRequest()->getCookie($cookieName);

				if (strlen($cookieVal))
					return $cookieVal;
				else {
					$newCookie = $this->create_anonymous_cookie();
					Mage::getModel('core/cookie')->set($cookieName, $newCookie, 86400*100, '/', null, null, false);
					return $newCookie;
				}
			}
		}
		return $this->getData('vufind_id');
	}
	
    public function getCurrentProduct()
    {
        if (!$this->hasData('product')) {
            $this->setData('product', Mage::registry('product'));
        }
        return $this->getData('product');
    }

	private function create_anonymous_cookie()
	{
		$cookie = 'usr_'.$this->ms_time().'_'.rand(0,100000);
		return $cookie;
	}
	
	private function ms_time()
	{
		$timeday = gettimeofday();
		$sec = intval($timeday['sec']);
		$msec = intval(floor($timeday['usec']/1000));
		if (strlen($msec) == 2) $msec = '0'.$msec;
		elseif (strlen($msec) == 1) $msec = '00'.$msec;
			
		return $sec.$msec;
	}

	public function isActive()
	{
		if (!Mage::getStoreConfigFlag('vumatch/account/active')) return false;
		if (strlen($this->getApiKey()) < 1) return false;
		if (strlen($this->getApiToken()) < 1) return false;
		if (strlen($this->getCustomerId()) < 1) return false;
		return true;
	}

	public function getApiKey() 
	{
		if (!$this->hasData('vumatch_api_key')) {
			$this->setData('vumatch_api_key', Mage::getStoreConfig('vumatch/account/api_key'));
		}
		return $this->getData('vumatch_api_key');	
	}

	public function getApiToken() 
	{
		if (!$this->hasData('vumatch_api_token')) {
			$this->setData('vumatch_api_token', Mage::getStoreConfig('vumatch/account/api_token'));
		}
		return $this->getData('vumatch_api_token');	
	}

	public function getCustomerId() 
	{
		if (!$this->hasData('vumatch_customer_id')) {
			$this->setData('vumatch_customer_id', Mage::getStoreConfig('vumatch/account/customer_id'));
		}
		return $this->getData('vumatch_customer_id');	
	}
}

?>
