<?php
$installer = $this;
/* @var $installer Mage_Catalog_Model_Resource_Setup */

$installer->addAttribute(Mage_Catalog_Model_Product::ENTITY, 'rakuten_category_fields', array(
	'group'             => 'Product Feeds',
	'type'              => 'text',
	'backend'           => '',
	'frontend'          => '',
	'label'             => 'Rakuten Custom Category Fields',
	'input'             => 'textarea',
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
	'note'              => 'Separate each key-value pair with a new line. Separate each key-value with a |'
));
