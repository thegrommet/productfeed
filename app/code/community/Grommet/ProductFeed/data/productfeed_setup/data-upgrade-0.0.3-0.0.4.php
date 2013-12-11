<?php
$installer = $this;
/* @var $installer Mage_Catalog_Model_Resource_Setup */

$installer->addAttribute(Mage_Catalog_Model_Product::ENTITY, 'rakuten_feed_price', array(
	'group'             => 'Product Feeds',
	'type'              => 'decimal',
	'backend'           => 'catalog/product_attribute_backend_price',
	'frontend'          => '',
	'label'             => 'Rakuten Feed Custom Price',
	'input'             => 'price',
	'class'             => '',
	'source'            => '',
	'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE,
	'visible'           => true,
	'required'          => false,
	'user_defined'      => false,
	'default'           => '',
	'searchable'        => false,
	'filterable'        => false,
	'comparable'        => false,
	'visible_on_front'  => false,
	'unique'            => false,
	'is_configurable'   => false,
	'note'              => 'If not specified, normal Magento price determination will be used.'
));
