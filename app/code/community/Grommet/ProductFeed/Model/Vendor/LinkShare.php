<?php
/**
 * LinkShare vendor feed
 *
 * @author tmannherz
 */
class Grommet_ProductFeed_Model_Vendor_LinkShare extends Grommet_ProductFeed_Model_Vendor_Ftp
{
	const CODE		= 'linkshare';
	const DELIMITER	= '|';
	const SEPARATOR = '~~';
	const EOL		= "\r\n";
	
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
		$mId = $this->getVendorConfig('merchant_id', $storeId);
		$company = $this->getVendorConfig('company', $storeId);
		if (!$mId || !$company) {
			Mage::throwException(Mage::helper('productfeed')->__('LinkShare Merchant ID and Company Name must be set.'));
		}
		Varien_Profiler::start('productfeed_' . $this->getVendorCode() . '::' . __FUNCTION__);
		
		$content = implode(self::DELIMITER, array('HDR', $mId, $company, Mage::getModel('core/date')->date('Y-m-d/H:i:s'))) . self::EOL;
		foreach ($productsData as $row) {
			$content .= $row . self::EOL;
		}
		
		$filename = $mId . '_nmerchandis' . Mage::getModel('core/date')->date('Ymd') . '.txt';
		$filepath = $this->getFeedStorageDir() . $filename;
		try {
			$ioAdapter = new Varien_Io_File();
			$ioAdapter->setAllowCreateFolders(true);
			$ioAdapter->createDestinationDir($this->getFeedStorageDir());
			$ioAdapter->cd($this->getFeedStorageDir());
			$ioAdapter->streamOpen($filename);
			$ioAdapter->streamWrite($content);
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
			$price = $finalPrice = $taxHelper->getPrice($product, $product->getMinimalPrice());
		}
		else {
			$price = $taxHelper->getPrice($product, $product->getPrice());
			$finalPrice = $taxHelper->getPrice($product, $product->getFinalPrice());
		}

		// required atts
		$name = trim($product->getData($this->getAttributeMapping('name')));
		$shortDescription = trim(strip_tags($product->getData($this->getAttributeMapping('short_description'))));

		if (!$price || !$name || !$shortDescription) {
			return;
		}

		// add-to-cart URL
		if ($product->isSuper()) {
			$buyRedirect = $product->getProductUrl();
		}
		else {
			$buyRedirect = Mage::getUrl('checkout/cart');
		}
		$buyUrlParams = array(
			Mage_Core_Controller_Front_Action::PARAM_NAME_URL_ENCODED => Mage::helper('core')->urlEncode($buyRedirect)
		);
		$buyUrl = Mage::helper('checkout/cart')->getAddUrl($product, $buyUrlParams);

		$row = array(
			'product_id' => $product->getEntityId(),
			'name' => $name,
			'sku' => (string)$product->getData($this->getAttributeMapping('sku')),
			'primary_category' => $this->getPrimaryCategory($product),
			'secondary_categories' => $this->getSecondaryCategories($product),
			'product_url' => $product->getProductUrl(),
			'product_image_url' => $this->getImageUrl($product, $this->getAttributeMapping('image')),
			'buy_url' => $buyUrl,
			'short_description' => $shortDescription,
			'description' => strip_tags((string)$product->getData($this->getAttributeMapping('description'))),
			'discount' => '',
			'discount_type' => '',
			'sale_price' => $finalPrice,
			'retail_price' => $price,
			'begin_date' => $this->formatDate($product->getData($this->getAttributeMapping('news_from_date'))),
			'end_date' => '',
			'brand' => (string)$product->getData($this->getAttributeMapping('brand')),
			'shipping' => '',
			'is_deleted_flag' => 'N',
			'keywords' => (string)$product->getData($this->getAttributeMapping('meta_keyword')),
			'is_all_flag' => 'Y',
			'manufacturer_part_number' => (string)$product->getData($this->getAttributeMapping('manufacturer_part_number')),
			'manufacturer' => (string)$product->getData($this->getAttributeMapping('manufacturer')),
			'shipping_info' => (string)$product->getData($this->getAttributeMapping('shipping_info')),
			'stock_status' => $product->getSalable() ? Mage::helper('productfeed')->__('In Stock') : Mage::helper('productfeed')->__('Out of Stock'),
			'upc' => (string)$product->getData($this->getAttributeMapping('upc')),
			'class_id' => '',
			'is_product_link_flag' => 'Y',
			'is_storefront_flag' => 'N',
			'is_merchandise_flag' => 'Y',
			'currency' => Mage::app()->getStore($product->getStoreId())->getCurrentCurrencyCode(),
			'm1' => ''
		);
		
		$this->sanitizeRow($row);

		$row = new Varien_Object($row);
		Mage::dispatchEvent('productfeed_addproducttofeed', array('vendor' => $this, 'row' => $row));

		$args['products'][] = implode(self::DELIMITER, $row->getData());
	}

	/**
	 * Removes deliniating and other illegal chars from each field. Formats other fields.
	 * 
	 * @param array $row
	 */
	protected function sanitizeRow (array &$row)
	{
		if (strlen($row['short_description']) > 500) {
			$row['short_description'] = substr($row['short_description'], 0, 497) . '...';
		}
		if (strlen($row['description']) > 2000) {
			$row['description'] = substr($row['description'], 0, 1997) . '...';
		}
		if (strlen($row['keywords'])) {
			$keywords = array();
			$split = preg_split('/[\n,]/', $row['keywords']);
			foreach ($split as $keyword) {
				$keyword = trim($keyword);
				if ($keyword) {
					$keywords[] = $keyword;
				}
			}
			$row['keywords'] = implode(self::SEPARATOR, $keywords);
		}
		foreach ($row as &$field) {
			$field = str_replace('&nbsp;', ' ', $field);
			$field = preg_replace('/[|\n\r]/', '', $field);
		}
	}

	/**
	 * @param Mage_Catalog_Model_Product $product
	 * @return string
	 */
	public function getPrimaryCategory (Mage_Catalog_Model_Product $product)
	{
		return ''; //$product->getAttributeText($this->getAttributeMapping('primary_category'));
	}

	/**
	 * @param Mage_Catalog_Model_Product $product
	 * @return string
	 */
	public function getSecondaryCategories (Mage_Catalog_Model_Product $product)
	{
		$categories = array();
		$collection = $product->getCategoryCollection()
			->addAttributeToSelect('name', 'inner')
			->addAttributeToFilter('is_active', 1)
			->addAttributeToFilter('entity_id', array('nin' => Mage::app()->getStore()->getRootCategoryId()));
		
		foreach ($collection as $category) {
			if ($category->getName()) {
				$categories[] = $category->getName();
			}
		}
		return implode(self::SEPARATOR, $categories);
	}

	/**
	 * Returns a date in the mm/dd/yyyy LinkShare format.
	 *
	 * @param string $date
	 * @return string
	 */
	public function formatDate ($date)
	{
		if (!$date) {
			return '';
		}
		return date('m/d/Y', strtotime($date));
	}
}
