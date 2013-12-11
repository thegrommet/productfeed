<?php
/**
 * Interface Interface
 *
 * @author tmannherz
 */
interface Grommet_ProductFeed_Model_Feed_Publisher_Interface
{
	/**
	 * Publish the specified feed file.
	 * 
	 * @param string $filepath
	 * @param array $params
	 * @return Grommet_ProductFeed_Model_Feed_Publisher_Interface
	 */
	public function publish ($filepath, array $params = array());
}
