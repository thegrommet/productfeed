<?php
$installer = $this;
/* @var $installer Mage_Catalog_Model_Resource_Setup */

$installer->addAttribute(Mage_Catalog_Model_Product::ENTITY, 'rakuten_shipping_std', array(
	'group'             => 'Product Feeds',
	'type'              => 'decimal',
	'backend'           => 'catalog/product_attribute_backend_price',
	'frontend'          => '',
	'label'             => 'Rakuten Shipping - Standard',
	'input'             => 'price',
	'class'             => '',
	'source'            => '',
	'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
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
	'note'              => 'If not specified, the config default will be used.'
));
