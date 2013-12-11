<?php
/**
 * LinkShare Product Feed Observer
 *
 * @author tmannherz
 */
class Grommet_ProductFeed_Model_Vendor_LinkShare_Observer
{
	/**
	 * Set LinkShare cookie if referral source in URL is detected.
	 * 
	 * @param Varien_Event_Observer $observer
	 */
	public function predispatch (Varien_Event_Observer $observer)
	{
		if (!$this->isEnabled()) {
			return;
		}
		$action = $observer->getEvent()->getControllerAction();
		if ($action->getRequest()->getParam('siteID')) {
			$cookie = Mage::getModel('productfeed/vendor_linkShare_cookie');
			$cookie->init($action->getRequest()->getParam('siteID'));
		}
	}

	/**
	 * Set LinkShare source on the order.
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function prepareOrder (Varien_Event_Observer $observer)
	{
		if (!$this->isEnabled()) {
			return;
		}
		$order = $observer->getEvent()->getOrder();
		$cookie = Mage::getModel('productfeed/vendor_linkShare_cookie');
		if ($order && $cookie->hasData()) {
			$order->setExternalSource(Grommet_ProductFeed_Model_Vendor_LinkShare::CODE);
		}
	}

	/**
	 * Set order IDs on the tracking pixel block.
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function orderSuccess (Varien_Event_Observer $observer)
	{
		if (!$this->isEnabled()) {
			return;
		}
		$orderIds = $observer->getEvent()->getOrderIds();
		if (empty($orderIds) || !is_array($orderIds)) {
			return;
		}
		$block = Mage::app()->getLayout()->getBlock('productfeed_linkshare');
		if ($block) {
			$block->setOrderIds($orderIds);
		}
	}

	/**
	 * @return bool
	 */
	public function isEnabled ()
	{
		return Mage::helper('productfeed')->isFeedEnabled(Grommet_ProductFeed_Model_Vendor_LinkShare::CODE);
	}
}