Grommet ProductFeed
===========
Magento module for publishing product feeds to LinkShare, MediaForge and other advertisers.

Supported Publishers:

* LinkShare (FTP)
* MediaForge (FTP)
* Rakuten Marketplace (formerly Buy.com) (FTP)
* Tid.al (JSON)

Features
--------
On a configurable basis, product feeds are generated for each enabled publisher and are either uploaded via FTP to the publisher or made available in a public Magento directory for download.

*LinkShare*

* Cookie tracking for LinkShare-referred customers, with order success pixel rendering so LinkShare can be credited with a referral
* Order crediting exclusions by email and customer group
* Processing of refunded orders, to get reimbursed from LinkShare for your refunds

*Rakuten Marketplace*

* Separate product and inventory feeds

Installation
------------
Copy repository contents in a Magento root directory. Clear cache and navigate to _System -> Configuration -> Product Feeds_

Configuration
-------------
Navigate to _System -> Configuration -> Product Feeds_

* _Enable [publisher] Feed_: Enable or disable scheduled generation of the publisher's feed.
* _Filter Feed Based on Product Attribute_: To control which products are in a given feed, a new product attribute is added, labeled 'Available In Product Feeds'. If _Filter Feed Based on Product Attribute_ is set to Yes, a product will only appear in the feed if a given publisher is selected. If set to No, all eligible products will be included in the feed.
* _Cron Schedule_: Cron expression for feed generation. See http://en.wikipedia.org/wiki/Cron#CRON_expression
* _Feed Image Width_: Width for image URLs generated in the feed.
* _Feed Image Height_: Height for image URLs generated in the feed.

Customization
-------------
Due to the unique nature of each store's catalog, this is not a plug-n-play module. Instead, it requires modification to map attributes in each publisher's feed to those on your products.

For example, the LinkShare feed can accept a 'brand' attribute. In one store, the associated product attribute might be called 'manufacturer'. XML mapping are used to make these associations.

To start customizing the feed, first extend the ProductFeed module as `YourNamespace_ProductFeed`. See [this Module creation article](http://coding.smashingmagazine.com/2012/03/01/basics-creating-magento-module/) for more info.

See the `config.xml` file that comes with the module. Each publisher has an attribute list in Magento's config at xpath `global/productfeed/[publisher]`:

```xml
<productfeed>
	<linkshare translate="label" module="productfeed">
		<title>LinkShare</title>
		<attributes>
			<name><!-- feed attribute label -->
				<map>*</map> <!-- product attribute label. use '*' if it is the same as the feed attribute -->
				<required /> <!-- only products with this attribute will be included in the feed -->
			</name>
			<brand>
				<map>manufacturer</map> <!-- example where the feed attribute is different from the product attribute -->
			</brand>
		</attributes>
	</linkshare>
</productfeed>
```

*Test Feed Generation*

* LinkShare, product feed: http://www.yourstore.com/productfeed/process/linkshare
* LinkShare, refund feed: http://www.yourstore.com/productfeed/process/linkshareRefund
* MediaForge: http://www.yourstore.com/productfeed/process/mediaforge
* Rakuten Marketplace, product feed: http://www.yourstore.com/productfeed/process/rakutenProduct
* Rakuten Marketplace, inventory feed: http://www.yourstore.com/productfeed/process/rakutenInventory
* Tid.al: http://www.yourstore.com/productfeed/process/tidal

Feed files are stored in [magento_root]/var/productfeed before being uploaded or published. Feed files older than 14 days are automatically cleaned up.

Magento Developer mode must be enabled for manual feed file generation.

Requirements
------------
* Magento 1.6+
* PHP 5.3+
