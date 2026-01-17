<?php
/**
-
	v2.2 Jan 2026 reinstate tablecols static method
	v2.1 introduce override of defaults for table type, charset and collation
  v2.0 finally revise for consts and static Dec 2020
  v1.1 remove table constants September 2020
  
  wrapper for data model requirements for addons / upgrades

  Author John Ferguson (@BrockleyJohn) john@sewebsites.net
  
	copyright  (c) 2017 SEwebsites

 * specialisations carry the actual model extensions and version vars
 * installs the required model extensions into the osc database
 * caters for adding in extra ones in an update
 */
namespace SEWC;

class dataModel
{
/*  const MODEL_VERSION...; // the addon model version the files need
  const MODEL_VERSION_VAR...; // the name of the config var it's saved in
  const MODEL_VERSION_VAR_TITLE...; // title for the config var 
*/
	protected $tables;
	const TABLE_TYPE = 'InnoDB'; // set if you want to override the default table type, i.e. 'InnoDB' or 'MyISAM'
	const TABLE_CHARSET = 'utf8'; // set if you want to override the default column charset, e.g. 'utf8mb4' or 'utf8'
	const TABLE_COLLATE = 'utf8_unicode_ci'; // set if you want to override the default column collation, e.g. 'utf8mb4_unicode_ci' or 'utf8_general_ci'

	public static function tablecols($table)
	{
	  $model = new static();
	  return array_keys($model->defineTables()[$table]['columns']);
	}

	public function __construct()
	{
	  if (! defined(static::MODEL_VERSION_VAR) || constant(static::MODEL_VERSION_VAR) <> static::MODEL_VERSION ) {
		  $this->check();
		}
	}
	
	protected function check()
	{
	  $this->tables = $this->defineTables();
		if (count($this->tables)) {
			foreach ($this->tables as $table => $def) {
				$exists = tep_db_num_rows(tep_db_query('SHOW TABLES LIKE "' . $table . '"'));
				if (! $exists) {
					$this->installTable($table);
				} else {
					$this->checkTable($table);
				}
			}
		}
		if (defined(static::MODEL_VERSION_VAR)) {
		  $this->checkData();
		  tep_db_query('UPDATE configuration SET configuration_value = "' . static::MODEL_VERSION . '" WHERE configuration_key = "' . static::MODEL_VERSION_VAR . '"');
		} else {
        $sql_data_array = array('configuration_title' => '',
                                'configuration_key' => static::MODEL_VERSION_VAR,
                                'configuration_value' => static::MODEL_VERSION,
                                'configuration_description' => static::MODEL_VERSION_VAR_TITLE,
                                'configuration_group_id' => '6',
                                'date_added' => 'now()');
        tep_db_perform('configuration', $sql_data_array);
		}
	}

	public function checkOneTable($table, $link = 'db_link')
	{
	  $this->tables = $this->defineTables();
		if (isset($this->tables[$table])) {
			$exists = tep_db_num_rows(tep_db_query('SHOW TABLES LIKE "' . $table . '"', $link));
			if (! $exists) {
				$this->installTable($table, $link);
			} else {
				$this->checkTable($table, $link);
			}
		} else {
			error_log("key $table not set in " . print_r($this->tables, true));
		}
	}
	
	protected function checkTable($table, $link = 'db_link')
	{
	  $sql = '';
		$query = tep_db_query('DESCRIBE ' . $table, $link);
		$cols = array();
		while ($row = tep_db_fetch_array($query)) {
		  $cols[] = $row['Field'];
		}
		foreach ($this->tables[$table]['columns'] as $col => $def) {
		  if (! in_array($col,$cols)) {
				if (! strlen($sql)) {
					$sql = 'ALTER TABLE `' . $table . '` ADD COLUMN `' . $col . '` ' . $def;
				} else {
					$sql .= ',
		ADD COLUMN `' . $col . '` ' . $def;
				}
				if (strlen(static::TABLE_CHARSET)) {
					$sql .= ' CHARACTER SET ' . static::TABLE_CHARSET;
				}
				if (strlen(static::TABLE_COLLATE)) {
					$sql .= ' COLLATE ' . static::TABLE_COLLATE;
				}
			}
		}
		if (strlen($sql)) tep_db_query($sql, $link);
		$sql = '';
		$query = tep_db_query('SHOW INDEX FROM ' . $table, $link);
		$keys = array();
		while ($row = tep_db_fetch_array($query, $link)) {
			$keys[] = $row['Key_name'];
		}
		if ((!in_array('PRIMARY',$keys)) && array_key_exists('primary_key',$this->tables[$table])) {
			$sql = 'ALTER TABLE `' . $table . '` ADD PRIMARY KEY (`' . $this->tables[$table]['primary_key'] . '`)';
		}
		if (array_key_exists('keys',$this->tables[$table])) {
		  foreach($this->tables[$table]['keys'] as $name => $columns) {
				if (! in_array($name,$keys))
					if (strlen($sql)) {
					  $sql .= ',
		ADD KEY `' . $name . '` (`' . $columns . '`)';
		      } else {
			      $sql = 'ALTER TABLE `' . $table . '` ADD KEY `' . $name . '` (`' . $columns . '`)';
					}
			}
		}
		if (strlen($sql)) tep_db_query($sql, $link);
	}
	
	protected function installTable($table, $link = 'db_link')
	{
	  $sql = 'CREATE TABLE `' . $table . '` (
		';
		foreach ($this->tables[$table]['columns'] as $col => $def) {
		  $sql .= '`' . $col . '` ' . $def . ',
		';
		}
		$sql .= 'PRIMARY KEY (' . $this->tables[$table]['primary_key'] . ')';
		if (array_key_exists('keys',$this->tables[$table])) {
		  foreach($this->tables[$table]['keys'] as $name => $columns) {
			  $sql .= ',
		KEY `' . $name . '` (' . $columns . ')';
			}
		}
		$sql .= ')';
		if (strlen(static::TABLE_TYPE)) {
		  $sql .= ' ENGINE=' . static::TABLE_TYPE;
		}
		if (strlen(static::TABLE_CHARSET)) {
		  $sql .= ' CHARACTER SET ' . static::TABLE_CHARSET;
		}
		if (strlen(static::TABLE_COLLATE)) {
		  $sql .= ' COLLATE ' . static::TABLE_COLLATE;
		}
		tep_db_query($sql, $link);
		if (array_key_exists('data',$this->tables[$table])) {
		  tep_db_query($this->tables[$table]['data'], $link);
		}
	}

	public function read()
	{
		$this->defineTables();
	}
	
	protected function defineTables()
	{
	  return array();
	}
	
	protected function checkData()
	{
	  return;
	}
	
}