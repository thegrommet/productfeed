<?php
/**
 * MediaForge vendor feed.
 *
 * @author tmannherz
 */
class Grommet_ProductFeed_Model_Vendor_MediaForge extends Grommet_ProductFeed_Model_Vendor_Ftp
{
	const CODE			= 'mediaforge';
	const DELIMITER		= ',';

	/**
	 * @var array
	 */
	protected $_fields = array(
		'Product ID',
		'Category',
		'Title',
		'Link',
		'Sku',
		'Price',
		'Brand',
		'Image',
		'Description',
		'Manufacturer',
		'Sale',
		'First Launch Date',
		'Video Link'
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

		// product type filtering
		//$collection->addFieldToFilter('type_id', Mage_Catalog_Model_Product_Type_Grouped::TYPE_CODE);

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
		$filename = 'mediaforge_' . Mage::getModel('core/date')->date('Ymd') . '.csv';
		$filepath = $this->getFeedStorageDir() . $filename;
		try {
			$ioAdapter = new Varien_Io_File();
			$ioAdapter->setAllowCreateFolders(true);
			$ioAdapter->createDestinationDir($this->getFeedStorageDir());
			$ioAdapter->cd($this->getFeedStorageDir());
			$ioAdapter->streamOpen($filename);
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
			'product_id' => $product->getId(),
			'category' => '', //$product->getAttributeText($this->getAttributeMapping('category')),
			'title' => $name,
			'link' => $product->getProductUrl(),
			'sku' => (string)$product->getData($this->getAttributeMapping('sku')),
			'price' => $finalPrice,
			'brand' => '',
			'image' => $this->getImageUrl($product, $this->getAttributeMapping('image')),
			'description' => $shortDescription,
			'manufacturer' => '',
			'sale' => '',
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
}
