<?php
/**
 * Tid.al vendor feed.
 *
 * @author dsanford
 */
class Grommet_ProductFeed_Model_Vendor_Tidal extends Grommet_ProductFeed_Model_Vendor_Abstract
{
	const CODE = 'tidal';

	/**
	 * @var array
	 */
	protected $_fields = array(
        'name',
        'description',
        'image',
        'url',
        'retailer',
        'item_id',
        'price',
        'category',
		'item_id',
		'category',
        'launch_date'
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

		$collection->addFieldToFilter('type_id', Mage_Catalog_Model_Product_Type_Grouped::TYPE_CODE);

		Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
		Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($collection);

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
		Varien_Profiler::start('productfeed_' . $this->getVendorCode() . '::' . __FUNCTION__);

		$filename = $this->getVendorCode() . '_' . Mage::getModel('core/date')->date('Ymd') . '.json';
		$filepath = $this->getFeedStorageDir() . $filename;
		try {
			$ioAdapter = new Varien_Io_File();
			$ioAdapter->setAllowCreateFolders(true);
			$ioAdapter->createDestinationDir($this->getFeedStorageDir());
			$ioAdapter->cd($this->getFeedStorageDir());
			$ioAdapter->streamOpen($filename);
			$ioAdapter->streamWrite(json_encode($productsData));
			Varien_Profiler::stop('productfeed_' . $this->getVendorCode() . '::' . __FUNCTION__);
			return $filepath;
		} catch (Exception $e) {
			Varien_Profiler::stop('productfeed_' . $this->getVendorCode() . '::' . __FUNCTION__);
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
		if ($product->isGrouped()) {
			$finalPrice = $taxHelper->getPrice($product, $product->getMinimalPrice());
		}
		else {
			$finalPrice = $taxHelper->getPrice($product, $product->getFinalPrice());
		}

		// required atts
		$name = trim($product->getData($this->getAttributeMapping('name')));
		$shortDescription = trim(strip_tags($product->getData($this->getAttributeMapping('short_description'))));
		
		if (!$finalPrice || !$name || !$shortDescription) {
			return;
		}

		$row = array(
			'name' => $name,
			'description' => $shortDescription,
			'image' => $this->getImageUrl($product, $this->getAttributeMapping('image')),
			'url' => $product->getProductUrl(),
			'retailer' => '',
			'item_id' => $product->getId(),
			'price' => $finalPrice,
			'category' => '', //$product->getAttributeText($this->getAttributeMapping('category')),
		);

		$this->sanitizeRow($row);

		$row = new Varien_Object($row);
		Mage::dispatchEvent('productfeed_addproducttofeed', array('vendor' => $this, 'row' => $row));

		$args['products'][] = $row->getData();
	}

	/**
	 * Removes deliniating and other illegal chars from each field. Formats other fields.
	 *
	 * @param array $row
	 */
	protected function sanitizeRow (array &$row)
	{
		foreach ($row as &$field) {
			$field = preg_replace('/[\n\r]/', ' ', $field);
		}
	}

	/**
	 * Returns the publishing model.
	 *
	 * @return Grommet_ProductFeed_Model_Feed_Publisher_File
	 */
	public function getPublisher ()
	{
		return Mage::getModel('productfeed/feed_publisher_file');
	}

	/**
	 * Returns publishing parameters.
	 *
	 * @param int $storeId
	 * @return array
	 */
	public function getPublishParams ($storeId = null)
	{
		return array('destination_path' => $this->getPublicFeedStorageDir() . 'tidal.json');
	}
}
