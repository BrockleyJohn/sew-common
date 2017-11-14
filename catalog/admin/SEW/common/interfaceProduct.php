<?php
/**
-
  wrapper for osCommerce product for interface use

  Author John Ferguson (@BrockleyJohn) john@sewebsites.net
  
	copyright  (c) 2017 SEwebsites

 * 
 * 
 * 
 */
namespace SEWC;

class interfaceProduct
{
  public $id;
	public $name;
	public $description;
	public $quantity;
	public $model;
	public $image;
	public $price;
	public $weight;
	public $status;
	public $taxId;
	public $gtin;
	public $manufacturerId;
	public $url;
	protected $languageId;
	protected $productFields = array();
	protected $descriptionFields = array();
	
	public __construct()
	{ // set default language id
	  
	}
	
	public function setId($value)
	{
	  $this->productFields['products_id'] = $value;
	}
	public function setName($value)
	{
	  $this->descriptionFields[$this->languageId]['products_name'] = $value;
	}
	public function setDescription($value)
	{
	  $this->descriptionFields[$this->languageId]['products_description'] = $value;
	}
	public function setUrl($value)
	{
	  $this->descriptionFields[$this->languageId]['products_url'] = $value;
	}
	public function setModel($value)
	{
	  $this->productFields['products_model'] = $value;
	}
	public function setImage($value)
	{
	  $this->productFields['products_image'] = $value;
	}
	public function setPrice($value)
	{
	  $this->productFields['products_price'] = $value;
	}
	public function setWeight($value)
	{
	  $this->productFields['products_weight'] = $value;
	}
	public function setStatus($value)
	{
	  $this->productFields['products_status'] = $value;
	}
	public function setTaxId($value)
	{
	  $this->productFields['products_tax_class_id'] = $value;
	}
	public function setManufacturerId($value)
	{
	  $this->productFields['manufacturers_id'] = $value;
	}
	public function setGtin($value)
	{
	  $this->productFields['products_gtin'] = $value;
	}
}
