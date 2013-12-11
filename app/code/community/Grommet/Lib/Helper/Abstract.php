<?php
/**
 * Abstract Helper class
 *
 * @author tmannherz
 */
abstract class Grommet_Lib_Helper_Abstract extends Mage_Core_Helper_Abstract
{
	/**
	 * @var int
	 */
	protected $_logLevelCutoff = Zend_Log::NOTICE;
	
	/**
	 * @var string
	 */
	protected $_defaultLogfile = 'helper.log';
		
	/**
	 * Logs a debug message to $logFile
	 *
	 * @param mixed $message
	 * @param string $logFile
	 * @param int $logLevel
	 */
	public function log ($message, $logFile = null, $logLevel = Zend_Log::NOTICE)
	{
		if ($logLevel > $this->_logLevelCutoff) {
			return;
		}
		if (!$logFile) {
			$logFile = $this->_defaultLogfile;
		}
		if (is_array($message)) {
			$message = print_r($message, 1);
		}
		else if ($message instanceof Varien_Object) {
			$message = get_class($message) . ': ' . print_r($message->debug(), 1);
		}
		else if ($message instanceof Exception) {
			$message = get_class($message) . ': ' . $message->getMessage() . "\n" . $message->getTraceAsString();
		}
		Mage::log($message, null, $logFile, true);
	}
}
