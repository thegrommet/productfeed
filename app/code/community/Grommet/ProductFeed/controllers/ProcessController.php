<?php
/**
 * Product Feed Process Controller
 *
 * @author tmannherz
 */
class Grommet_ProductFeed_ProcessController extends Mage_Core_Controller_Front_Action
{
	/**
	 * Ensure dev mode for manual feed generation.
	 */
	public function preDispatch ()
	{
		parent::preDispatch();
		if (!Mage::getIsDeveloperMode()) {
			$this->norouteAction();
			$this->setFlag('', 'no-dispatch', true);
		}
	}

	/**
	 * Manually generated the LinkShare feed.
	 */
	public function linkshareAction ()
	{
		try {
			$cron = Mage::getModel('productfeed/cron');
			$cron->processLinkShareFeed();
			$text = '<h1>Done</h1>';
		} catch (Exception $e) {
			$text = '<h1>' . $e->getMessage() . '</h1><br /><pre>' . $e->getTraceAsString() . '</pre>';
		}
		$this->_renderLayoutMessage($text);
	}

	/**
	 * Manually generated the LinkShare refund feed.
	 */
	public function linkshareRefundAction ()
	{
		try {
			$cron = Mage::getModel('productfeed/cron');
			$cron->processLinkShareRefunds();
			$text = '<h1>Done</h1>';
		} catch (Exception $e) {
			$text = '<h1>' . $e->getMessage() . '</h1><br /><pre>' . $e->getTraceAsString() . '</pre>';
		}
		$this->_renderLayoutMessage($text);
	}

	/**
	 * Manually generated the Rakuten inventory feed.
	 */
	public function rakutenInventoryAction ()
	{
		try {
			$cron = Mage::getModel('productfeed/cron');
			$cron->processRakutenInventoryFeed();
			$text = '<h1>Done</h1>';
		} catch (Exception $e) {
			$text = '<h1>' . $e->getMessage() . '</h1><br /><pre>' . $e->getTraceAsString() . '</pre>';
		}
		$this->_renderLayoutMessage($text);
	}

	/**
	 * Manually generated the Rakuten inventory feed.
	 */
	public function rakutenProductAction ()
	{
		try {
			$cron = Mage::getModel('productfeed/cron');
			$cron->processRakutenProductFeed();
			$text = '<h1>Done</h1>';
		} catch (Exception $e) {
			$text = '<h1>' . $e->getMessage() . '</h1><br /><pre>' . $e->getTraceAsString() . '</pre>';
		}
		$this->_renderLayoutMessage($text);
	}

	/**
	 * Manually generated the MediaForge inventory feed.
	 */
	public function mediaforgeAction ()
	{
		try {
			$cron = Mage::getModel('productfeed/cron');
			$cron->processMediaForgeFeed();
			$text = '<h1>Done</h1>';
		} catch (Exception $e) {
			$text = '<h1>' . $e->getMessage() . '</h1><br /><pre>' . $e->getTraceAsString() . '</pre>';
		}
		$this->_renderLayoutMessage($text);
	}

	/**
	 * Manually generated the Tid.al feed.
	 */
	public function tidalAction ()
	{
		try {
			$cron = Mage::getModel('productfeed/cron');
			$cron->processTidalFeed();
			$text = '<h1>Done</h1>';
		} catch (Exception $e) {
			$text = '<h1>' . $e->getMessage() . '</h1><br /><pre>' . $e->getTraceAsString() . '</pre>';
		}
		$this->_renderLayoutMessage($text);
	}

	/**
	 * @param string $message
	 * @return Grommet_ProductFeed_ProcessController
	 */
	protected function _renderLayoutMessage ($message)
	{
		$this->loadLayout();
		$block = $this->getLayout()->createBlock('core/text')->setText($message);
		$this->getLayout()->getBlock('content')->append($block);
		$this->renderLayout();
		return $this;
	}
}
