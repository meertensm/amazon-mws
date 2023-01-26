<?php
namespace MCS;

use DateTime;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use League\Csv\Reader;
use League\Csv\Writer;
use Spatie\ArrayToXml\ArrayToXml;
use SplTempFileObject;
use SimpleXMLElement;
use GuzzleHttp\Exception\GuzzleException;

class MWSClient
{

    const SIGNATURE_METHOD = 'HmacSHA256';
    const SIGNATURE_VERSION = '2';
    const DATE_FORMAT = "Y-m-d\TH:i:s.\\0\\0\\0\\Z";
    const APPLICATION_NAME = 'MCS/MwsClient';
    protected $debugNextFeed = false;
    protected $client = NULL;

    private $config = [
        'Seller_Id' => null,
        'Marketplace_Id' => null,
        'Access_Key_ID' => null,
        'Secret_Access_Key' => null,
        'MWSAuthToken' => null,
        'Application_Version' => '0.0.*'
    ];

    private $MarketplaceIds = [
        'A2EUQ1WTGCTBG2' => 'mws.amazonservices.ca',
        'ATVPDKIKX0DER' => 'mws.amazonservices.com',
        'A1AM78C64UM0Y8' => 'mws.amazonservices.com.mx',
        'A1PA6795UKMFR9' => 'mws-eu.amazonservices.com',
        'A1RKKUPIHCS9HS' => 'mws-eu.amazonservices.com',
        'A13V1IB3VIYZZH' => 'mws-eu.amazonservices.com',
        'A21TJRUUN4KGV' => 'mws.amazonservices.in',
        'APJ6JRA9NG5V4' => 'mws-eu.amazonservices.com',
        'A1F83G8C2ARO7P' => 'mws-eu.amazonservices.com',
        'A1VC38T7YXB528' => 'mws.amazonservices.jp',
        'AAHKV2X7AFYLW' => 'mws.amazonservices.com.cn',
        'A39IBJ37TRP1C6' => 'mws.amazonservices.com.au',
        'A2Q3Y263D00KWC' => 'mws.amazonservices.com'
    ];

    /**
     * @param array $config
     * @throws Exception
     */
    public function __construct(array $config)
    {

        foreach ($config as $key => $value) {
            if (array_key_exists($key, $this->config)) {
                $this->config[$key] = $value;
            }
        }

        $required_keys = [
            'Marketplace_Id', 'Seller_Id', 'Access_Key_ID', 'Secret_Access_Key'
        ];

        foreach ($required_keys as $key) {
            if (is_null($this->config[$key])) {
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

    /**
     * Call this method to get the raw feed instead of sending it
     */
    public function debugNextFeed()
    {
        $this->debugNextFeed = true;
    }

    /**
     * A method to quickly check if the supplied credentials are valid
     * @return boolean
     *
     * @throws GuzzleException
     */
    public function validateCredentials()
    {
        try {
            $this->ListOrderItems('validate');
        } catch (Exception $e) {
            if ($e->getMessage() == 'Invalid AmazonOrderId: validate') {
                return true;
            } else {
                return false;
            }
        }

        return false;
    }

    /**
     * Returns order items based on the AmazonOrderId that you specify.
     * @param string $AmazonOrderId
     * @return MWSResponse
     *
     * @throws GuzzleException
     */
    public function ListOrderItems($AmazonOrderId)
    {
        $response = $this->request('ListOrderItems', [
            'AmazonOrderId' => $AmazonOrderId
        ]);
        $result = $response->getOriginalResponse();
        $result = array_values($result['ListOrderItemsResult']['OrderItems']);

        if (isset($result[0]['QuantityOrdered'])) {
            $returnResult = $result;
        } else {
            $returnResult = $result[0];
        }

        $response->setUpdatedResponse($returnResult);
        return $response;
    }

    /**
     * Request MWS
     *
     * @return MWSResponse
     * @throws Exception
     * @throws GuzzleException
     */
    private function request($endPoint, array $query = [], $body = null)
    {

        $endPoint = MWSEndPoint::get($endPoint);

        $merge = [
            'Timestamp' => gmdate(self::DATE_FORMAT, time()),
            'AWSAccessKeyId' => $this->config['Access_Key_ID'],
            'Action' => $endPoint['action'],
            //'MarketplaceId.Id.1' => $this->config['Marketplace_Id'],
            'SellerId' => $this->config['Seller_Id'],
            'SignatureMethod' => self::SIGNATURE_METHOD,
            'SignatureVersion' => self::SIGNATURE_VERSION,
            'Version' => $endPoint['date'],
        ];

        $query = array_merge($merge, $query);

        if (!isset($query['MarketplaceId.Id.1'])) {
            $query['MarketplaceId.Id.1'] = $this->config['Marketplace_Id'];
        }

        if (!is_null($this->config['MWSAuthToken']) and $this->config['MWSAuthToken'] != "") {
            $query['MWSAuthToken'] = $this->config['MWSAuthToken'];
        }

        if (isset($query['MarketplaceId'])) {
            unset($query['MarketplaceId.Id.1']);
        }

        if (isset($query['MarketplaceIdList.Id.1'])) {
            unset($query['MarketplaceId.Id.1']);
        }

        try {

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
                    . "\n"
                    . $this->config['Region_Host']
                    . "\n"
                    . $endPoint['path']
                    . "\n"
                    . http_build_query($query, null, '&', PHP_QUERY_RFC3986),
                    $this->config['Secret_Access_Key'],
                    true
                )
            );

            $requestOptions['query'] = $query;

            if ($this->client === NULL) {
                $this->client = new Client();
            }

            $response = $this->client->request(
                $endPoint['method'],
                $this->config['Region_Url'] . $endPoint['path'],
                $requestOptions
            );


            return new MWSResponse($response);

        } catch (BadResponseException $e) {
            if ($e->hasResponse()) {
                $message = $e->getResponse();
                $message = $message->getBody();
                if (strpos($message, '<ErrorResponse') !== false) {
                    $error = simplexml_load_string($message);
                    $message = $error->Error->Message;
                }
            } else {
                $message = 'An error occured';
            }
            throw new Exception($message);
        }
    }

    /**
     * Returns the current competitive price of a product, based on ASIN.
     * @param array [$asin_array = []]
     * @return MWSResponse
     *
     * @throws Exception
     * @throws GuzzleException
     */
    public function GetCompetitivePricingForASIN($asin_array = [])
    {
        if (count($asin_array) > 20) {
            throw new Exception('Maximum amount of ASIN\'s for this call is 20');
        }

        $counter = 1;
        $query = [
            'MarketplaceId' => $this->config['Marketplace_Id']
        ];

        foreach ($asin_array as $key) {
            $query['ASINList.ASIN.' . $counter] = $key;
            $counter++;
        }

        $mwResponse = $this->request(
            'GetCompetitivePricingForASIN',
            $query
        );

        $response = $mwResponse->getOriginalResponse();

        $rawLoaded = simplexml_load_string($mwResponse->getOriginalResponse(true), SimpleXMLElement::class, LIBXML_NOCDATA);
        $listingCount = [];
        foreach ($rawLoaded->GetCompetitivePricingForASINResult as $resultPricing) {
            $product = $resultPricing->Product;
            $id = (string) $resultPricing->attributes()['ASIN'];

            if (isset($product->CompetitivePricing->NumberOfOfferListings->OfferListingCount)) {
                $id = (string) $product->Identifiers->MarketplaceASIN->ASIN;
                $listings = $product->CompetitivePricing
                    ->NumberOfOfferListings
                    ->OfferListingCount;

                foreach ($listings as $i) {
                    if (isset($i->attributes()['condition'])) {
                        if (!array_key_exists($id, $listingCount)) {
                            $listingCount[$id] = [];
                        }

                        $listingCount[$id][(string) $i->attributes()['condition']] = (int) $i;
                    }
                }
            }
        }

        if (isset($response['GetCompetitivePricingForASINResult'])) {
            $response = $response['GetCompetitivePricingForASINResult'];

            if (array_keys($response) !== range(0, count($response) - 1)) {
                $response = [$response];
            }
        } else {
            $mwResponse->setUpdatedResponse([]);
            return $mwResponse;
        }

        $array = [];
        foreach ($response as $product) {
            if (isset($product['Product']['CompetitivePricing']['CompetitivePrices']['CompetitivePrice']['Price'])) {
                $existsRankings = isset($product['Product']['SalesRankings']['SalesRank']);
                $asin = $product['Product']['Identifiers']['MarketplaceASIN']['ASIN'];
                $array[$asin] = [
                    'Price' => $product['Product']['CompetitivePricing']['CompetitivePrices']['CompetitivePrice']['Price'],
                    'SalesRank' => $existsRankings ?  $product['Product']['SalesRankings']['SalesRank'] : [],
                    'NumberOfOfferListings' => array_key_exists($asin, $listingCount) ? $listingCount[$asin] : []
                ];
            }
        }

        $mwResponse->setUpdatedResponse($array);
        return $mwResponse;
    }

    /**
     * Returns the current competitive price of a product, based on SKU.
     * @param array [$sku_array = []]
     * @return MWSResponse
     *
     * @throws Exception
     * @throws GuzzleException
     */
    public function GetCompetitivePricingForSKU($sku_array = [])
    {
        if (count($sku_array) > 20) {
            throw new Exception('Maximum amount of SKU\'s for this call is 20');
        }

        $counter = 1;
        $query = [
            'MarketplaceId' => $this->config['Marketplace_Id']
        ];

        foreach ($sku_array as $key) {
            $query['SellerSKUList.SellerSKU.' . $counter] = $key;
            $counter++;
        }

        $mwResponse = $this->request(
            'GetCompetitivePricingForSKU',
            $query
        );

        $response = $mwResponse->getOriginalResponse();

        if (isset($response['GetCompetitivePricingForSKUResult'])) {
            $response = $response['GetCompetitivePricingForSKUResult'];
            if (array_keys($response) !== range(0, count($response) - 1)) {
                $response = [$response];
            }
        } else {
            $mwResponse->setUpdatedResponse([]);
            return $mwResponse;
        }

        $array = [];
        foreach ($response as $product) {
            if (isset($product['Product']['CompetitivePricing']['CompetitivePrices']['CompetitivePrice']['Price'])) {
                $array[$product['Product']['Identifiers']['SKUIdentifier']['SellerSKU']]['Price'] = $product['Product']['CompetitivePricing']['CompetitivePrices']['CompetitivePrice']['Price'];
                $array[$product['Product']['Identifiers']['SKUIdentifier']['SellerSKU']]['Rank'] = $product['Product']['SalesRankings']['SalesRank'][1];
            }
        }

        $mwResponse->setUpdatedResponse($array);
        return $mwResponse;
    }

    /**
     * Returns lowest priced offers for a single product, based on ASIN.
     * @param string $asin
     * @param string [$ItemCondition = 'New'] Should be one in: New, Used, Collectible, Refurbished, Club
     * @return MWSResponse
     *
     * @throws Exception
     * @throws GuzzleException
     */
    public function GetLowestPricedOffersForASIN($asin, $ItemCondition = 'New')
    {

        $query = [
            'ASIN' => $asin,
            'MarketplaceId' => $this->config['Marketplace_Id'],
            'ItemCondition' => $ItemCondition
        ];

        return $this->request('GetLowestPricedOffersForASIN', $query);

    }

    /**
     * Returns pricing information for your own offer listings, based on SKU.
     * @param array  [$sku_array = []]
     * @param string [$ItemCondition = null]
     * @return MWSResponse
     *
     * @throws Exception
     * @throws GuzzleException
     */
    public function GetMyPriceForSKU($sku_array = [], $ItemCondition = null)
    {
        if (count($sku_array) > 20) {
            throw new Exception('Maximum amount of SKU\'s for this call is 20');
        }

        $counter = 1;
        $query = [
            'MarketplaceId' => $this->config['Marketplace_Id']
        ];

        if (!is_null($ItemCondition)) {
            $query['ItemCondition'] = $ItemCondition;
        }

        foreach ($sku_array as $key) {
            $query['SellerSKUList.SellerSKU.' . $counter] = $key;
            $counter++;
        }

        $mwResponse = $this->request(
            'GetMyPriceForSKU',
            $query
        );

        $response = $mwResponse->getOriginalResponse();

        if (isset($response['GetMyPriceForSKUResult'])) {
            $response = $response['GetMyPriceForSKUResult'];
            if (array_keys($response) !== range(0, count($response) - 1)) {
                $response = [$response];
            }
        } else {
            return $mwResponse;
        }

        $array = [];
        foreach ($response as $product) {
            if (isset($product['@attributes']['status']) && $product['@attributes']['status'] == 'Success') {
                if (isset($product['Product']['Offers']['Offer'])) {
                    $array[$product['@attributes']['SellerSKU']] = $product['Product']['Offers']['Offer'];
                } else {
                    $array[$product['@attributes']['SellerSKU']] = [];
                }
            } else {
                $array[$product['@attributes']['SellerSKU']] = false;
            }
        }

        $mwResponse->setUpdatedResponse($array);
        return $mwResponse;

    }

    /**
     * Returns pricing information for your own offer listings, based on ASIN.
     * @param array [$asin_array = []]
     * @param string [$ItemCondition = null]
     * @return MWSResponse
     *
     * @throws Exception
     * @throws GuzzleException
     */
    public function GetMyPriceForASIN($asin_array = [], $ItemCondition = null)
    {
        if (count($asin_array) > 20) {
            throw new Exception('Maximum amount of SKU\'s for this call is 20');
        }

        $counter = 1;
        $query = [
            'MarketplaceId' => $this->config['Marketplace_Id']
        ];

        if (!is_null($ItemCondition)) {
            $query['ItemCondition'] = $ItemCondition;
        }

        foreach ($asin_array as $key) {
            $query['ASINList.ASIN.' . $counter] = $key;
            $counter++;
        }

        $mwResponse = $this->request(
            'GetMyPriceForASIN',
            $query
        );
        $response = $mwResponse->getOriginalResponse();

        if (isset($response['GetMyPriceForASINResult'])) {
            $response = $response['GetMyPriceForASINResult'];
            if (array_keys($response) !== range(0, count($response) - 1)) {
                $response = [$response];
            }
        } else {
            return $mwResponse;
        }

        $array = [];
        foreach ($response as $product) {
            if (isset($product['@attributes']['status']) && $product['@attributes']['status'] == 'Success' && isset($product['Product']['Offers']['Offer'])) {
                $array[$product['@attributes']['ASIN']] = $product['Product']['Offers']['Offer'];
            } else {
                $array[$product['@attributes']['ASIN']] = false;
            }
        }

        $mwResponse->setUpdatedResponse($array);
        return $mwResponse;

    }

    /**
     * Returns pricing information for the lowest-price active offer listings for up to 20 products, based on ASIN.
     * @param array [$asin_array = []] array of ASIN values
     * @param array [$ItemCondition = null] Should be one in: New, Used, Collectible, Refurbished, Club. Default: All
     * @return MWSResponse
     *
     * @throws Exception
     * @throws GuzzleException
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

        foreach ($asin_array as $key) {
            $query['ASINList.ASIN.' . $counter] = $key;
            $counter++;
        }

        $mwResponse = $this->request(
            'GetLowestOfferListingsForASIN',
            $query
        );
        $response = $mwResponse->getOriginalResponse();
        
        if (isset($response['GetLowestOfferListingsForASINResult'])) {
            $response = $response['GetLowestOfferListingsForASINResult'];
            if (array_keys($response) !== range(0, count($response) - 1)) {
                $response = [$response];
            }
        } else {
            $mwResponse->setUpdatedResponse([]);
            return $mwResponse;
        }

        $array = [];
        foreach ($response as $product) {
            if (isset($product['Product']['LowestOfferListings']['LowestOfferListing'])) {
                $array[$product['Product']['Identifiers']['MarketplaceASIN']['ASIN']] = $product['Product']['LowestOfferListings']['LowestOfferListing'];
            } else {
                $array[$product['Product']['Identifiers']['MarketplaceASIN']['ASIN']] = false;
            }
        }
        $mwResponse->setUpdatedResponse($array);
        return $mwResponse;

    }

    /**
     * Returns orders created or updated during a time frame that you specify.
     * @param DateTime $from
     * @param boolean $allMarketplaces , list orders from all marketplaces
     * @param array $states , an array containing orders states you want to filter on
     * @param string $FulfillmentChannels
     * @param DateTime $till , end of time frame
     * @return MWSResponse
     *
     * @throws GuzzleException
     */
    public function ListOrders(DateTime $from, $allMarketplaces = false, $states = [
        'Unshipped', 'PartiallyShipped'
    ], $FulfillmentChannels = 'MFN', DateTime $till = null)
    {
        $query = [
            'CreatedAfter' => gmdate(self::DATE_FORMAT, $from->getTimestamp())
        ];

        if ($till !== null) {
            $query['CreatedBefore'] = gmdate(self::DATE_FORMAT, $till->getTimestamp());
        }

        $counter = 1;
        foreach ($states as $status) {
            $query['OrderStatus.Status.' . $counter] = $status;
            $counter = $counter + 1;
        }

        if ($allMarketplaces == true) {
            $counter = 1;
            foreach ($this->MarketplaceIds as $key => $value) {
                $query['MarketplaceId.Id.' . $counter] = $key;
                $counter = $counter + 1;
            }
        }

        if (is_array($FulfillmentChannels)) {
            $counter = 1;
            foreach ($FulfillmentChannels as $fulfillmentChannel) {
                $query['FulfillmentChannel.Channel.' . $counter] = $fulfillmentChannel;
                $counter = $counter + 1;
            }
        } else {
            $query['FulfillmentChannel.Channel.1'] = $FulfillmentChannels;
        }

        $mwResponse = $this->request('ListOrders', $query);
        $response = $mwResponse->getOriginalResponse();

        if (isset($response['ListOrdersResult']['Orders']['Order'])) {
            if (isset($response['ListOrdersResult']['NextToken'])) {
                $data['ListOrders'] = $response['ListOrdersResult']['Orders']['Order'];
                $data['NextToken'] = $response['ListOrdersResult']['NextToken'];
                $mwResponse->setUpdatedResponse($data);
                return $mwResponse;
            }

            $response = $response['ListOrdersResult']['Orders']['Order'];

            if (array_keys($response) !== range(0, count($response) - 1)) {
                $mwResponse->setUpdatedResponse([$response]);
            }

            $mwResponse->setUpdatedResponse($response);

        } else {
            $mwResponse->setUpdatedResponse([]);
        }

        return $mwResponse;
    }

    /**
     * Returns orders created or updated during a time frame that you specify.
     * @param string $nextToken
     * @return MWSResponse
     *
     * @throws GuzzleException
     */
    public function ListOrdersByNextToken($nextToken)
    {
        $query = [
            'NextToken' => $nextToken,
        ];

        $mwResponse = $this->request(
            'ListOrdersByNextToken',
            $query
        );
        $response = $mwResponse->getOriginalResponse();

        if (isset($response['ListOrdersByNextTokenResult']['Orders']['Order'])) {
            if (isset($response['ListOrdersByNextTokenResult']['NextToken'])) {
                $data['ListOrders'] = $response['ListOrdersByNextTokenResult']['Orders']['Order'];
                $data['NextToken'] = $response['ListOrdersByNextTokenResult']['NextToken'];
                $mwResponse->setUpdatedResponse($data);
                return $mwResponse;

            }
            $response = $response['ListOrdersByNextTokenResult']['Orders']['Order'];

            if (array_keys($response) !== range(0, count($response) - 1)) {
                $mwResponse->setUpdatedResponse([$response]);
            } else {

                $mwResponse->setUpdatedResponse($response);
            }
        } else {
            $mwResponse->setUpdatedResponse([]);
        }

        return $mwResponse;
    }

    /**
     * Returns an order based on the AmazonOrderId values that you specify.
     * @param string $AmazonOrderId
     * @return MWSResponse|bool if the order is found, false if not
     * @throws GuzzleException
     */
    public function GetOrder($AmazonOrderId)
    {
        $mwResponse = $this->request('GetOrder', [
            'AmazonOrderId.Id.1' => $AmazonOrderId
        ]);
        $response = $mwResponse->getOriginalResponse();

        if (isset($response['GetOrderResult']['Orders']['Order'])) {
            $mwResponse->setUpdatedResponse($response['GetOrderResult']['Orders']['Order']);
            return $mwResponse;
        } else {
            return false;
        }
    }

    /**
     * Returns the parent product categories that a product belongs to, based on SellerSKU.
     * @param string $SellerSKU
     * @return MWSResponse|bool if found, false if not found
     *
     * @throws Exception
     * @throws GuzzleException
     */
    public function GetProductCategoriesForSKU($SellerSKU)
    {
        $mwResponse = $this->request('GetProductCategoriesForSKU', [
            'MarketplaceId' => $this->config['Marketplace_Id'],
            'SellerSKU' => $SellerSKU
        ]);
        $result = $mwResponse->getOriginalResponse();

        if (isset($result['GetProductCategoriesForSKUResult']['Self'])) {
            $mwResponse->setUpdatedResponse($result['GetProductCategoriesForSKUResult']['Self']);
            return $mwResponse;
        } else {
            return false;
        }
    }

    /**
     * Returns the parent product categories that a product belongs to, based on ASIN.
     * @param string $ASIN
     * @return MWSResponse|bool if found, false if not found
     *
     * @throws Exception
     * @throws GuzzleException
     */
    public function GetProductCategoriesForASIN($ASIN)
    {
        $result = $this->request('GetProductCategoriesForASIN', [
            'MarketplaceId' => $this->config['Marketplace_Id'],
            'ASIN' => $ASIN
        ]);

        if (isset($result['GetProductCategoriesForASINResult']['Self'])) {
            return $result['GetProductCategoriesForASINResult']['Self'];
        } else {
            return false;
        }
    }

    /**
     * Returns a list of products and their attributes, based on a list of ASIN, GCID, SellerSKU, UPC, EAN, ISBN, and JAN values.
     * @param array $asin_array A list of id's
     * @param string [$type = 'ASIN']  the identifier name
     * @return MWSResponse
     *
     * @throws Exception
     * @throws GuzzleException
     */
    public function GetMatchingProductForId(array $asin_array, $type = 'ASIN')
    {
        $asin_array = array_unique($asin_array);

        if (count($asin_array) > 5) {
            throw new Exception('Maximum number of id\'s = 5');
        }

        $counter = 1;
        $array = [
            'MarketplaceId' => $this->config['Marketplace_Id'],
            'IdType' => $type
        ];

        foreach ($asin_array as $asin) {
            $array['IdList.Id.' . $counter] = $asin;
            $counter++;
        }

        $mwResponse = $this->request(
            'GetMatchingProductForId',
            $array,
            null
        );
        $response = $mwResponse->getOriginalResponse(true);

        $languages = [
            'de-DE', 'en-EN', 'es-ES', 'fr-FR', 'it-IT', 'en-US'
        ];

        $replace = [
            '</ns2:ItemAttributes>' => '</ItemAttributes>'
        ];

        foreach ($languages as $language) {
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
            foreach ($response['GetMatchingProductForIdResult'] as $result) {

                //print_r($product);exit;

                $asin = $result['@attributes']['Id'];
                if ($result['@attributes']['status'] != 'Success') {
                    $not_found[] = $asin;
                } else {
                    if (isset($result['Products']['Product']['AttributeSets'])) {
                        $products[0] = $result['Products']['Product'];
                    } else {
                        $products = $result['Products']['Product'];
                    }
                    foreach ($products as $product) {
                        $array = [];
                        if (isset($product['Identifiers']['MarketplaceASIN']['ASIN'])) {
                            $array["ASIN"] = $product['Identifiers']['MarketplaceASIN']['ASIN'];
                        }

                        foreach ($product['AttributeSets']['ItemAttributes'] as $key => $value) {
                            if (is_string($key) && is_string($value)) {
                                $array[$key] = $value;
                            }
                        }

                        if (isset($product['AttributeSets']['ItemAttributes']['Feature'])) {
                            $array['Feature'] = $product['AttributeSets']['ItemAttributes']['Feature'];
                        }

                        if (isset($product['AttributeSets']['ItemAttributes']['PackageDimensions'])) {
                            $array['PackageDimensions'] = array_map(
                                'floatval',
                                $product['AttributeSets']['ItemAttributes']['PackageDimensions']
                            );
                        }

                        if (isset($product['AttributeSets']['ItemAttributes']['ListPrice'])) {
                            $array['ListPrice'] = $product['AttributeSets']['ItemAttributes']['ListPrice'];
                        }

                        if (isset($product['AttributeSets']['ItemAttributes']['SmallImage'])) {
                            $image = $product['AttributeSets']['ItemAttributes']['SmallImage']['URL'];
                            $array['medium_image'] = $image;
                            $array['small_image'] = str_replace('._SL75_', '._SL50_', $image);
                            $array['large_image'] = str_replace('._SL75_', '', $image);;
                        }
                        if (isset($product['Relationships']['VariationParent']['Identifiers']['MarketplaceASIN']['ASIN'])) {
                            $array['Parentage'] = 'child';
                            $array['Relationships'] = $product['Relationships']['VariationParent']['Identifiers']['MarketplaceASIN']['ASIN'];
                        }
                        if (isset($product['Relationships']['VariationChild'])) {
                            $array['Parentage'] = 'parent';
                        }
                        if (isset($product['SalesRankings']['SalesRank'])) {
                            $array['SalesRank'] = $product['SalesRankings']['SalesRank'];
                        }
                        $found[$asin][] = $array;
                    }
                }
            }
        }

        $mwResponse->setUpdatedResponse([
            'found' => $found,
            'not_found' => $not_found
        ]);

        return $mwResponse;

    }

    /**
     * Convert an xml string to an array
     * @param string $xmlstring
     * @return array
     */
    private function xmlToArray($xmlstring)
    {
        return json_decode(json_encode(simplexml_load_string($xmlstring)), true);
    }

    /**
     * Returns a list of products and their attributes, ordered by relevancy, based on a search query that you specify.
     * @param string $query the open text query
     * @param string [$query_context_id = null] the identifier for the context within which the given search will be performed. see: http://docs.developer.amazonservices.com/en_US/products/Products_QueryContextIDs.html
     * @return MWSResponse
     *
     * @throws GuzzleException
     * @throws Exception
     */
    public function ListMatchingProducts($query, $query_context_id = null)
    {

        if (trim($query) == "") {
            throw new Exception('Missing query');
        }

        $array = [
            'MarketplaceId' => $this->config['Marketplace_Id'],
            'Query' => urlencode($query),
            'QueryContextId' => $query_context_id
        ];

        $mwResponse = $this->request(
            'ListMatchingProducts',
            $array,
            null
        );
        $response = $mwResponse->getOriginalResponse(true);


        $languages = [
            'de-DE', 'en-EN', 'es-ES', 'fr-FR', 'it-IT', 'en-US'
        ];

        $replace = [
            '</ns2:ItemAttributes>' => '</ItemAttributes>'
        ];

        foreach ($languages as $language) {
            $replace['<ns2:ItemAttributes xml:lang="' . $language . '">'] = '<ItemAttributes><Language>' . $language . '</Language>';
        }

        $replace['ns2:'] = '';

        $response = $this->xmlToArray(strtr($response, $replace));

        if (isset($response['ListMatchingProductsResult'])) {
            $mwResponse->setUpdatedResponse($response['ListMatchingProductsResult']);
        } else {
            $mwResponse->setUpdatedResponse(['ListMatchingProductsResult' => []]);
        }

        return $mwResponse;
    }

    /**
     * Returns a list of reports that were created in the previous 90 days.
     * @param array [$ReportTypeList = []]
     * @return MWSResponse
     *
     * @throws Exception
     * @throws GuzzleException
     */
    public function GetReportList($ReportTypeList = [])
    {
        $array = [];
        $counter = 1;
        if (count($ReportTypeList)) {
            foreach ($ReportTypeList as $ReportType) {
                $array['ReportTypeList.Type.' . $counter] = $ReportType;
                $counter++;
            }
        }

        return $this->request('GetReportList', $array);
    }

    /**
     * Returns your active recommendations for a specific category or for all categories for a specific marketplace.
     * @param string [$RecommendationCategory = null] One of: Inventory, Selection, Pricing, Fulfillment, ListingQuality, GlobalSelling, Advertising
     * @return MWSResponse|bool
     *
     * @throws Exception
     * @throws GuzzleException
     */
    public function ListRecommendations($RecommendationCategory = null)
    {
        $query = [
            'MarketplaceId' => $this->config['Marketplace_Id']
        ];

        if (!is_null($RecommendationCategory)) {
            $query['RecommendationCategory'] = $RecommendationCategory;
        }

        $mwResponse = $this->request('ListRecommendations', $query);
        $result = $mwResponse->getOriginalResponse();

        if (isset($result['ListRecommendationsResult'])) {
            $mwResponse->setUpdatedResponse($result['ListRecommendationsResult']);
            return $mwResponse;
        } else {
            return false;
        }

    }

    /**
     * Returns a list of marketplaces that the seller submitting the request can sell in, and a list of participations that include seller-specific information in that marketplace
     * @return MWSResponse
     *
     * @throws GuzzleException
     */
    public function ListMarketplaceParticipations()
    {
        $mwResult = $this->request('ListMarketplaceParticipations');
        $result = $mwResult->getOriginalResponse();

        if (isset($result['ListMarketplaceParticipationsResult'])) {
            $mwResult->setUpdatedResponse($result);
        }

        return $mwResult;
    }

    /**
     * Delete product's based on SKU
     * @param string $array array containing sku's
     * @return MWSResponse feed submission result
     * @throws GuzzleException
     */
    public function deleteProductBySKU(array $array)
    {

        $feed = [
            'MessageType' => 'Product',
            'Message' => []
        ];

        foreach ($array as $sku) {
            $feed['Message'][] = [
                'MessageID' => rand(),
                'OperationType' => 'Delete',
                'Product' => [
                    'SKU' => $sku
                ]
            ];
        }

        return $this->SubmitFeed('_POST_PRODUCT_DATA_', $feed);
    }

    /**
     * Uploads a feed for processing by Amazon MWS.
     * @param string $FeedType (http://docs.developer.amazonservices.com/en_US/feeds/Feeds_FeedType.html)
     * @param mixed $feedContent Array will be converted to xml using https://github.com/spatie/array-to-xml. Strings will not be modified.
     * @param boolean $debug Return the generated xml and don't send it to amazon
     * @return MWSResponse
     *
     * @throws GuzzleException
     */
    public function SubmitFeed($FeedType, $feedContent, $debug = false, $options = [])
    {

        if (is_array($feedContent)) {
            $feedContent = $this->arrayToXml(
                array_merge([
                    'Header' => [
                        'DocumentVersion' => 1.01,
                        'MerchantIdentifier' => $this->config['Seller_Id']
                    ]
                ], $feedContent)
            );
        }

        if ($debug === true) {
            return (new MWSResponse())->setUpdatedResponse($feedContent);
        } else if ($this->debugNextFeed == true) {
            $this->debugNextFeed = false;
            return (new MWSResponse())->setUpdatedResponse($feedContent);
        }

        $purgeAndReplace = isset($options['PurgeAndReplace']) ? $options['PurgeAndReplace'] : false;

        $query = [
            'FeedType' => $FeedType,
            'PurgeAndReplace' => ($purgeAndReplace ? 'true' : 'false'),
            'Merchant' => $this->config['Seller_Id'],
            'MarketplaceId.Id.1' => false,
            'SellerId' => false,
        ];

        //if ($FeedType === '_POST_PRODUCT_PRICING_DATA_') {
        $query['MarketplaceIdList.Id.1'] = $this->config['Marketplace_Id'];
        //}

        $mwResponse = $this->request(
            'SubmitFeed',
            $query,
            $feedContent
        );

        $response = $mwResponse->getOriginalResponse();
        $mwResponse->setUpdatedResponse($response['SubmitFeedResult']['FeedSubmissionInfo']);
        return $mwResponse;
    }

    /**
     * Convert an array to xml
     * @param $array array to convert
     * @param $customRoot [$customRoot = 'AmazonEnvelope']
     * @return string
     */
    private function arrayToXml(array $array, $customRoot = 'AmazonEnvelope')
    {
        return ArrayToXml::convert($array, $customRoot);
    }

    /**
     * Update a product's stock quantity
     * @param array $array array containing sku as key and quantity as value
     * @return MWSResponse feed submission result
     * @throws GuzzleException
     */
    public function updateStock(array $array)
    {
        $feed = [
            'MessageType' => 'Inventory',
            'Message' => []
        ];

        foreach ($array as $sku => $quantity) {
            $feed['Message'][] = [
                'MessageID' => rand(),
                'OperationType' => 'Update',
                'Inventory' => [
                    'SKU' => $sku,
                    'Quantity' => (int)$quantity
                ]
            ];
        }

        return $this->SubmitFeed('_POST_INVENTORY_AVAILABILITY_DATA_', $feed);

    }

    /**
     * Update a product's stock quantity
     *
     * @param array $array array containing arrays with next keys: [sku, quantity, latency]
     * @return MWSResponse feed submission result
     * @throws GuzzleException
     */
    public function updateStockWithFulfillmentLatency(array $array)
    {
        $feed = [
            'MessageType' => 'Inventory',
            'Message' => []
        ];

        foreach ($array as $item) {
            $feed['Message'][] = [
                'MessageID' => rand(),
                'OperationType' => 'Update',
                'Inventory' => [
                    'SKU' => $item['sku'],
                    'Quantity' => (int)$item['quantity'],
                    'FulfillmentLatency' => $item['latency']
                ]
            ];
        }

        return $this->SubmitFeed('_POST_INVENTORY_AVAILABILITY_DATA_', $feed);
    }

    /**
     * Update a product's price
     * @param array $standardprice an array containing sku as key and price as value
     * @param array $salesprice an optional array with sku as key and value consisting of an array with key/value pairs for SalePrice, StartDate, EndDate
     * Dates in DateTime object
     * Price has to be formatted as XSD Numeric Data Type (http://www.w3schools.com/xml/schema_dtypes_numeric.asp)
     * @return MWSResponse feed submission result
     * @throws GuzzleException
     */
    public function updatePrice(array $standardprice, array $saleprice = null)
    {

        $feed = [
            'MessageType' => 'Price',
            'Message' => []
        ];

        foreach ($standardprice as $sku => $price) {
            $feed['Message'][] = [
                'MessageID' => rand(),
                'Price' => [
                    'SKU' => $sku,
                    'StandardPrice' => [
                        '_value' => strval($price),
                        '_attributes' => [
                            'currency' => 'DEFAULT'
                        ]
                    ]
                ]
            ];

            if (isset($saleprice[$sku]) && is_array($saleprice[$sku])) {
                $feed['Message'][count($feed['Message']) - 1]['Price']['Sale'] = [
                    'StartDate' => $saleprice[$sku]['StartDate']->format(self::DATE_FORMAT),
                    'EndDate' => $saleprice[$sku]['EndDate']->format(self::DATE_FORMAT),
                    'SalePrice' => [
                        '_value' => strval($saleprice[$sku]['SalePrice']),
                        '_attributes' => [
                            'currency' => 'DEFAULT'
                        ]]
                ];
            }
        }

        return $this->SubmitFeed('_POST_PRODUCT_PRICING_DATA_', $feed);
    }

    /**
     * Post to create or update a product (_POST_FLAT_FILE_LISTINGS_DATA_)
     * @param object $MWSProduct or array of MWSProduct objects
     * @return MWSResponse
     *
     * @throws GuzzleException
     */
    public function postProduct($MWSProduct)
    {

        if (!is_array($MWSProduct)) {
            $MWSProduct = [$MWSProduct];
        }

        $csv = Writer::createFromFileObject(new SplTempFileObject());

        $csv->setDelimiter("\t");
        $csv->setInputEncoding('iso-8859-1');

        $csv->insertOne(['TemplateType=Offer', 'Version=2014.0703']);

        $header = ['sku', 'price', 'quantity', 'product-id',
            'product-id-type', 'condition-type', 'condition-note',
            'ASIN-hint', 'title', 'product-tax-code', 'operation-type',
            'sale-price', 'sale-start-date', 'sale-end-date', 'leadtime-to-ship',
            'launch-date', 'is-giftwrap-available', 'is-gift-message-available',
            'fulfillment-center-id', 'main-offer-image', 'offer-image1',
            'offer-image2', 'offer-image3', 'offer-image4', 'offer-image5'
        ];

        $csv->insertOne($header);
        $csv->insertOne($header);

        foreach ($MWSProduct as $product) {
            $csv->insertOne(
                array_values($product->toArray())
            );
        }

        return $this->SubmitFeed('_POST_FLAT_FILE_LISTINGS_DATA_', $csv);

    }

    /**
     * Returns the feed processing report and the Content-MD5 header.
     * @param string $FeedSubmissionId
     * @return MWSResponse
     *
     * @throws Exception
     * @throws GuzzleException
     */
    public function GetFeedSubmissionResult($FeedSubmissionId)
    {
        $mwResult = $this->request('GetFeedSubmissionResult', [
            'FeedSubmissionId' => $FeedSubmissionId
        ]);
        $result = $mwResult->getOriginalResponse();

        if (isset($result['Message']['ProcessingReport'])) {
            $mwResult->setUpdatedResponse($result['Message']['ProcessingReport']);
            return $mwResult;
        } else {
            return $mwResult;
        }
    }

    /**
     * Creates a report request and submits the request to Amazon MWS.
     * @param string $report (http://docs.developer.amazonservices.com/en_US/reports/Reports_ReportType.html)
     * @param DateTime|null $StartDate
     * @param DateTime|null $EndDate
     *
     * @return MWSResponse ReportRequestId
     * @throws GuzzleException
     * @throws Exception
     */
    public function RequestReport($report, DateTime $StartDate = null, DateTime $EndDate = null)
    {
        $query = [
            'MarketplaceIdList.Id.1' => $this->config['Marketplace_Id'],
            'ReportType' => $report
        ];

        if (!is_null($StartDate)) {
            $query['StartDate'] = gmdate(self::DATE_FORMAT, $StartDate->getTimestamp());
        }

        if (!is_null($EndDate)) {
            $query['EndDate'] = gmdate(self::DATE_FORMAT, $EndDate->getTimestamp());
        }

        $mwResult = $this->request(
            'RequestReport',
            $query
        );
        $result = $mwResult->getOriginalResponse();

        if (isset($result['RequestReportResult']['ReportRequestInfo']['ReportRequestId'])) {
            $mwResult->setUpdatedResponse($result['RequestReportResult']['ReportRequestInfo']['ReportRequestId']);
            return $mwResult;
        } else {
            throw new Exception('Error trying to request report');
        }
    }

    /**
     * Get a report's content
     * @param string $ReportId
     * @return MWSResponse|bool
     *
     * @throws Exception
     * @throws GuzzleException
     */
    public function GetReport($ReportId)
    {
        $statusResponse = $this->GetReportRequestStatus($ReportId);
        $status = $this->GetReportRequestStatus($ReportId);

        if ($status !== false && $status['ReportProcessingStatus'] === '_DONE_NO_DATA_') {
            $statusResponse->setUpdatedResponse([]);
            return $statusResponse;
        } else if ($status !== false && $status['ReportProcessingStatus'] === '_DONE_') {

            $resultReport = $this->request('GetReport', [
                'ReportId' => $status['GeneratedReportId']
            ]);
            $result = $resultReport->getOriginalResponse(false);

            if (is_string($result)) {
                $csv = Reader::createFromString($result);
                $csv->setDelimiter("\t");
                $headers = $csv->fetchOne();
                $result = [];
                foreach ($csv->setOffset(1)->fetchAll() as $row) {
                    $result[] = array_combine($headers, $row);
                }
            }

            $resultReport->setUpdatedResponse($result);
            return $resultReport;

        } else {
            return false;
        }
    }

    /**
     * Get a report's processing status
     * @param string $ReportId
     * @return MWSResponse|bool if the report is found
     *
     * @throws Exception
     * @throws GuzzleException
     */
    public function GetReportRequestStatus($ReportId)
    {
        $mwResponse = $this->request('GetReportRequestList', [
            'ReportRequestIdList.Id.1' => $ReportId
        ]);

        $result = $mwResponse->getOriginalResponse();

        if (isset($result['GetReportRequestListResult']['ReportRequestInfo'])) {
            $mwResponse->setUpdatedResponse($result['GetReportRequestListResult']['ReportRequestInfo']);
            return $mwResponse;
        }

        return false;

    }

    /**
     * Get a list's inventory for Amazon's fulfillment
     *
     * @param array $sku_array
     *
     * @return MWSResponse
     * @throws Exception
     * @throws GuzzleException
     */
    public function ListInventorySupply($sku_array = [])
    {

        if (count($sku_array) > 50) {
            throw new Exception('Maximum amount of SKU\'s for this call is 50');
        }

        $counter = 1;
        $query = [
            'MarketplaceId' => $this->config['Marketplace_Id']
        ];

        foreach ($sku_array as $key) {
            $query['SellerSkus.member.' . $counter] = $key;
            $counter++;
        }

        $mwResponse = $this->request(
            'ListInventorySupply',
            $query
        );
        $response = $mwResponse->getOriginalResponse();

        $result = [];
        if (isset($response['ListInventorySupplyResult']['InventorySupplyList']['member'])) {
            foreach ($response['ListInventorySupplyResult']['InventorySupplyList']['member'] as $index => $ListInventorySupplyResult) {
                $result[$index] = $ListInventorySupplyResult;
            }
        }

        $mwResponse->setUpdatedResponse($result);
        return $mwResponse;
    }

    public function setClient(Client $client)
    {
        $this->client = $client;
    }
}
