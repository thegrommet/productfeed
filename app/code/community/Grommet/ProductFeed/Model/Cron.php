<?php
/**
 * Product Feed Cron model
 *
 * @author tmannherz
 */
class Grommet_ProductFeed_Model_Cron
{
	/**
	 * Process the LinkShare feed.
	 */
	public function processLinkShareFeed ()
	{
		$this->_processFeed('linkShare');
	}

	/**
	 * Process the MediaForge feed.
	 */
	public function processMediaForgeFeed ()
	{
		$this->_processFeed('mediaForge');
	}
	
    /**
	 * Process the Tid.al feed.
	 */
	public function processTidalFeed ()
	{
		$this->_processFeed('tidal');
	}

	/**
	 * Process the Rakuten Inventory feed.
	 */
	public function processRakutenInventoryFeed ()
	{
		$this->_processFeed('rakuten_inventory');
	}

	/**
	 * Process the Rakuten Product feed.
	 */
	public function processRakutenProductFeed ()
	{
		$this->_processFeed('rakuten_product');
	}

	/**
	 * Process a given feed.
	 *
	 * @param string $vendor
	 */
	protected function _processFeed ($vendor)
	{
		$helper = Mage::helper('productfeed');
		$feed = Mage::getModel('productfeed/vendor_' . $vendor);
		/* @var $feed Grommet_ProductFeed_Model_Vendor_Abstract */
		foreach (Mage::app()->getStores() as $store) {
			try {
				if ($this->isEnabled($vendor, $store->getId())) {
					$helper->log(sprintf(
						'Processing feed for vendor %s / store %s',
						$vendor,
						$store->getCode()
					));
					$filepath = $feed->generate($store->getId());
					$feed->publish($filepath, $store->getId());
				}
				else {
					$helper->log(sprintf(
						'Skipping feed process for vendor %s / store %s',
						$vendor,
						$store->getCode()
					));
				}
			} catch (Exception $e) {
				$helper->log(sprintf(
					'Error processing feed for vendor %s / store %s',
					$vendor,
					$store->getCode()
				));
				$helper->log($e);
			}			
		}
	}

	/**
	 * Submit refunds back to LinkShare for reimbursement.
	 */
	public function processLinkShareRefunds ()
	{
		foreach (Mage::app()->getStores() as $store) {
			if ($this->isEnabled('linkshare', $store->getId())) {
				try {
					Mage::getModel('productfeed/vendor_linkShare_refund')->processDailyRefunds($store->getId());
				} catch (Exception $e) {
					Mage::helper('productfeed')->log($e);
				}
			}			
		}
	}

	/**
	 * Remove old feed files.
	 */
	public function cleanup ()
	{
		try {
			$ts = time() - 60 * 60 * 24 * 14;
			$iterator = new DirectoryIterator(Mage::getBaseDir('var') . DS . 'productfeed');
			foreach ($iterator as $file) {
				if ($file->isFile() && $file->getMTime() < $ts) {
					unlink($file->getPathname());
				}
			}
		} catch (Exception $e) {
			Mage::helper('productfeed')->log($e);
		}
	}

	/**
	 * @param string $vendor
	 * @param int $storeId
	 * @return bool
	 */
	public function isEnabled ($vendor, $storeId)
	{
		return Mage::helper('productfeed')->isFeedEnabled($vendor, $storeId);
	}
}
