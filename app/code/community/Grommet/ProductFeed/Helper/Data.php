<?php
/**
 * Data
 *
 * @author tmannherz
 */
class Grommet_ProductFeed_Helper_Data extends Grommet_Lib_Helper_Abstract
{
	/**
	 * @var string
	 */
	protected $_defaultLogfile = 'productfeed.log';

	/**
	 * Is a given vendor feed enabled?
	 *
	 * @param string $vendor
	 * @param int $storeId
	 * @return bool
	 */
	public function isFeedEnabled ($vendor, $storeId = null)
	{
		return Mage::getStoreConfigFlag('productfeed/' . strtolower($vendor) . '/enabled', $storeId);
	}
}
