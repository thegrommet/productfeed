<?php
/**
 * LinkShare Refund model
 *
 * @author tmannherz
 */
class Grommet_ProductFeed_Model_Vendor_LinkShare_Refund extends Grommet_ProductFeed_Model_Vendor_LinkShare
{
	/**
	 * Submit refunds back to LinkShare for reimbursement.
	 *
	 * @param int $storeId
	 * @return Grommet_ProductFeed_Model_Vendor_LinkShare_Refund
	 */
	public function processDailyRefunds ($storeId)
	{
		$rows = array();
		$ordersProcessed = array();

		// add credit memos to the feed
		$creditmemos = $this->_getCreditMemos($storeId);
		if (count($creditmemos)) {
			foreach ($creditmemos as $creditmemo) {
				$rows = array_merge($rows, $this->creditmemoToFeed($creditmemo));
				$ordersProcessed[] = $creditmemo->getOrderId();
			}
		}

		// add cancelations to the feed
		$orders = $this->_getOrders($storeId, $ordersProcessed);
		if (count($orders)) {
			foreach ($orders as $order) {
				$rows = array_merge($rows, $this->orderToFeed($order));
			}
		}

		if (count($rows)) {
			$mId = $this->getVendorConfig('merchant_id', $storeId);
			if (!$mId) {
				Mage::throwException(Mage::helper('productfeed')->__('LinkShare Merchant ID must be set.'));
			}

			$content = '';
			foreach ($rows as $row) {
				$content .= implode(self::DELIMITER, array_values($row)) . self::EOL;
			}

			$filename = $mId . '_trans' . Mage::getModel('core/date')->date('Ymd') . '.txt';
			$filepath = $this->getFeedStorageDir() . $filename;
			try {
				$ioAdapter = new Varien_Io_File();
				$ioAdapter->setAllowCreateFolders(true);
				$ioAdapter->createDestinationDir($this->getFeedStorageDir());
				$ioAdapter->cd($this->getFeedStorageDir());
				$ioAdapter->streamOpen($filename);
				$ioAdapter->streamWrite($content);
			} catch (Exception $e) {
				Mage::throwException(Mage::helper('productfeed')->__('Could not write refund file to path: %s, %s', $filepath, $e->getMessage()));
			}

			$publisher = $this->getPublisher();
			$publisher->publish($filepath, $this->getPublishParams($storeId));
		}
		return $this;
	}

	/**
	 * Convert a creditmemo to a LinkShare refund row.
	 *
	 * @param Mage_Sales_Model_Order_Creditmemo $creditmemo
	 * @return array
	 */
	public function creditmemoToFeed (Mage_Sales_Model_Order_Creditmemo $creditmemo)
	{
		$orderDate = new Zend_Date($creditmemo->getOrderDate(), Varien_Date::DATETIME_INTERNAL_FORMAT);
		$orderDate = $orderDate->toString('yyyy-MM-dd');
		$cmDate = new Zend_Date($creditmemo->getCreatedAt(), Varien_Date::DATETIME_INTERNAL_FORMAT);
		$cmDate = $cmDate->toString('yyyy-MM-dd');

		$rows = array();
		foreach ($creditmemo->getAllItems() as $cmItem) {
			/* @var $cmItem Mage_Sales_Model_Order_Creditmemo_Item */
			if (!$cmItem->getOrderItem()->getParentItemId()) {
				$rows[] = array(
					'order_id' => $creditmemo->getOrderIncrementId(),
					'site_id' => '',  // always blank
					'order_date' => $orderDate,
					'transaction_date' => $cmDate,
					'sku' => $cmItem->getSku(),
					'quantity' => $cmItem->getQty(),
					'amount' => ($cmItem->getBaseRowTotal() - $cmItem->getBaseDiscountAmount()) * -100,
					'currency' => $creditmemo->getBaseCurrencyCode(),
					'blank' => '',
					'blank' => '',
					'blank' => '',
					'product_name' => ''
				);
			}
		}
		return $rows;
	}

	/**
	 * Convert an order to a LinkShare refund row.
	 *
	 * @param Mage_Sales_Model_Order $order
	 * @return array
	 */
	public function orderToFeed (Mage_Sales_Model_Order $order)
	{
		$orderDate = new Zend_Date($order->getCreatedAt(), Varien_Date::DATETIME_INTERNAL_FORMAT);
		$orderDate = $orderDate->toString('yyyy-MM-dd');
		$transDate = Mage::getModel('core/date')->date('Y-m-d');

		$rows = array();
		foreach ($order->getAllVisibleItems() as $item) {
			/* @var $item Mage_Sales_Model_OrderItem */
			$rows[] = array(
				'order_id' => $order->getIncrementId(),
				'site_id' => '',  // always blank
				'order_date' => $orderDate,
				'transaction_date' => $transDate,
				'sku' => $item->getSku(),
				'quantity' => $item->getQtyCanceled(),
				'amount' => ($item->getBaseRowTotal() - $item->getBaseDiscountAmount()) * -100,
				'currency' => $order->getBaseCurrencyCode(),
				'blank' => '',
				'blank' => '',
				'blank' => '',
				'product_name' => ''
			);
		}
		return $rows;
	}

	/**
	 * Get a collection of credit memos from the day to process.
	 *
	 * @param int $storeId
	 * @return Mage_Sales_Model_Resource_Order_Creditmemo_Collection
	 */
	protected function _getCreditMemos ($storeId)
	{
		$to = Mage::app()->getLocale()->date()
				->subDay(1)
				->subSecond(Mage::getModel('core/date')->getGmtOffset())
				->toString(Varien_Date::DATETIME_INTERNAL_FORMAT);

		$collection = Mage::getResourceModel('sales/order_creditmemo_collection');
		/* @var $collection Mage_Sales_Model_Resource_Order_Creditmemo_Collection */
		$collection
			->addFieldToFilter('main_table.created_at', array('gteq' => $to))
			->addFieldToFilter('main_table.store_id', $storeId);

		$collection->getSelect()
			->join(
				array('order' => $collection->getTable('sales/order')),
				'main_table.order_id = order.entity_id',
				array(
					'order_increment_id' => 'increment_id',
					'order_date' => 'created_at'
				)
			)
			->where('order.external_source = ?', Grommet_ProductFeed_Model_Vendor_LinkShare::CODE);

		return $collection;
	}

	/**
	 * Get a collection of credit memos from the day to process.
	 *
	 * @param int $storeId
	 * @param array $excludes
	 * @return Mage_Sales_Model_Resource_Order_Collection
	 */
	protected function _getOrders ($storeId, array $excludes = array())
	{
		$to = Mage::app()->getLocale()->date()
				->subDay(1)
				->subSecond(Mage::getModel('core/date')->getGmtOffset())
				->toString(Varien_Date::DATETIME_INTERNAL_FORMAT);

		$states = array(
			Mage_Sales_Model_Order::STATE_CANCELED,
			Mage_Sales_Model_Order::STATE_CLOSED
		);

		$collection = Mage::getResourceModel('sales/order_collection');
		/* @var $collection Mage_Sales_Model_Resource_Order_Collection */
		$collection
			->addFieldToFilter('created_at', array('gteq' => $to))
			->addFieldToFilter('store_id', $storeId)
			->addFieldToFilter('external_source', Grommet_ProductFeed_Model_Vendor_LinkShare::CODE)
			->addFieldToFilter('entity_id', array('nin' => $excludes))
			->addFieldToFilter('state', array('in' => $states));

		return $collection;
	}
}
