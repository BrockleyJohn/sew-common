<?php
/**
-
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
  protected $MODEL_VERSION; // pseudo-constant set in child declarations
  protected $MODEL_VERSION_VAR; // ditto
  protected $MODEL_VERSION_VAR_TITLE; // and again
	protected $tables;

	public function __construct()
	{
	  if (! defined($this->MODEL_VERSION_VAR) || constant($this->MODEL_VERSION_VAR) <> $this->MODEL_VERSION ) {
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
		if (defined($this->MODEL_VERSION_VAR)) {
		  $this->checkData();
		  tep_db_query('UPDATE ' . TABLE_CONFIGURATION . ' SET configuration_value = "' . $this->MODEL_VERSION . '" WHERE configuration_key = "' . $this->MODEL_VERSION_VAR . '"');
		} else {
        $sql_data_array = array('configuration_title' => '',
                                'configuration_key' => $this->MODEL_VERSION_VAR,
                                'configuration_value' => $this->MODEL_VERSION,
                                'configuration_description' => $this->MODEL_VERSION_VAR_TITLE,
                                'configuration_group_id' => '6',
                                'date_added' => 'now()');
        tep_db_perform(TABLE_CONFIGURATION, $sql_data_array);
		}
	}
	
	protected function checkTable($table)
	{
	  $sql = '';
		$query = tep_db_query('DESCRIBE ' . $table);
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
			}
		}
		if (strlen($sql)) tep_db_query($sql);
		$sql = '';
		$query = tep_db_query('SHOW INDEX FROM ' . $table);
		$keys = array();
		while ($row = tep_db_fetch_array($query)) {
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
		if (strlen($sql)) tep_db_query($sql);
	}
	
	protected function installTable($table)
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
		tep_db_query($sql);
		if (array_key_exists('data',$this->tables[$table])) {
		  tep_db_query($this->tables[$table]['data']);
		}
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