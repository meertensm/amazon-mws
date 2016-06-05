# Amazon Marketplace Webservices
[![Latest Stable Version](https://poser.pugx.org/mcs/amazon-mws/v/stable)](https://packagist.org/packages/mcs/amazon-mws) [![Latest Unstable Version](https://poser.pugx.org/mcs/amazon-mws/v/unstable)](https://packagist.org/packages/mcs/amazon-mws) [![License](https://poser.pugx.org/mcs/amazon-mws/license)](https://packagist.org/packages/mcs/amazon-mws)

Installation:
```bash
$ composer require mcs/amazon-mws
```
Interaction with the Amazon Api for vendors called MWS

```php
require_once 'vendor/autoload.php';

$client = new MCS\MWSClient([
    'Marketplace_Id' => '',
    'Seller_Id' => '',
    'Access_Key_ID' => '',
    'Secret_Access_Key' => '',
]);

// Get orders
$fromDate = new DateTime('2016-01-01');
$orders = $client->ListOrders($fromDate);

print_r($orders);

// Update stock
$result = $client->updateStock([
    'sku1' => 20,
    'sku2' => 9,
]);

print_r($result);
```