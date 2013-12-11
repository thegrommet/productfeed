<?php
/**
 * LinkShare Session
 *
 * @author tmannherz
 */
class Grommet_ProductFeed_Model_Vendor_LinkShare_Cookie
{
	const COOKIE_NAME = 'linkshare_referral';
	const DEFAULT_LIFETIME = 15552000;  // 6 months

	/**
	 * Set the cookie
	 * 
	 * @param string $cookieData
	 * @return Grommet_ProductFeed_Model_Vendor_LinkShare_Cookie
	 */
	public function init ($cookieData)
	{
		Mage::getSingleton('core/cookie')->set(
			self::COOKIE_NAME,
			$cookieData,
			$this->getCookieLifetime()
		);
		return $this;
	}

	/**
	 * Is cookie data set?
	 *
	 * @return bool
	 */
	public function hasData ()
	{
		$data = Mage::getSingleton('core/cookie')->get(self::COOKIE_NAME);
		return (bool)$data;
	}

	/**
	 * Clear the LinkShare cookie.
	 * 
	 * @return Grommet_ProductFeed_Model_Vendor_LinkShare_Cookie
	 */
	public function delete ()
	{
		Mage::getSingleton('core/cookie')->delete(self::COOKIE_NAME);
		return $this;
	}

	/**
	 * @return int
	 */
	public function getCookieLifetime ()
	{
		$lifetime = Mage::getStoreConfig('productfeed/linkshare/cookie_lifetime');
		if ($lifetime == '' || is_null($lifetime)) {
			$lifetime = self::DEFAULT_LIFETIME;
		}
		return $lifetime;
	}
}
