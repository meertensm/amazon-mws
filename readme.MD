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
Methods available at this time:
- validateCredentials
- GetCompetitivePricingForASIN
- GetLowestPricedOffersForASIN
- GetMyPriceForSKU
- GetMyPriceForASIN
- GetLowestOfferListingsForASIN
- ListOrders
- GetOrder
- ListOrderItems
- GetProductCategoriesForSKU
- GetProductCategoriesForASIN
- GetMatchingProductForId
- GetReportList
- GetFeedSubmissionResult
- SubmitFeed
- RequestReport
- GetReport
- GetReportRequestStatus
- updateStock
- updatePrice
- ListMarketplaceParticipations