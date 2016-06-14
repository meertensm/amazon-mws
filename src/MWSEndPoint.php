<?php 
namespace MCS;

use Exception;

class MWSEndPoint{
    
    public static $endpoints = [
        'GetProductCategoriesForSKU' => [
            'method' => 'POST',
            'action' => 'GetProductCategoriesForSKU',
            'path' => '/Products/2011-10-01',
            'date' => '2011-10-01'
        ],
        'GetProductCategoriesForASIN' => [
            'method' => 'POST',
            'action' => 'GetProductCategoriesForASIN',
            'path' => '/Products/2011-10-01',
            'date' => '2011-10-01'
        ],
        'GetFeedSubmissionResult' => [
            'method' => 'POST',
            'action' => 'GetFeedSubmissionResult',
            'path' => '/',
            'date' => '2009-01-01'
        ],
        'GetReportList' => [
            'method' => 'POST',
            'action' => 'GetReportList',
            'path' => '/',
            'date' => '2009-01-01'
        ],
        'GetReportRequestList' => [
            'method' => 'POST',
            'action' => 'GetReportRequestList',
            'path' => '/',
            'date' => '2009-01-01'
        ],
        'GetReport' => [
            'method' => 'POST',
            'action' => 'GetReport',
            'path' => '/',
            'date' => '2009-01-01'
        ],
        'RequestReport' => [
            'method' => 'POST',
            'action' => 'RequestReport',
            'path' => '/',
            'date' => '2009-01-01'
        ],
        'ListOrders' => [
            'method' => 'POST',
            'action' => 'ListOrders',
            'path' => '/Orders/2013-09-01',
            'date' => '2013-09-01'
        ],
        'ListOrderItems' => [
            'method' => 'POST',
            'action' => 'ListOrderItems',
            'path' => '/Orders/2013-09-01',
            'date' => '2013-09-01'
        ],
        'GetOrder' => [
            'method' => 'POST',
            'action' => 'GetOrder',
            'path' => '/Orders/2013-09-01',
            'date' => '2013-09-01'
        ],
        'SubmitFeed' => [
            'method' => 'POST',
            'action' => 'SubmitFeed',
            'path' => '/',
            'date' => '2009-01-01'
        ],
        'GetMatchingProductForId' => [
            'method' => 'POST',
            'action' => 'GetMatchingProductForId',
            'path' => '/Products/2011-10-01',
            'date' => '2011-10-01'
        ],
        'GetCompetitivePricingForASIN' => [
            'method' => 'POST',
            'action' => 'GetCompetitivePricingForASIN',
            'path' => '/Products/2011-10-01',
            'date' => '2011-10-01'
        ],
        'GetLowestOfferListingsForASIN' => [
            'method' => 'POST',
            'action' => 'GetLowestOfferListingsForASIN',
            'path' => '/Products/2011-10-01',
            'date' => '2011-10-01'
        ],
        'GetLowestPricedOffersForASIN' => [
            'method' => 'POST',
            'action' => 'GetLowestPricedOffersForASIN',
            'path' => '/Products/2011-10-01',
            'date' => '2011-10-01'
        ]
    ];
    
    public static function get($key)
    {
        if (isset(self::$endpoints[$key])) {
            return self::$endpoints[$key];    
        } else {
            throw new Exception('Call to undefined endpoint ' . $key);    
        }
    }
}
