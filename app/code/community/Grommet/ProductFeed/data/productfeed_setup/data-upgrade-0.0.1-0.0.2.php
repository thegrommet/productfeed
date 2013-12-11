<?php
$installer = $this;
/* @var $installer Mage_Catalog_Model_Resource_Setup */

$installer->addAttributeGroup(
	Mage_Catalog_Model_Product::ENTITY,
	$installer->getAttributeSetId(Mage_Catalog_Model_Product::ENTITY, 'Default'),
	'Product Feeds',
	50
);

$installer->addAttribute(Mage_Catalog_Model_Product::ENTITY, 'available_feeds', array(
	'group'             => 'Product Feeds',
	'type'              => 'varchar',
	'backend'           => 'eav/entity_attribute_backend_array',
	'frontend'          => '',
	'label'             => 'Available In Product Feeds',
	'input'             => 'multiselect',
	'class'             => '',
	'source'            => 'productfeed/product_attribute_source_feeds',
	'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
	'visible'           => true,
	'required'          => false,
	'user_defined'      => false,
	'default'           => '',
	'searchable'        => false,
	'filterable'        => false,
	'comparable'        => false,
	'visible_on_front'  => false,
	'unique'            => false,
	'is_configurable'   => false
));

$installer->addAttribute(Mage_Catalog_Model_Product::ENTITY, 'rakuten_category_id', array(
	'group'             => 'Product Feeds',
	'type'              => 'varchar',
	'backend'           => '',
	'frontend'          => '',
	'label'             => 'Rakuten Category ID',
	'input'             => 'text',
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
	'is_configurable'   => false
));
