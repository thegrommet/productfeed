<?php
/**
 * Order Success Tracking Pixel
 *
 * @author tmannherz
 * @method array getOrderIds()
 */
class Grommet_ProductFeed_Block_LinkShare_TrackingPixel extends Mage_Core_Block_Abstract
{
	const DELIMITER = '|';
	
	/**
	 * @var Mage_Sales_Model_Resource_Order_Collection 
	 */
	protected $_orders;

	/**
	 * @return string
	 */
	protected function _toHtml ()
	{
		$cookie = Mage::getModel('productfeed/vendor_linkShare_cookie');
		if (!$cookie->hasData() || !$this->getConfig('merchant_id') || !$this->getTrackingDomain()
			|| !is_array($this->getOrderIds()) || !count($this->getOrders())) {
			return '';
		}
		/**
		 * Check excluded email addresses.
		 */
		$orders = array();
		foreach ($this->getOrders() as $order) {
			if (!$this->_isExcluded($order)) {
				$orders[] = $order;
			}
		}
		if (!count($orders)) {
			return '';
		}

		$mId = $this->getConfig('merchant_id');
		/**
		 * LinkShare assumes only one order is placed in the transaction - use the first
		 */
		$first = reset($orders);
		$orderId = $first->getIncrementId();
		$currencyCode = $first->getBaseCurrencyCode();

		$skus = $qtys = $amts = $names = array();
		foreach ($orders as $order) {
			foreach ($order->getAllVisibleItems() as $item) {
				/* @var $item Mage_Sales_Model_Order_Item */
				$skus[] = str_replace(self::DELIMITER, '', $item->getSku());
				$qtys[] = round($item->getQtyOrdered());
				$amts[] = ($item->getBaseRowTotal() - $item->getBaseDiscountAmount()) * 100;  // all prices are x 100
				$names[] = str_replace(self::DELIMITER, '-', $item->getName());
			}
		}

		$queryParams = array(
			'mid' => $mId,
			'ord' => $orderId,
			'skulist' => implode(self::DELIMITER, $skus),
			'qlist' => implode(self::DELIMITER, $qtys),
			'amtlist' => implode(self::DELIMITER, $amts),
			'cur' => $currencyCode,
			'namelist' => implode(self::DELIMITER, $names),
		);

		$query = str_replace('%7C', self::DELIMITER, http_build_query($queryParams));  // pipe shouldn't be url encoded
		$src = $this->getTrackingDomain() . '?' . $query;
		
		return sprintf('<div style="display: none;"><img src="%s" alt="" /></div>', $src);
	}
	
	/**
	 * @return Mage_Sales_Model_Resource_Order_Collection
	 */
	public function getOrders ()
	{
		if ($this->_orders === null) {
			$this->_orders = Mage::getResourceModel('sales/order_collection')
				->addFieldToFilter('entity_id', array('in' => $this->getOrderIds()));
		}
		return $this->_orders;
	}

	/**
	 * @return string|bool
	 */
	public function getTrackingDomain ()
	{
		$domain = $this->getConfig('tracking_domain');
		if (!$domain) {
			return false;
		}
		if ($this->getRequest()->isSecure()) {
			$domain = 'https://' . $domain;
		}
		else {
			$domain = 'http://' . $domain;
		}
		return $domain;
	}
	
	/**
	 * Get a LinkShare config field.
	 *
	 * @param string $field
	 * @param int $storeId
	 * @return mixed
	 */
	public function getConfig ($field, $storeId = null)
	{
		return Mage::getStoreConfig('productfeed/linkshare/' . $field, $storeId);
	}

	/**
	 * Should an order be excluded from tracking?
	 *
	 * @param Mage_Sales_Model_Order $order
	 * @return bool
	 */
	protected function _isExcluded (Mage_Sales_Model_Order $order)
	{
		$emails = $this->getConfig('excluded_emails', $order->getStoreId());
		if (!empty($emails)) {
			$emails = explode("\n", $emails);
			foreach ($emails as $email) {
				$email = trim($email);
				if (stripos($order->getCustomerEmail(), $email) !== false) {
					return true;
				}
			}
		}
		$groups = $this->getConfig('excluded_customer_groups', $order->getStoreId());
		if (!empty($groups)) {
			$groups = explode(',', $groups);
			foreach ($groups as $group) {
				if ($group == $order->getCustomerGroupId()) {
					return true;
				}
			}
		}
		return false;
	}
}
