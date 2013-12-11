<?php
/**
 * FTP publishing vendor
 *
 * @author tmannherz
 */
abstract class Grommet_ProductFeed_Model_Vendor_Ftp extends Grommet_ProductFeed_Model_Vendor_Abstract
{
	/**
	 * Returns the publishing model.
	 *
	 * @return Grommet_ProductFeed_Model_Feed_Publisher_Ftp
	 */
	public function getPublisher ()
	{
		return Mage::getModel('productfeed/feed_publisher_ftp');
	}

	/**
	 * Returns publishing parameters.
	 *
	 * @param int $storeId
	 * @return array
	 */
	public function getPublishParams ($storeId = null)
	{
		return array(
			'host' => $this->getVendorConfig('ftp_host', $storeId),
			'user' => $this->getVendorConfig('ftp_username', $storeId),
			'password' => $this->getVendorConfig('ftp_password', $storeId),
			'path' => $this->getVendorConfig('ftp_remote_folder', $storeId)
		);
	}
}
