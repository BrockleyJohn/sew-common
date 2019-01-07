<?php
/**
-
  wrapper for data model requirements for easify v4 interface

  Author John Ferguson (@BrockleyJohn) john@sewebsites.net
  
	copyright  (c) 2017 SEwebsites

 * installs the required config options into the osc database
 * caters for adding in extra ones in an update
 */
namespace SEWC;

class distroModel extends \SEWC\dataModel
{
  protected $MODEL_VERSION = '1.04';
  protected $MODEL_VERSION_VAR = 'SEW_DISTRO_MODEL_VERSION';
  protected $MODEL_VERSION_VAR_TITLE = 'Addon Distribution Model Version';
	
  protected function defineTables()
	{
		return array(
			'sew_addons' => array(
				'columns' => array(
					'version_id' => 'int(11) NOT NULL AUTO_INCREMENT',
					'app_code' => 'varchar(12) NOT NULL',
					'app_key' => 'varchar(128) NOT NULL',
					'version_name' => 'varchar(12) NOT NULL',
					'version_title' => 'varchar(36) NOT NULL',
					'version_author' => 'varchar(256)',
					'pro_cost' => 'DECIMAL (4,2)',
					'version_data' => 'TEXT',
					'last_check' => 'datetime DEFAULT NULL',
					'update_available' => 'TINYINT(1) NOT NULL DEFAULT 0',
					'version_available' => 'varchar(12) NULL',
					'pro_installed' => 'TINYINT(1) NOT NULL DEFAULT 0',
					'pro_build_ip' => 'varchar(36)',
				),
				'primary_key' => 'version_id',
				'keys' => array('idx_app_code' => 'app_code')
			),
		);
		
	}
}