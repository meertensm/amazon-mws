<?php namespace MCS;

use DateTime;
use Exception;
use DateTimeZone;

use MCS\MWSOrder;

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
        'Application_Version' => '0.0.1'
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
        ]
    ];
    
    public function __construct(array $config)
    {   
        foreach($config as $key => $value)
        {
            $this->config[$key] = $value;
        }
        
        $this->config['Application_Version'] = file_get_contents('./version.txt');
        
        foreach($this->config as $key => $value)
        {
            if(is_null($value)){
                throw new Exception('Required field ' . $key . ' is not set');    
            }
        } 
        
        if(!isset($this->MarketplaceIds[$this->config['Marketplace_Id']])){
            throw new Exception('Invalid Marketplace Id');    
        }
        
        $this->config['Application_Name'] = self::APPLICATION_NAME;
        $this->config['Region_Host'] = $this->MarketplaceIds[$this->config['Marketplace_Id']];
        $this->config['Region_Url'] = 'https://' . $this->config['Region_Host'];
        
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
        
        if(isset($response['ListOrdersResult']['Orders']['Order'])){
            $response = $response['ListOrdersResult']['Orders']['Order'];
            if (array_keys($response) !== range(0, count($response) - 1)){
                return [$response];
            }
            return $response;
        }
        else{
            return false;    
        }   
    }
    
    public function GetOrder($id)
    { 
        $response = $this->request($this->endPoints['GetOrder'], [
            'AmazonOrderId.Id.1' => $id
        ]); 
        
        if(isset($response['GetOrderResult']['Orders']['Order'])){
            return $response['GetOrderResult']['Orders']['Order'];
        }
        else{
            return false;    
        }
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
        
        foreach($array as $sku => $quantity){
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
    
    private function request($endPoint, array $query = [], $body = null)
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
        
        try{
            
            $headers = [
                'Accept' => 'application/xml',
                'x-amazon-user-agent' => $this->config['Application_Name'] . '/' . $this->config['Application_Version']
            ];
            
            if($endPoint['action'] === 'SubmitFeed'){
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
            
            $body = simplexml_load_string((string) $response->getBody());
            
            return json_decode(json_encode($body), true);
           
        }
        catch(BadResponseException $e){
            if ($e->hasResponse()){
                $message = $e->getResponse();
                $message = $message->getStatusCode() . ' - ' . $message->getReasonPhrase();
            }
            else{
                $message = 'An error occured';    
            }
            throw new Exception($message);
        }  
    }
}