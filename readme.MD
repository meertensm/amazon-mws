### Change your composer.json file 

```
"require": {
    // ...
    "mcs/amazon-mws": "*",
    // ...
},
"repositories": [
    {
        "name": "mcs/amazon-mws",
        "type": "git",
        "url": "https://github.com/lrnzfrr/amazon-mws.git"
    }
],
```

In this fork i added $queryStartDateTime in ListInventorySupply

```php
// Get a list's inventory for Amazon's fulfillment
$client->ListInventorySupply($sku_array = []);
// ..or
$queryStartDateTime = date('c',$time);
$client->ListInventorySupply([],$queryStartDateTime]);
```
