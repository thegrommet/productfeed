<?php
/**
 * Rakuten inventory feed
 *
 * @author tmannherz
 */
class Grommet_ProductFeed_Model_Vendor_Rakuten_Inventory extends Grommet_ProductFeed_Model_Vendor_Ftp
{
	const CODE			= 'rakuten';
	const DELIMITER		= "\t";
	const FEED_VERSION	= '5.0';
	
	/**
	 * @var array 
	 */
	protected $_fields = array(
		'ListingId',
		'ProductId',
		'ProductIdType',
		'ItemCondition',
		'Price',
		'MAP',
		'MAPType',
		'Quantity',
		'OfferExpeditedShipping',
		'Description',
		'ShippingRateStandard',
		'ShippingRateExpedited',
		'ShippingLeadTime',
		'OfferTwoDayShipping',
		'ShippingRateTwoDay',
		'OfferOneDayShipping',
		'ShippingRateOneDay',
		'OfferSameDayShipping',
		'ShippingRateSameDay',
		'OfferLocalDeliveryShippingRates',
		'ReferenceId'		
	);
	
	/**
	 * Returns the vendor code.
	 * 
	 * @return string
	 */
	public function getVendorCode ()
	{
		return self::CODE;
	}

	/**
	 * Prepare and return the product collection.
	 *
	 * @param int $storeId
	 * @return Mage_Catalog_Model_Resource_Product_Collection
	 */
	public function prepareProductCollection ($storeId)
	{
		$collection = parent::prepareProductCollection($storeId);
		$collection->addFieldToFilter('type_id', array('neq' => Mage_Catalog_Model_Product_Type_Grouped::TYPE_CODE));

		$configManageStock = (int)Mage::getStoreConfigFlag(Mage_CatalogInventory_Model_Stock_Item::XML_PATH_MANAGE_STOCK);
		$manageColumn = new Zend_Db_Expr(
			'IF(' .
			' (stock_item.use_config_manage_stock = 0 AND stock_item.manage_stock = 1)' .
			" OR (stock_item.use_config_manage_stock = 1 AND {$configManageStock} = 1)" .
			', 1, 0)'
		);

		$collection->getSelect()->join(
			array('stock_item' => $collection->getTable('cataloginventory/stock_item')),
			'e.entity_id = stock_item.product_id',
			array(
				'inventory_qty' => 'qty',
				'manage_stock' => $manageColumn
			)
		);

		return $collection;
	}

	/**
	 * Prepare the feed file and returns its path
	 *
	 * @param array $productsData
	 * @param int $storeId
	 * @return string
	 */
	public function prepareFeed (array $productsData, $storeId)
	{
		$filename = 'rakuten_inventory_' . Mage::getModel('core/date')->date('Ymd') . '.txt';
		$filepath = $this->getFeedStorageDir() . $filename;
		try {
			$ioAdapter = new Varien_Io_File();
			$ioAdapter->setAllowCreateFolders(true);
			$ioAdapter->createDestinationDir($this->getFeedStorageDir());
			$ioAdapter->cd($this->getFeedStorageDir());
			$ioAdapter->streamOpen($filename);
			$ioAdapter->streamWriteCsv(array('##Type=Inventory;Version=' . self::FEED_VERSION), self::DELIMITER);
			$ioAdapter->streamWriteCsv($this->_fields, self::DELIMITER);
			foreach ($productsData as $row) {
				$ioAdapter->streamWriteCsv($row, self::DELIMITER);
			}
			return $filepath;
		} catch (Exception $e) {
			Mage::throwException(Mage::helper('productfeed')->__('Could not write feed file to path: %s, %s', $filepath, $e->getMessage()));
		}
	}

	/**
	 * Iterator callback to add a product to the feed.
	 *
	 * @param array $args
	 */
	public function addProductToFeed ($args)
	{
		$product = Mage::getModel('catalog/product')->setData($args['row']);
		/* @var $product Mage_Catalog_Model_Product */

		// price
		$taxHelper  = Mage::helper('tax');
		if ($product->getData($this->getAttributeMapping('rakuten_feed_price'))) {
			$finalPrice = $taxHelper->getPrice($product, $product->getData($this->getAttributeMapping('rakuten_feed_price')));
		}
		else {
			$finalPrice = $taxHelper->getPrice($product, $product->getFinalPrice());
		}

		if (!$finalPrice) {
			return;
		}

		$shippingStd = $product->getData($this->getAttributeMapping('rakuten_shipping_std'));
		if (!strlen($shippingStd)) {
			$shippingStd = $this->getInventoryConfig('shipping_std_default', $product->getStoreId());
		}
		$shippingStd = $taxHelper->getPrice($product, $shippingStd);
		
		$row = array(
			'listing_id' => '',
			'product_id' => (string)$product->getData($this->getAttributeMapping('sku')),
			'product_id_type' => 3,  // seller SKU
			'item_condition' => 1,  // brand new
			'price' => $finalPrice,
			'map' => '',  // minimum advertised price
			'map_type' => 0,
			'quantity' => $this->getStockQuantity($product),
			'offer_expedited_shipping' => 0,
			'description' => (string)$product->getData($this->getAttributeMapping('inventory_description')),
			'shipping_rate_standard' => $shippingStd,
			'shipping_rate_expedited' => '',
			'shipping_lead_time' => '',
			'offer_two_day_shipping' => 0,
			'shipping_rate_two_day' => '',
			'offer_one_day_shipping' => 0,
			'shipping_rate_one_day' => '',
			'offer_same_day_shipping' => 0,
			'shipping_rate_same_day' => '',
			'offer_local_delivery_shipping_rates' => 0,
			'reference_id' => (string)$product->getData($this->getAttributeMapping('sku'))
		);

		$this->sanitizeRow($row);

		$row = new Varien_Object($row);
		Mage::dispatchEvent('productfeed_addproducttofeed', array('vendor' => $this, 'row' => $row));

		$args['products'][] = $row->getData();
	}

	/**
	 * @param Mage_Catalog_Model_Product $product
	 * @return int
	 */
	public function getStockQuantity (Mage_Catalog_Model_Product $product)
	{
		if (!$product->getSalable()) {
			return 0;
		}
		if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
			/**
			 * Attempt to calculate available bundle qty. This is really just an estimate as bundle option
			 * qty can, depending on config, be specified by the customer.
			 */
			$selections = $product->getTypeInstance()->getSelectionsCollection($product->getTypeInstance()->getOptionsIds());
			$min = 0;
			foreach ($selections as $selection) {
				if ($selection->getIsSalable()) {
					$optionQty = floor($selection->getStockItem()->getQty() / $selection->getSelectionQty());
					if ($min === 0) {
						$min = $optionQty;
					}
					else {
						$min = min($min, $optionQty);
					}
				}
			}
			$product->setInventoryQty($min);
		}
		if ($product->getManageStock()) {
			return max(0, floor($product->getInventoryQty() * (int)$this->getInventoryConfig('inventory_percentage', $product->getStoreId()) / 100));
		}
		else {  // stock not managed, set max available inventory
			return 9999;
		}
	}

	/**
	 * Removes deliniating and other illegal chars from each field. Formats other fields.
	 * 
	 * @param array $row
	 */
	protected function sanitizeRow (array &$row)
	{
		foreach ($row as &$field) {
			$field = preg_replace('/[\t\n\r]/', '', $field);
		}
	}

	/**
	 * Get a inventory-specific config field.
	 *
	 * @param string $field
	 * @param int $storeId
	 * @return mixed
	 */
	public function getInventoryConfig ($field, $storeId = null)
	{
		return Mage::getStoreConfig('productfeed/rakuten_inventory/' . $field, $storeId);
	}

	/**
	 * @param int $storeId
	 * @return array
	 */
	public function getPublishParams ($storeId = null)
	{
		$params = parent::getPublishParams($storeId);
		$params['path'] = $this->getInventoryConfig('ftp_remote_folder', $storeId);

		return $params;
	}
}
