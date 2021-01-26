<?php

namespace MCS;

class AmazonMarketPlaceProduct
{
    private $feed_product_type;
    private $sku;
    private $price;
    private $quantity = 0;
    private $product_id;
    private $product_id_type;
    private $condition_type = 'New';
    private $condition_note;
    private $title;
    private $brand;
    private $shipping;
    private $weight;
    private $image = [];
    private $recommended_browse_nodes;
    private $description;
    private $parent_sku;
    private $variation_theme;
    private $keywords;
    private $bullet_point = [];
    private $sale_price;
    private $currency;
    private $color_name;
    private $size_name;
    private $manufacturer;
    private $sale_from_date;
    private $sale_end_date;
    private $other_attributes = [];
    /**
     * @var string Child or Parent or ''
     */
    private $parent_child;


    /**
     * @param mixed $sku
     * @return AmazonMarketPlaceProduct
     */
    public function setSku($sku)
    {
        $this->sku = $sku;
        return $this;
    }

    /**
     * @param mixed $price
     * @return AmazonMarketPlaceProduct
     */
    public function setPrice($price)
    {
        $this->price = $price;
        return $this;
    }

    /**
     * @param int $quantity
     * @return AmazonMarketPlaceProduct
     */
    public function setQuantity(int $quantity): AmazonMarketPlaceProduct
    {
        $this->quantity = $quantity;
        return $this;
    }

    /**
     * @param mixed $product_id
     * @return AmazonMarketPlaceProduct
     */
    public function setProductId($product_id)
    {
        $this->product_id = $product_id;
        return $this;
    }

    /**
     * @param mixed $product_id_type
     * @return AmazonMarketPlaceProduct
     */
    public function setProductIdType($product_id_type)
    {
        $this->product_id_type = $product_id_type;
        return $this;
    }

    /**
     * @param string $condition_type
     * @return AmazonMarketPlaceProduct
     */
    public function setConditionType(string $condition_type): AmazonMarketPlaceProduct
    {
        $this->condition_type = $condition_type;
        return $this;
    }

    /**
     * @param mixed $condition_note
     * @return AmazonMarketPlaceProduct
     */
    public function setConditionNote($condition_note)
    {
        $this->condition_note = $condition_note;
        return $this;
    }

    /**
     * @param mixed $title
     * @return AmazonMarketPlaceProduct
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @param mixed $brand
     * @return AmazonMarketPlaceProduct
     */
    public function setBrand($brand)
    {
        $this->brand = $brand;
        return $this;
    }

    /**
     * @param mixed $shipping
     * @return AmazonMarketPlaceProduct
     */
    public function setShipping($shipping)
    {
        $this->shipping = $shipping;
        return $this;
    }

    /**
     * @param array $image
     * @return AmazonMarketPlaceProduct
     */
    public function setImage(array $image): AmazonMarketPlaceProduct
    {
        $this->image = $image;
        return $this;
    }

    /**
     * @param array $validation_errors
     * @return AmazonMarketPlaceProduct
     */
    public function setValidationErrors(array $validation_errors): AmazonMarketPlaceProduct
    {
        $this->validation_errors = $validation_errors;
        return $this;
    }

    /**
     * @param array $conditions
     * @return AmazonMarketPlaceProduct
     */
    public function setConditions(array $conditions): AmazonMarketPlaceProduct
    {
        $this->conditions = $conditions;
        return $this;
    }

    /**
     * @return float
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * @param float $weight
     * @return AmazonMarketPlaceProduct
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;
        return $this;
    }

    /**
     * @return string
     */
    public function getRecommendedBrowseNodes()
    {
        return $this->recommended_browse_nodes;
    }

    /**
     * @param string $recommended_browse_nodes
     * @return AmazonMarketPlaceProduct
     */
    public function setRecommendedBrowseNodes($recommended_browse_nodes)
    {
        $this->recommended_browse_nodes = $recommended_browse_nodes;
        return $this;
    }

    /**
     * @return string
     */
    public function getFeedProductType()
    {
        return $this->feed_product_type;
    }

    /**
     * @param string $feed_product_type
     * @return AmazonMarketPlaceProduct
     */
    public function setFeedProductType($feed_product_type)
    {
        $this->feed_product_type = $feed_product_type;
        return $this;
    }

    /**
     * @param string $description
     * @return AmazonMarketPlaceProduct
     */
    public function setDescription(string $description)
    {
        $this->description = $description;
        return $this;
    }

    private $validation_errors = [];

    private $conditions = [
        'New',
        'Refurbished',
        'UsedLikeNew',
        'UsedVeryGood',
        'UsedGood',
        'UsedAcceptable'
    ];

    public function __construct(array $array = [])
    {
        foreach ($array as $property => $value) {
            $this->{$property} = $value;
        }
    }

    public function getValidationErrors()
    {
        return $this->validation_errors;
    }


    public function toArray($toEncoding = true)
    {
        /*'feed_product_type' => 'television',*/
        return array_merge(
            $this->other_attributes,
            [
                'feed_product_type' => $this->feed_product_type,
                'item_sku' => $this->sku,
                'external_product_id' => $this->product_id,
                'external_product_id_type' => $this->product_id_type,
                'brand_name' => $this->brand,
                'item_name' => $this->title,
                'product_description' => $this->description,
                "manufacturer" => $this->manufacturer,
                'standard_price' => $this->price,
                'sale_price' => $this->sale_price,
                'currency' => $this->currency,
                'quantity' => $this->quantity,
                'parent_child' => $this->parent_child,
                'relationship_type' => $this->parent_child == 'Child' ? 'Variation' : '',
                'parent_sku' => $this->parent_sku,
                'variation_theme' => $this->variation_theme,
                'generic_keywords' => $this->keywords,
                'color_name' => $this->color_name,
                'size_name' => $this->size_name,
                'merchant_shipping_group_name' => $this->shipping,
                'website_shipping_weight_unit_of_measure' => $this->weight,
                'recommended_browse_nodes' => $this->recommended_browse_nodes,
                'sale_from_date' => $this->sale_from_date,
                'sale_end_date' => $this->sale_end_date,
                'main_image_url' => array_key_exists(0, $this->image) ? $this->image[0] : '',
                'other_image_url1' => array_key_exists(1, $this->image) ? $this->image[1] : '',
                'other_image_url2' => array_key_exists(2, $this->image) ? $this->image[2] : '',
                'other_image_url3' => array_key_exists(3, $this->image) ? $this->image[3] : '',
                'other_image_url4' => array_key_exists(4, $this->image) ? $this->image[4] : '',
                'other_image_url5' => array_key_exists(5, $this->image) ? $this->image[5] : '',
                'other_image_url6' => array_key_exists(6, $this->image) ? $this->image[6] : '',
                'other_image_url7' => array_key_exists(7, $this->image) ? $this->image[7] : '',
                'other_image_url8' => array_key_exists(8, $this->image) ? $this->image[8] : '',
                'bullet_point1' => array_key_exists(0, $this->bullet_point) ? $this->bullet_point[0] : '',
                'bullet_point2' => array_key_exists(1, $this->bullet_point) ? $this->bullet_point[1] : '',
                'bullet_point3' => array_key_exists(2, $this->bullet_point) ? $this->bullet_point[2] : '',
                'bullet_point4' => array_key_exists(3, $this->bullet_point) ? $this->bullet_point[3] : '',
                'bullet_point5' => array_key_exists(4, $this->bullet_point) ? $this->bullet_point[4] : '',
            ]
        );
    }

    public function validate()
    {
        if (mb_strlen($this->sku) < 1 or strlen($this->sku) > 40) {
            $this->validation_errors['sku'] = 'Should be longer then 1 character and shorter then 40 characters';
        }

        $this->price = str_replace(',', '.', $this->price);

        $exploded_price = explode('.', $this->price);

        if (count($exploded_price) == 2) {
            if (mb_strlen($exploded_price[0]) > 18) {
                $this->validation_errors['price'] = 'Too high';
            } else {
                if (mb_strlen($exploded_price[1]) > 2) {
                    $this->validation_errors['price'] = 'Too many decimals';
                }
            }
        } else {
            $this->validation_errors['price'] = 'Looks wrong';
        }

        $this->quantity = (int)$this->quantity;
        $this->product_id = (string)$this->product_id;

        $product_id_length = mb_strlen($this->product_id);

        switch ($this->product_id_type) {
            case 'ASIN':
                if ($product_id_length != 10) {
                    $this->validation_errors['product_id'] = 'ASIN should be 10 characters long';
                }
                break;
            case 'UPC':
                if ($product_id_length != 12) {
                    $this->validation_errors['product_id'] = 'UPC should be 12 characters long';
                }
                break;
            case 'EAN':
                if ($product_id_length != 13) {
                    $this->validation_errors['product_id'] = 'EAN should be 13 characters long';
                }
                break;
            default:
                $this->validation_errors['product_id_type'] = 'Not one of: ASIN,UPC,EAN';
        }

        if (!in_array($this->condition_type, $this->conditions)) {
            $this->validation_errors['condition_type'] = 'Not one of: ' . implode($this->conditions, ',');
        }

        if ($this->condition_type != 'New') {
            $length = mb_strlen($this->condition_note);
            if ($length < 1) {
                $this->validation_errors['condition_note'] = 'Required if condition_type not is New';
            } else {
                if ($length > 1000) {
                    $this->validation_errors['condition_note'] = 'Should not exceed 1000 characters';
                }
            }
        }

        if (count($this->validation_errors) > 0) {
            return false;
        } else {
            return true;
        }
    }

    public function __set($property, $value)
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
    }

    /**
     * @param string $parent_sku
     * @return AmazonMarketPlaceProduct
     */
    public function setParentSku(string $parent_sku)
    {
        $this->parent_sku = $parent_sku;
        return $this;
    }

    /**
     * @param string $variation_theme
     * @return AmazonMarketPlaceProduct
     */
    public function setVariationTheme(string $variation_theme)
    {
        $this->variation_theme = $variation_theme;
        return $this;
    }

    /**
     * @param string $keywords
     * @return AmazonMarketPlaceProduct
     */
    public function setKeywords(string $keywords)
    {
        $this->keywords = $keywords;
        return $this;
    }

    /**
     * @param array $bullet_point
     * @return AmazonMarketPlaceProduct
     */
    public function setBulletPoint(array $bullet_point)
    {
        $this->bullet_point = $bullet_point;
        return $this;
    }

    /**
     * @param mixed $sale_price
     * @return AmazonMarketPlaceProduct
     */
    public function setSalePrice($sale_price)
    {
        $this->sale_price = $sale_price;
        return $this;
    }

    /**
     * @param string $currency
     * @return AmazonMarketPlaceProduct
     */
    public function setCurrency(string $currency)
    {
        $this->currency = $currency;
        return $this;
    }

    /**
     * @param string $color_name
     * @return AmazonMarketPlaceProduct
     */
    public function setColorName(string $color_name)
    {
        $this->color_name = $color_name;
        return $this;
    }

    /**
     * @param string $size_name
     * @return AmazonMarketPlaceProduct
     */
    public function setSizeName(string $size_name)
    {
        $this->size_name = $size_name;
        return $this;
    }

    /**
     * @param string $manufacturer
     * @return AmazonMarketPlaceProduct
     */
    public function setManufacturer(string $manufacturer)
    {
        $this->manufacturer = $manufacturer;
        return $this;
    }

    /**
     * @param string $sale_from_date
     * @return AmazonMarketPlaceProduct
     */
    public function setSaleFromDate($sale_from_date)
    {
        $this->sale_from_date = $sale_from_date;
        return $this;
    }

    /**
     * @param string $sale_end_date
     * @return AmazonMarketPlaceProduct
     */
    public function setSaleEndDate($sale_end_date)
    {
        $this->sale_end_date = $sale_end_date;
        return $this;
    }

    /**
     * @param array $other_attributes
     * @return AmazonMarketPlaceProduct
     */
    public function setOtherAttributes(array $other_attributes)
    {
        $this->other_attributes = $other_attributes;
        return $this;
    }

    /**
     * @param string $parent_child
     * @return AmazonMarketPlaceProduct
     */
    public function setParentChild($parent_child)
    {
        $this->parent_child = $parent_child;
        return $this;
    }
}
