<?php
/**
 * Abstract Product Feed Vendor
 *
 * @author tmannherz
 * @method Mage_Catalog_Model_Resource_Product_Collection getProductCollection()
 */
abstract class Grommet_ProductFeed_Model_Vendor_Abstract extends Varien_Object
{
	const DEFAULT_IMAGE_WIDTH = 500;
	const DEFAULT_IMAGE_HEIGHT = 500;
	
	/**
	 * @var array 
	 */
	protected $attributeMap = null;

	/**
	 * Generate the product feed and return its path.
	 *
	 * @param int $storeId
	 * @return string
	 */
	public function generate ($storeId)
	{
		Varien_Profiler::start('productfeed_' . $this->getVendorCode() . '::' . __FUNCTION__);
		
		// disable flat config
		$flatConfigPath = Mage_Catalog_Helper_Product_Flat::XML_PATH_USE_PRODUCT_FLAT;
		$flatConfig = Mage::getStoreConfigFlag($flatConfigPath, $storeId);
		Mage::app()->getStore($storeId)->setConfig($flatConfigPath, 0);

		$collection = $this->prepareProductCollection($storeId);
		$this->setProductCollection($collection);
		
		$eventParams = array('vendor' => $this, 'products' => $collection);
		Mage::dispatchEvent('productfeed_generatefeed', $eventParams);
		Mage::dispatchEvent('productfeed_generatefeed_' . $this->getVendorCode(), $eventParams);

		//Mage::helper('productfeed')->log($collection->getSelectSql(1));

		$productsData = array();
		Mage::getSingleton('core/resource_iterator')->walk(
			$collection->getSelect(),
			array(array($this, 'addProductToFeed')),
			array('products' => &$productsData)
		);

		$feedPath = $this->prepareFeed($productsData, $storeId);

		// re-enable flat config
		Mage::app()->getStore($storeId)->setConfig($flatConfigPath, $flatConfig);

		Varien_Profiler::stop('productfeed_' . $this->getVendorCode() . '::' . __FUNCTION__);

		return $feedPath;
	}

	/**
	 * Publish a feed.
	 * 
	 * @param string $filepath
	 * @param int $storeId
	 * @return mixed
	 */
	public function publish ($filepath, $storeId)
	{
		$publisher = $this->getPublisher();
		if (!($publisher instanceof Grommet_ProductFeed_Model_Feed_Publisher_Interface)) {
			throw new Mage_Core_Exception('Invalid publisher model.');
		}
		return $publisher->publish($filepath, $this->getPublishParams($storeId));
	}
	
	/**
	 * Prepare the feed file and returns its path
	 * 
	 * @param array $productsData
	 * @param int $storeId
	 * @return string
	 */
	public abstract function prepareFeed (array $productsData, $storeId);

	/**
	 * Iterator callback to add a product to the feed.
	 *
	 * @param array $args
	 */
	public abstract function addProductToFeed ($args);

	/**
	 * Returns the vendor code.
	 * 
	 * @return string
	 */
	public abstract function getVendorCode ();

	/**
	 * Returns the publishing model.
	 *
	 * @return Grommet_ProductFeed_Model_Feed_Publisher_Interface
	 */
	public abstract function getPublisher ();

	/**
	 * Returns publishing parameters.
	 *
	 * @param int $storeId
	 * @return array
	 */
	public abstract function getPublishParams ($storeId);

	/**
	 * Prepare and return the product collection.
	 *
	 * @param int $storeId
	 * @return Mage_Catalog_Model_Resource_Product_Collection
	 * @todo Move grommet-specific code
	 */
	public function prepareProductCollection ($storeId)
	{
		$collection = Mage::getResourceModel('catalog/product_collection')
			->setStoreId($storeId)
			->addStoreFilter()
			->addMinimalPrice()
			->addFinalPrice()
			->addTaxPercents()
			->addUrlRewrite();
		/* @var $collection Mage_Catalog_Model_Resource_Product_Collection */

		Mage::getModel('cataloginventory/stock_status')->addStockStatusToSelect(
			$collection->getSelect(),
			Mage::app()->getStore($storeId)->getWebsite()
		);

		$attributes = $this->getProductAttributes();
		foreach ($attributes as $attribute) {
			$collection->addAttributeToSelect($attribute['map'], $attribute['required'] ? 'inner' : 'left');
		}

		// default attributes
		$defaultAttributes = array('price', 'price_view', 'price_type', 'tax_class_id');
		$defaultAttributes = array_diff($defaultAttributes, array_keys($attributes));
		foreach ($defaultAttributes as $attribute) {
			$collection->addAttributeToSelect($attribute, 'left');
		}
		
		// product filtering
		if ($this->getVendorConfig('filter_feed')) {
			$collection->addAttributeToFilter('available_feeds', array('like' => '%' . $this->getVendorCode() . '%'));
		}

		// default sorting
		$collection->addAttributeToSort('news_from_date', 'desc');

		return $collection;
	}

	/**
	 * Get a vendor-specific config field.
	 *
	 * @param string $field
	 * @param int $storeId
	 * @return mixed
	 */
	public function getVendorConfig ($field, $storeId = null)
	{
		return Mage::getStoreConfig('productfeed/' . $this->getVendorCode() . '/' . $field, $storeId);
	}

	/**
	 * Directory for feed storage.
	 *
	 * @return string
	 */
	protected function getFeedStorageDir ()
	{
		return Mage::getBaseDir('var') . DS . 'productfeed' . DS;
	}

	/**
	 * Public directory for feed storage.
	 *
	 * @return string
	 */
	protected function getPublicFeedStorageDir ()
	{
		return Mage::getBaseDir('media') . DS . 'productfeed' . DS;
	}

	/**
	 * Returns an array of attributes to add to the collection select.
	 *
	 * @return array
	 */
	protected function getProductAttributes ()
	{
		if ($this->attributeMap === null) {
			$this->attributeMap = array();
			$attributes = Mage::getConfig()->getNode('global/productfeed/' . $this->getVendorCode() . '/attributes')->asArray();
			foreach ($attributes as $attributeCode => $config) {
				$data = array('required' => isset($config['required']) ? true : false);
				if (!isset($config['map']) || $config['map'] == '*') {
					$data['map'] = $attributeCode;
				}
				else {
					$data['map'] = $config['map'];
				}
				$attribute = Mage::getSingleton('eav/config')->getAttribute(Mage_Catalog_Model_Product::ENTITY, $data['map']);
				if ($attribute->getId()) {
					$this->attributeMap[$attributeCode] = $data;
				}
			}						
		}
		return $this->attributeMap;
	}

	/**
	 * Returns the local alias of a given attribute.
	 *
	 * @param string $attribute
	 * @return string
	 */
	public function getAttributeMapping ($attribute)
	{
		$atts = $this->getProductAttributes();
		if (isset($atts[$attribute])) {
			return $atts[$attribute]['map'];
		}
		return $attribute;
	}

	/**
	 * Get image resize dimensions.
	 *
	 * @param string $dim
	 * @param int $storeId
	 * @return int
	 */
	public function getImageDimension ($dim, $storeId = null)
	{
		$px = $this->getVendorConfig('image_' . $dim, $storeId);
		if (!$px) {
			$px = ($dim == 'width' ? self::DEFAULT_IMAGE_WIDTH : self::DEFAULT_IMAGE_HEIGHT);
		}
		return $px;
	}

	/**
	 * Get a resized product image URL.
	 *
	 * @param Mage_Catalog_Model_Product $product
	 * @param string $imageAtt
	 * @param bool $placeholder
	 * @return string
	 */
	public function getImageUrl (Mage_Catalog_Model_Product $product, $imageAtt, $placeholder = true)
	{
		if (!$placeholder && (!$product->getData($imageAtt) || $product->getData($imageAtt) == 'no_selection')) {
			return '';
		}
		try {
			return Mage::helper('catalog/image')
				->init($product, $imageAtt)
				->resize($this->getImageDimension('width', $product->getStoreId()), $this->getImageDimension('height', $product->getStoreId()))
				->__toString();			
		} catch (Exception $e) {
			return '';
		}
	}
}
