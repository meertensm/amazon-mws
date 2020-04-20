# Amazon Marketplace Webservices

In this fork i added $queryStartDateTime in ListInventorySupply

// Get a list's inventory for Amazon's fulfillment
$client->ListInventorySupply($sku_array = []);
// ..or
$queryStartDateTime = date('c',$time);
$client->ListInventorySupply([],$queryStartDateTime]);

