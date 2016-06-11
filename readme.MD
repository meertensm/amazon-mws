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
```
### Orders
```php
$fromDate = new DateTime('2016-01-01');
$orders = $client->ListOrders($fromDate);
foreach ($orders as $order) {
    $items = $client->ListOrderItems($order['AmazonOrderId']);
    print_r($order);
    print_r($items);
}
```
### Stock
```php
$result = $client->updateStock([
    'sku1' => 20,
    'sku2' => 9,
]);
print_r($result);
```
### Reports
For all report types, visit:  [http://docs.developer.amazonservices.com](http://docs.developer.amazonservices.com/en_US/reports/Reports_ReportType.html#ReportTypeCategories__ListingsReports)

```php
$reportId = $client->RequestReport('_GET_MERCHANT_LISTINGS_DATA_');
// Wait a couple of minutes and get it's content
$report_content = $client->GetReport($reportId);
print_r($report_content);
```