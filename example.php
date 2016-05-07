<?php

    require_once 'vendor/autoload.php';
    
    $client = new \MCS\MWSClient([
        'Marketplace_Id' => '',
        'Seller_Id' => '',
        'Access_Key_ID' => '',
        'Secret_Access_Key' => '',
    ]);

    // Get orders

    $fromDate = new DateTime('2016-01-01');
    
    $orders = $client->ListOrders();

    print_r($orders);
    
    // Update stock

    $result = $client->updateStock([
        'sku1' => 20,
        'sku2' => 9,
    ]);

    print_r($result);

   