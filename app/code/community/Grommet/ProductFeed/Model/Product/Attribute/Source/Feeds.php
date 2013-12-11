<?php
/**
 * Available Feeds product attribute source
 *
 * @author tmannherz
 */
class Grommet_ProductFeed_Model_Product_Attribute_Source_Feeds extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{
	/**
	 * Retrieve Full Option values array
	 *
	 * @param bool $withEmpty Add empty option to array
	 * @return array
	 */
	public function getAllOptions ($withEmpty = true)
	{
		if ($this->_options === null) {
			$this->_options = array();
			foreach (Mage::getConfig()->getNode('global/productfeed')->children() as $vendor) {
				$this->_options[] = array(
					'value' => $vendor->getName(),
					'label' => (string)$vendor->title
				);
			}
		}
		if ($withEmpty) {
			$opts = $this->_options;
			array_unshift($opts, array('label' => '', 'value' => ''));
			return $opts;
		}
		return $this->_options;
	}

	/**
	 * Retrieve flat column definition
	 *
	 * @return array
	 */
	public function getFlatColums ()
	{
        $attributeCode = $this->getAttribute()->getAttributeCode();
        $column = array(
            'is_null'   => true,
            'default'   => null,
            'extra'     => null
        );

        if (Mage::helper('core')->useDbCompatibleMode()) {
            $column['type']     = 'varchar(255)';
            $column['is_null']  = true;
        } else {
            $column['type']     = Varien_Db_Ddl_Table::TYPE_TEXT;
            $column['length']   = 255;
            $column['nullable'] = true;
            $column['comment']  = $attributeCode . ' column';
        }

        return array($attributeCode => $column);
	}

	/**
	 * Retrieve Select For Flat Attribute update
	 *
	 * @param Mage_Catalog_Model_Resource_Eav_Attribute $attribute
	 * @param int $store
	 * @return Varien_Db_Select|null
	 */
	public function getFlatUpdateSelect ($store)
	{
		return Mage::getResourceSingleton('eav/entity_attribute')
				->getFlatUpdateSelect($this->getAttribute(), $store);
	}
}
