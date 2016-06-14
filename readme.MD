# Amazon Marketplace Webservices
[![Latest Stable Version](https://poser.pugx.org/mcs/amazon-mws/v/stable)](https://packagist.org/packages/mcs/amazon-mws) [![Latest Unstable Version](https://poser.pugx.org/mcs/amazon-mws/v/unstable)](https://packagist.org/packages/mcs/amazon-mws) [![License](https://poser.pugx.org/mcs/amazon-mws/license)](https://packagist.org/packages/mcs/amazon-mws)

Interaction with the Amazon Api for vendors called MWS

### Installation:
```bash
$ composer require mcs/amazon-mws
```
### Initiate the client
```php
require_once 'vendor/autoload.php';

$client = new MCS\MWSClient([
    'Marketplace_Id' => '',
    'Seller_Id' => '',
    'Access_Key_ID' => '',
    'Secret_Access_Key' => '',
]);

// Optionally check if the supplied credentials are valid
if ($client->validateCredentials()) {
    // Credentials are valid
} else {
    // Credentials are not valid
}
```
### Get orders
```php
$fromDate = new DateTime('2016-01-01');
$orders = $client->ListOrders($fromDate);
foreach ($orders as $order) {
    $items = $client->ListOrderItems($order['AmazonOrderId']);
    print_r($order);
    print_r($items);
}
```
### Get product attributes
```php
$searchField = 'ASIN'; // Can be GCID, SellerSKU, UPC, EAN, ISBN, or JAN

$result = $client->GetMatchingProductForId([
    '<ASIN1>', '<ASIN2>', '<ASIN3>'
], $searchField);

print_r($result);
```
### Update product stock
```php
$result = $client->updateStock([
    'sku1' => 20,
    'sku2' => 9,
]);
print_r($result);

$info = $client->GetFeedSubmissionResult($result['FeedSubmissionId']);
print_r($info);
```
### Update product pricing
```php
$result = $client->updatePrice([
    'sku1' => '20.99',
    'sku2' => '100.00',
]);
print_r($result);

$info = $client->GetFeedSubmissionResult($result['FeedSubmissionId']);
print_r($info);
```
### Reports
For all report types, visit:  [http://docs.developer.amazonservices.com](http://docs.developer.amazonservices.com/en_US/reports/Reports_ReportType.html#ReportTypeCategories__ListingsReports)

```php
$reportId = $client->RequestReport('_GET_MERCHANT_LISTINGS_DATA_');
// Wait a couple of minutes and get it's content
$report_content = $client->GetReport($reportId);
print_r($report_content);
```
### Available methods:
Generated, view source for detailed argument description.

```php
// Returns the current competitive price of a product, based on ASIN.
$client->GetCompetitivePricingForASIN($asin_array);

// Get a feed's submission status
$client->GetFeedSubmissionResult($FeedSubmissionId);

// Returns pricing information for the lowest-price active offer listings for up to 20 products, based on ASIN.
$client->GetLowestOfferListingsForASIN($asin_array, $ItemCondition);

// Returns lowest priced offers for a single product, based on ASIN.
$client->GetLowestPricedOffersForASIN($asin, $ItemCondition);

// Returns a list of products and their attributes, based on a list of ASIN, GCID, SellerSKU, UPC, EAN, ISBN, and JAN values.
$client->GetMatchingProductForId($list, $type);

// Returns pricing information for your own offer listings, based on ASIN.
$client->GetMyPriceForASIN($asin_array, $ItemCondition);

// Returns pricing information for your own offer listings, based on SKU.
$client->GetMyPriceForSKU($sku_array, $ItemCondition);

// Returns an order based on the AmazonOrderId values that you specify.
$client->GetOrder($AmazonOrderId);

// Returns the parent product categories that a product belongs to, based on ASIN.
$client->GetProductCategoriesForASIN($ASIN);

// Returns the parent product categories that a product belongs to, based on SellerSKU.
$client->GetProductCategoriesForSKU($SellerSKU);

// Get a report's content
$client->GetReport($ReportId);

// Returns a list of reports that were created in the previous 90 days.
$client->GetReportList();

// Get a report's processing status
$client->GetReportRequestStatus($ReportId);

// Returns a list of marketplaces that the seller submitting the request can sell in, and a list of participations that include seller-specific information in that marketplace
$client->ListMarketplaceParticipations();

// Returns order items based on the AmazonOrderId that you specify.
$client->ListOrderItems($AmazonOrderId);

// Returns orders created or updated during a time frame that you specify.
$client->ListOrders($from);

// Request a report
$client->RequestReport($report, $StartDate, $EndDate);

// Submit a feed to MWS.
$client->SubmitFeed($FeedType, $feedContent, $debug);

// Update a product's price
$client->updatePrice($array);

// Update a product's stock quantity
$client->updateStock($array);

// A method to quickly check if the supplied credentials are valid
$client->validateCredentials();
```