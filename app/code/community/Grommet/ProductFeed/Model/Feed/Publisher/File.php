<?php
/**
 * File Product Feed Publisher Adapter
 *
 * @author tmannherz
 */
class Grommet_ProductFeed_Model_Feed_Publisher_File
	implements Grommet_ProductFeed_Model_Feed_Publisher_Interface
{
	/**
	 * Publish the specified feed file.
	 *
	 * @param string $filepath
	 * @param array $params
	 * @return Grommet_ProductFeed_Model_Feed_Publisher_File
	 */
	public function publish ($filepath, array $params = array())
	{
		if (empty($params['destination_path'])) {
			throw new Mage_Core_Exception('Invalid parameters - destination_path must be set.');
		}

		$ioAdapter = new Varien_Io_File();
		$ioAdapter->setAllowCreateFolders(true);
		$ioAdapter->createDestinationDir(pathinfo($params['destination_path'], PATHINFO_DIRNAME));
		$writeResult = $ioAdapter->cp($filepath, $params['destination_path']);
		if (!$writeResult) {
			throw new Mage_Core_Exception('Unable to write file ' . $params['destination_path'] . ' to FTP.');
		}
		return $this;
	}
}