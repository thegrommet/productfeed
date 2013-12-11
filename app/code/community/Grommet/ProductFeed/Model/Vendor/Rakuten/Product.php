<?php
/**
 * Rakuten product feed
 *
 * @author tmannherz
 */
class Grommet_ProductFeed_Model_Vendor_Rakuten_Product extends Grommet_ProductFeed_Model_Vendor_Ftp
{
	const CODE			= 'rakuten';
	const DELIMITER		= "\t";
	const SEPARATOR		= '|';
	
	/**
	 * @var array 
	 */
	protected $_fields = array(
		'seller-id',
		'gtin',
		'isbn',
		'mfg-name',
		'mfg-part-number',
		'asin',
		'seller-sku',
		'title',
		'description',
		'main-image',
		'additional-images',
		'weight',
		'features',
		'listing-price',
		'msrp',
		'category-id',
		'keywords',
		'product-set-id'
	);
	
	/**
	 * @var array 
	 */
	protected $_categoryFields = array();

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
		if (!$mId) {
			Mage::throwException(Mage::helper('productfeed')->__('Rakuten Seller ID must be set.'));
		}
		$filename = 'rakuten_product_' . Mage::getModel('core/date')->date('Ymd') . '.txt';
		$filepath = $this->getFeedStorageDir() . $filename;
		try {
			$ioAdapter = new Varien_Io_File();
			$ioAdapter->setAllowCreateFolders(true);
			$ioAdapter->createDestinationDir($this->getFeedStorageDir());
			$ioAdapter->cd($this->getFeedStorageDir());
			$ioAdapter->streamOpen($filename);
			$ioAdapter->streamWrite(implode(self::DELIMITER, $this->getHeaders()) . "\n");
			foreach ($productsData as $productId => $row) {
				array_unshift($row, $mId);
				$this->prepareRow($row, $productId);
				$ioAdapter->streamWrite(implode(self::DELIMITER, $row) . "\n");  // because a CSV enclosure is not supported
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

		// required atts
		$name = trim($product->getData($this->getAttributeMapping('name')));
		$shortDescription = trim(strip_tags($product->getData($this->getAttributeMapping('short_description'))));
		$categoryId = $product->getData($this->getAttributeMapping('rakuten_category_id'));

		if (!$finalPrice || !$name || !$shortDescription || !$categoryId) {
			return;
		}

		// images
		$imageFile = $product->getData($this->getAttributeMapping('image'));
		if ($imageFile && $imageFile != 'no_selection') {
			$mainImage = $this->getAttributeMapping('image');
		}
		else {
			$mainImage = $this->getAttributeMapping('image2');
		}
		$additionalImages = array();
		$product->loadMediaGallery();
		foreach ($product->getMediaGalleryImages(false) as $imageInfo) {
			$file = $imageInfo->getFile();
			if ($file != $product->getData($mainImage) && $file != $product->getData($this->getAttributeMapping('image_exclude'))) {
				$product->setAdditionalImage($file);
				try {
					$additionalImages[] = $this->getImageUrl($product, 'additional_image', false);
				} catch (Exception $e) {}			
			}
		}

		$row = array(
			'gtin' => (string)$product->getData($this->getAttributeMapping('upc')),
			'isbn' => (string)$product->getData($this->getAttributeMapping('isbn')),
			'mfg_name' => $product->getAttributeText($this->getAttributeMapping('manufacturer_name')),
			'mfg_part_number' => (string)$product->getData($this->getAttributeMapping('manufacturer_part_number')),
			'asin' => (string)$product->getData($this->getAttributeMapping('asin')),
			'merchant_sku' => (string)$product->getData($this->getAttributeMapping('sku')),
			'title' => $name,
			'description' => $shortDescription,
			'main_image' => $this->getImageUrl($product, $mainImage),
			'additional_images' => implode(self::SEPARATOR, $additionalImages),
			'weight' => round($product->getData($this->getAttributeMapping('weight')), 2),
			'features' => (string)$product->getData($this->getAttributeMapping('features')),
			'listing_price' => $finalPrice,
			'msrp' => (string)$product->getData($this->getAttributeMapping('msrp')),
			'category_id' => $categoryId,
			'keywords' => (string)$product->getData($this->getAttributeMapping('meta_keyword')),
			'product_set_id' => ''
		);

		// ability to overwrite stock fields with category fields
		$categoryFields = $this->_processCategoryFields($product->getData($this->getAttributeMapping('rakuten_category_fields')));
		foreach ($categoryFields as $field => $value) {
			if (isset($row[$field])) {
				$row[$field] = $value;
				unset($categoryFields[$field]);
			}
			else {
				$map = str_replace('-', '_', $field);  // so product-set-id and product_set_id are both accounted for
				if (isset($row[$map])) {
					$row[$map] = $value;
					unset($categoryFields[$field]);
				}
			}
		}
		$row['category_fields'] = $categoryFields;

		$this->sanitizeRow($row);

		$row = new Varien_Object($row);
		Mage::dispatchEvent('productfeed_addproducttofeed', array('vendor' => $this, 'row' => $row));
		
		$this->addCategoryFields($product->getId(), $row->getCategoryFields());
		$row->unsCategoryFields();

		$args['products'][$product->getId()] = $row->getData();
	}

	/**
	 * Removes deliniating and other illegal chars from each field. Formats other fields.
	 * 
	 * @param array $row
	 */
	protected function sanitizeRow (array &$row)
	{
		if (!empty($row['title']) && strlen($row['title']) > 100) {
			$row['title'] = substr($row['title'], 0, 97) . '...';
		}
		if (!empty($row['description']) && strlen($row['description']) > 8000) {
			$row['description'] = substr($row['description'], 0, 7997) . '...';
		}
		if (!empty($row['features']) && strlen($row['features'])) {
			$features = array();
			$split = preg_split('/[\n]/', strip_tags($row['features']));
			foreach ($split as $feature) {
				$feature = trim($feature);
				if (strlen($feature)) {
					if (strlen($feature) > 250) {
						$feature = substr($feature, 0, 247) . '...';
					}
					$features[] = $feature;					
				}
			}
			$row['features'] = implode(self::SEPARATOR, $features);
		}
		if (!empty($row['mfg_name'])) {
			$row['mfg_name'] = preg_replace('/[^A-Za-z0-9_\s]/', '', $row['mfg_name']);  // non-alpha numeric chars not supported
		}
		if (!empty($row['keywords']) && strlen($row['keywords'])) {
			$keywords = array();
			$split = preg_split('/[\n,]/', $row['keywords']);
			foreach ($split as $keyword) {
				$keyword = trim($keyword);
				if ((strlen(implode(self::SEPARATOR, $keywords)) + strlen($keyword)) > 250) {
					break;
				}
				if ($keyword && strlen($keyword) <= 40) {
					$keywords[] = $keyword;
				}
			}
			$row['keywords'] = implode(self::SEPARATOR, $keywords);
		}
		
		$searches = array('&nbsp;','á','à','â','ã','ª','ä','å','Á','À','Â','Ã','Ä','é','è','ê','ë','É','È','Ê','Ë','í','ì','î','ï','Í','Ì','Î','Ï','œ','ò','ó','ô','õ','º','ø','Ø','Ó','Ò','Ô','Õ','ú','ù','û','Ú','Ù','Û','ç','Ç','Ñ','ñ');
		$replacements = array(' ','a','a','a','a','a','a','a','A','A','A','A','A','e','e','e','e','E','E','E','E','i','i','i','i','I','I','I','I','oe','o','o','o','o','o','o','O','O','O','O','O','u','u','u','U','U','U','c','C','N','n');
		
		foreach ($row as &$field) {
			if (is_scalar($field)) {
				$field = str_replace($searches, $replacements, $field);
				$field = preg_replace('/[\t\n\r]/', '', $field);
			}
		}
	}

	/**
	 * Get a inventory-specific config field.
	 *
	 * @param string $field
	 * @param int $storeId
	 * @return mixed
	 */
	public function getProductConfig ($field, $storeId = null)
	{
		return Mage::getStoreConfig('productfeed/rakuten_product/' . $field, $storeId);
	}

	/**
	 * @param int $storeId
	 * @return array
	 */
	public function getPublishParams ($storeId = null)
	{
		$params = parent::getPublishParams($storeId);
		$params['path'] = $this->getProductConfig('ftp_remote_folder', $storeId);

		return $params;
	}
	
	/**
	 * Breaks up custom fields for product data.
	 * 
	 * @param string $fieldData
	 * @return array
	 */
	protected function _processCategoryFields ($fieldData)
	{
		$categoryFields = array();
		if ($fieldData) {
			$rows = explode("\n", $fieldData);
			foreach ($rows as $row) {
				if (strpos($row, self::SEPARATOR)) {
					list($field, $value) = explode(self::SEPARATOR, $row);
					$categoryFields[$field] = $value;
				}
			}
			$this->sanitizeRow($categoryFields);
		}
		return $categoryFields;
	}

	/**
	 * Add custom category fields to the feed.
	 *
	 * @param int $productId
	 * @param array $fields
	 * @return Grommet_ProductFeed_Model_Vendor_Rakuten_Product
	 */
	public function addCategoryFields ($productId, array $fields)
	{
		foreach ($fields as $field => $value) {
			if (!isset($this->_categoryFields[$field])) {
				$this->_categoryFields[$field] = array();
			}
			$this->_categoryFields[$field][$productId] = $value;
		}
		return $this;
	}

	/**
	 * Feed headers.
	 *
	 * @return array
	 */
	public function getHeaders ()
	{
		return array_merge($this->_fields, array_keys($this->_categoryFields));
	}

	/**
	 * Prepare the product row with custom category data.
	 *
	 * @param array $row
	 * @param int $productId
	 */
	public function prepareRow (array &$row, $productId)
	{
		foreach ($this->_categoryFields as $field => $values) {
			if (isset($values[$productId])) {
				$row[] = $values[$productId];
			}
			else {
				$row[] = '';
			}
		}
	}
}
