<?php namespace MCS;

use DateTime;
use Exception;
use DateTimeZone;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use Spatie\ArrayToXml\ArrayToXml;

class MWSClient{
    
    const SIGNATURE_METHOD = 'HmacSHA256';
    const SIGNATURE_VERSION = '2';
    const DATE_FORMAT = "Y-m-d\TH:i:s.\\0\\0\\0\\Z";
    const APPLICATION_NAME = 'MCS/MwsClient';
    
    private $config = [
        'Seller_Id' => null,
        'Marketplace_Id' => null,
        'Access_Key_ID' => null,
        'Secret_Access_Key' => null,
        'Application_Version' => '0.0.*'
    ];  
    
    private $MarketplaceIds = [
        'A2EUQ1WTGCTBG2' => 'mws.amazonservices.ca',
        'ATVPDKIKX0DER'  => 'mws.amazonservices.com',
        'A1AM78C64UM0Y8' => 'mws.amazonservices.com.mx',
        'A1PA6795UKMFR9' => 'mws-eu.amazonservices.com',
        'A1RKKUPIHCS9HS' => 'mws-eu.amazonservices.com',
        'A13V1IB3VIYZZH' => 'mws-eu.amazonservices.com',
        'A21TJRUUN4KGV'  => 'mws.amazonservices.in',
        'APJ6JRA9NG5V4'  => 'mws-eu.amazonservices.com',
        'A1F83G8C2ARO7P' => 'mws-eu.amazonservices.com',
        'A1VC38T7YXB528' => 'mws.amazonservices.jp',
        'AAHKV2X7AFYLW'  => 'mws.amazonservices.com.cn',
    ];
    
    private $endPoints = [
        'GetReportList' => [
            'method' => 'POST',
            'action' => 'GetReportList',
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
    
    public function __construct(array $config)
    {   
        foreach($config as $key => $value) {
            $this->config[$key] = $value;
        }
        
        foreach($this->config as $key => $value) {
            if(is_null($value)) {
                throw new Exception('Required field ' . $key . ' is not set');    
            }
        } 
        
        if (!isset($this->MarketplaceIds[$this->config['Marketplace_Id']])) {
            throw new Exception('Invalid Marketplace Id');    
        }
        
        $this->config['Application_Name'] = self::APPLICATION_NAME;
        $this->config['Region_Host'] = $this->MarketplaceIds[$this->config['Marketplace_Id']];
        $this->config['Region_Url'] = 'https://' . $this->config['Region_Host'];
        
    }
    
    private function xmlToArray($xmlstring)
    {
        return json_decode(json_encode(simplexml_load_string($xmlstring)), true);
    }
    
    public function GetCompetitivePricingForASIN($asin_array = [])
    {
        if (count($asin_array) > 20) {
            throw new Exception('Maximum amount of ASIN\'s for this call is 20');    
        }
        
        $counter = 1;
        $query = [
            'MarketplaceId' => $this->config['Marketplace_Id']
        ];
        
        foreach($asin_array as $key){
            $query['ASINList.ASIN.' . $counter] = $key; 
            $counter++;
        }
        
        $response = $this->request($this->endPoints['GetCompetitivePricingForASIN'], $query);
        
        if (isset($response['GetCompetitivePricingForASINResult'])) {
            $response = $response['GetCompetitivePricingForASINResult'];
            if (array_keys($response) !== range(0, count($response) - 1)) {
                $response = [$response];
            }
        } else {
            return [];    
        }
        
        $array = [];
        foreach ($response as $product) {
            $array[$product['Product']['Identifiers']['MarketplaceASIN']['ASIN']] = $product['Product']['CompetitivePricing']['CompetitivePrices']['CompetitivePrice']['Price'];
        }
        return $array;
        
    }
    
    public function GetLowestPricedOffersForASIN($asin, $ItemCondition = 'New')
    {
        
        $query = [
            'ASIN' => $asin,
            'MarketplaceId' => $this->config['Marketplace_Id'],
            'ItemCondition' => $ItemCondition
        ];
        
        return $this->request($this->endPoints['GetLowestPricedOffersForASIN'], $query);
        
    }
    
    /**
     * GetLowestOfferListingsForASIN
     * @param  array [$asin_array = []] array of ASIN values
     * @param  array [$ItemCondition = null] New, Used, Collectible, Refurbished, Club, default: All
     * @return array 
     */
    public function GetLowestOfferListingsForASIN($asin_array = [], $ItemCondition = null)
    {
        if (count($asin_array) > 20) {
            throw new Exception('Maximum amount of ASIN\'s for this call is 20');    
        }
        
        $counter = 1;
        $query = [
            'MarketplaceId' => $this->config['Marketplace_Id']
        ];
        
        if (!is_null($ItemCondition)) {
            $query['ItemCondition'] = $ItemCondition;
        }
        
        foreach($asin_array as $key){
            $query['ASINList.ASIN.' . $counter] = $key; 
            $counter++;
        }
        
        $response = $this->request($this->endPoints['GetLowestOfferListingsForASIN'], $query);
        
        if (isset($response['GetLowestOfferListingsForASINResult'])) {
            $response = $response['GetLowestOfferListingsForASINResult'];
            if (array_keys($response) !== range(0, count($response) - 1)) {
                $response = [$response];
            }
        } else {
            return [];    
        }
        
        $array = [];
        foreach ($response as $product) {
            $array[$product['Product']['Identifiers']['MarketplaceASIN']['ASIN']] = $product['Product']['LowestOfferListings']['LowestOfferListing'];
        }
        return $array;
        
    }
    
    public function listOrders(DateTime $from)
    {
        $query = [
            'CreatedAfter' => gmdate(self::DATE_FORMAT, $from->getTimestamp()),
            'OrderStatus.Status.1' => 'Unshipped',
            'OrderStatus.Status.2' => 'PartiallyShipped',
            'FulfillmentChannel.Channel.1' => 'MFN'
        ];
        
        $response = $this->request($this->endPoints['ListOrders'], $query);
        
        if( isset($response['ListOrdersResult']['Orders']['Order'])) {
            $response = $response['ListOrdersResult']['Orders']['Order'];
            if (array_keys($response) !== range(0, count($response) - 1)) {
                return [$response];
            }
            return $response;
        } else {
            return [];    
        }   
    }
    
    public function GetOrder($id)
    { 
        $response = $this->request($this->endPoints['GetOrder'], [
            'AmazonOrderId.Id.1' => $id
        ]); 
        
        if (isset($response['GetOrderResult']['Orders']['Order'])) {
            return $response['GetOrderResult']['Orders']['Order'];
        }else {
            return false;    
        }
    }
    
    public function GetMatchingProductForId(array $asin_array, $type = 'ASIN')
    { 
        $asin_array = array_unique($asin_array);
        
        if(count($asin_array) > 5) {
            throw new Exception('Maximum number of id\'s = 5');    
        }
        
        $counter = 1;
        $array = [
            'MarketplaceId' => $this->config['Marketplace_Id'],
            'IdType' => $type
        ];
        
        foreach($asin_array as $key){
            $array['IdList.Id.' . $counter] = $key; 
            $counter++;
        }
        
        $response = $this->request($this->endPoints['GetMatchingProductForId'], $array, null, true); 
        
        $languages = [
            'de-DE', 'en-EN', 'es-ES', 'fr-FR', 'it-IT', 'en-US'
        ];
        
        $replace = [
            '</ns2:ItemAttributes>' => '</ItemAttributes>'
        ];
        
        foreach($languages as $language) {
            $replace['<ns2:ItemAttributes xml:lang="' . $language . '">'] = '<ItemAttributes><Language>' . $language . '</Language>';
        }
        
        $replace['ns2:'] = '';
        
        $response = $this->xmlToArray(strtr($response, $replace));
        
        if (isset($response['GetMatchingProductForIdResult']['@attributes'])) {
            $response['GetMatchingProductForIdResult'] = [
                0 => $response['GetMatchingProductForIdResult']
            ];    
        }
    
        $found = [];
        $not_found = [];
        
        if (isset($response['GetMatchingProductForIdResult']) && is_array($response['GetMatchingProductForIdResult'])) {
            $array = [];
            foreach ($response['GetMatchingProductForIdResult'] as $product) {
                $asin = $product['@attributes']['Id'];
                if ($product['@attributes']['status'] != 'Success') {
                    $not_found[] = $asin;    
                } else {
                    $array = [];
                    if (!isset($product['Products']['Product']['AttributeSets'])) {
                        $product['Products']['Product'] = $product['Products']['Product'][0];    
                    }
                    foreach ($product['Products']['Product']['AttributeSets']['ItemAttributes'] as $key => $value) {
                        if (is_string($key) && is_string($value)) {
                            $array[$key] = $value;    
                        }
                    }
                    if (isset($product['Products']['Product']['AttributeSets']['ItemAttributes']['SmallImage'])) {
                        $image = $product['Products']['Product']['AttributeSets']['ItemAttributes']['SmallImage']['URL'];
                        $array['medium_image'] = $image;
                        $array['small_image'] = str_replace('._SL75_', '._SL50_', $image);
                        $array['large_image'] = str_replace('._SL75_', '', $image);;
                    }
                    $found[$asin] = $array;
                }
            }
        }
        
        return [
            'found' => $found,
            'not_found' => $not_found
        ];
    
    }
    
    public function GetReportList()
    {
        return $this->request($this->endPoints['GetReportList']);   
    }
    
    public function ListOrderItems($id)
    {
        $response = $this->request($this->endPoints['ListOrderItems'], [
            'AmazonOrderId' => $id
        ]);
        
        return array_values($response['ListOrderItemsResult']['OrderItems']);   
    }
    
    public function updateStock(array $array)
    {   
        $message = [
            'Header' => [
                'DocumentVersion' => 1.01,
                'MerchantIdentifier' => $this->config['Seller_Id']
            ],
            'MessageType' => 'Inventory',
            'Message' => []
        ];
        
        foreach ($array as $sku => $quantity) {
            $message['Message'][] = [
                'MessageID' => rand(),
                'OperationType' => 'Update',
                'Inventory' => [
                    'SKU' => $sku,
                    'Quantity' => (int) $quantity
                ]
            ];  
        }
        
        $response = $this->request($this->endPoints['SubmitFeed'], [
            'FeedType' => '_POST_INVENTORY_AVAILABILITY_DATA_',
            'PurgeAndReplace' => 'false',
            'Merchant' => $this->config['Seller_Id'],
            'MarketplaceId.Id.1' => false,
            'SellerId' => false,
        ], ArrayToXml::convert($message, 'AmazonEnvelope'));

        return $response['SubmitFeedResult']['FeedSubmissionInfo'];
        
    }
    
    private function request($endPoint, array $query = [], $body = null, $raw = false)
    {
    
        $query = array_merge([
            'Timestamp' => gmdate(self::DATE_FORMAT, time()),
            'AWSAccessKeyId' => $this->config['Access_Key_ID'],
            'Action' => $endPoint['action'],
            'MarketplaceId.Id.1' => $this->config['Marketplace_Id'],
            'SellerId' => $this->config['Seller_Id'],
            'SignatureMethod' => self::SIGNATURE_METHOD,
            'SignatureVersion' => self::SIGNATURE_VERSION,
            'Version' => $endPoint['date'],
        ], $query);
        
        if (isset($query['MarketplaceId'])) {
            unset($query['MarketplaceId.Id.1']);
        }
        
        try{
            
            $headers = [
                'Accept' => 'application/xml',
                'x-amazon-user-agent' => $this->config['Application_Name'] . '/' . $this->config['Application_Version']
            ];
            
            if ($endPoint['action'] === 'SubmitFeed') {
                $headers['Content-MD5'] = base64_encode(md5($body, true));
                $headers['Content-Type'] = 'text/xml; charset=iso-8859-1';
                $headers['Host'] = $this->config['Region_Host'];
                
                unset(
                    $query['MarketplaceId.Id.1'],
                    $query['SellerId']
                );  
            }
            
            $requestOptions = [
                'headers' => $headers,
                'body' => $body
            ];
            
            ksort($query);
            
            $query['Signature'] = base64_encode(
                hash_hmac(
                    'sha256', 
                    $endPoint['method']
                    . PHP_EOL 
                    . $this->config['Region_Host']
                    . PHP_EOL 
                    . $endPoint['path'] 
                    . PHP_EOL 
                    . http_build_query($query), 
                    $this->config['Secret_Access_Key'], 
                    true
                )
            );
            
            $requestOptions['query'] = $query;
            
            $client = new Client();
            
            $response = $client->request(
                $endPoint['method'], 
                $this->config['Region_Url'] . $endPoint['path'], 
                $requestOptions
            );
            
            $body = (string) $response->getBody();
            
            if ($raw) {
                return $body;    
            }
            
            return $this->xmlToArray($body);
           
        } catch(BadResponseException $e) {
            if ($e->hasResponse()) {
                $message = $e->getResponse();
                $message = $message->getBody();
                //$message .= $message->getStatusCode() . ' - ' . $message->getReasonPhrase();
            } else {
                $message = 'An error occured';    
            }
            throw new Exception($message);
        }  
    }
}