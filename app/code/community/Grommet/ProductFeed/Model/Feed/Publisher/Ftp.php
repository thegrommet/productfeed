<?php
/**
 * FTP Product Feed Publisher Adapter
 *
 * @author tmannherz
 */
class Grommet_ProductFeed_Model_Feed_Publisher_Ftp
	implements Grommet_ProductFeed_Model_Feed_Publisher_Interface
{
	/**
	 * Publish the specified feed file.
	 *
	 * @param string $filepath
	 * @param array $params
	 * @return Grommet_ProductFeed_Model_Feed_Publisher_Ftp
	 */
	public function publish ($filepath, array $params = array())
	{
		if (empty($params['host']) || empty($params['user']) || empty($params['password'])) {
			throw new Mage_Core_Exception('Invalid FTP parameters - host, user and password must be set.');
		}
		$ftp = new Varien_Io_Ftp();
		$ftp->open($params);

		$filename = pathinfo($filepath, PATHINFO_BASENAME);
		$writeResult = $ftp->write($filename, $filepath);
		if (!$writeResult) {
			throw new Mage_Core_Exception('Unable to write file ' . $filename . ' to FTP.');
		}
		return $this;
	}
}
