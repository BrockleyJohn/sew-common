<?php
/**
-
  wrapper for data model requirements for easify v4 interface

  Author John Ferguson (@BrockleyJohn) john@sewebsites.net
  
	copyright  (c) 2017 SEwebsites

 * contains all model differences since Gold
 * supports automated check for any missing & application
 */
namespace SEWC;

class oscModel extends dataModel
{
  protected $MODEL_VERSION = '234.1';
  protected $MODEL_VERSION_VAR = 'OSC_MODEL_VERSION';
  protected $MODEL_VERSION_VAR_TITLE = 'osC Model Version';
	
  protected function defineTables()
	{
		$model_here = array(
		  'categories_description' => array(
			  'columns' => array(
          'categories_seo_description' => 'TEXT NULL',
          'categories_seo_keywords' => 'VARCHAR(128) NULL',
          'categories_seo_title' => 'VARCHAR(128) NULL',
				),
			),
		  'manufacturers_info' => array(
			  'columns' => array(
          'manufacturers_seo_description' => 'TEXT NULL',
          'manufacturers_seo_keywords' => 'VARCHAR(128) NULL',
          'manufacturers_seo_title' => 'VARCHAR(128) NULL',
				),
			),
		  'products' => array(
			  'columns' => array(
          'products_gtin' => 'CHAR(14) NULL',
				),
			),
		  'products_description' => array(
			  'columns' => array(
          'products_seo_description' => 'TEXT NULL',
          'products_seo_keywords' => 'VARCHAR(128) NULL',
          'products_seo_title' => 'VARCHAR(128) NULL',
				),
			),
		  'testimonials' => array(
			  'columns' => array(
          'testimonials_id' => 'int NOT NULL AUTO_INCREMENT',
          'customers_name' => 'varchar(255) NOT NULL',
          'date_added' => 'datetime',
          'last_modified' => 'datetime',
          'testimonials_status' => 'tinyint(1) NOT NULL default \'1\'',
				),
				'primary_key' => 'testimonials_id',
			),
		  'testimonials_description' => array(
			  'columns' => array(
          'testimonials_id' => 'int NOT NULL',
          'languages_id' => 'int NOT NULL',
          'testimonials_text' => 'text NOT NULL',
				),
				'primary_key' => 'testimonials_id,languages_id',
			),
		);
//		echo '<pre>'.print_r(array_merge($lookup_model,$model_here));
//		exit;
		return $model_here;
	}
	
}