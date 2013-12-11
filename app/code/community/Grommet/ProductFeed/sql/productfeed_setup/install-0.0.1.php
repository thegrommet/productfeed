<?php
/* @var $this Mage_Catalog_Model_Resource_Setup */

$this->getConnection()->addColumn($this->getTable('sales/order'), 'external_source', 'varchar(255)');
