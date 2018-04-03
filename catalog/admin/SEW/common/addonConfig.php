<?php
/**
-
  wrapper for osc config requirements for addons / upgrades

  Author John Ferguson (@BrockleyJohn) john@sewebsites.net
  
	copyright  (c) 2017 SEwebsites

 * specialisations carry the actual configuration entries needed
 * installs the required configs into the osc database
 * caters for adding in extra ones in an update
 */
namespace SEWC;

class addonConfig
{
	protected $GROUP_NAME; // pseudo-constant set in child declarations - it's the name of the configuration group for the addon
	protected $GROUP_DESC; // ditto
	protected $group_id;
	protected $keys;

	protected function getConfigGroup()
	{
		if (!strlen(tep_db_input($this->GROUP_NAME))) return false;
		$cfg_grp_query = tep_db_query('SELECT * FROM ' . TABLE_CONFIGURATION_GROUP . ' WHERE configuration_group_title = "' . tep_db_input($this->GROUP_NAME) . '"');
		if (tep_db_num_rows($cfg_grp_query)) {
			$cfg_grp_row = tep_db_fetch_array($cfg_grp_query);
			$cfg_grp_id = $cfg_grp_row['configuration_group_id'];
		} else {
			$cfg_grp_query = tep_db_query('SELECT MAX(sort_order) AS last_sort FROM ' . TABLE_CONFIGURATION_GROUP);
			$cfg_grp_row = tep_db_fetch_array($cfg_grp_query);
			$sql_data_array = array('configuration_group_title' => tep_db_input($this->GROUP_NAME), 'sort_order' => $cfg_grp_row['last_sort'] + 1);
			if ( tep_not_null(tep_db_input($this->GROUP_DESC)) ) $sql_data_array['configuration_group_description'] = tep_db_input($this->GROUP_DESC);
			tep_db_perform(TABLE_CONFIGURATION_GROUP,$sql_data_array);
			$cfg_grp_id = tep_db_insert_id();
		}
		return $cfg_grp_id;
	}
	
	public function checkConfigs($check_name)
	{
		// use passed setting as a check if installation is needed
		$done = false;
		if (!defined($check_name)) {
			$this->install();
			$done = DEFAULT_SETTINGS_INSTALLED;
		} else {
			$configs = array_keys($this->keys);
			foreach ($configs as $config) {
				if (! defined($config)) {
					$this->install($config);
					$done = EXTRA_DEFAULTS_INSTALLED;
				}
			}
		}
		return $done;
	}
	
	public function getSettingsFields()
	{ // return the fields to set all the vars (support an addon settings page)
		$keys = '';
		foreach ($this->keys as $key => $value) {
			$keys .= '<strong>' . $value['title'] . '</strong><br />' . $value['desc'] . '<br />';
			if (defined($key)) {
				$current = constant($key);
			} else {
				$current = $value['value'];
			}
			
			if ($value['set_function']) {
			  eval('$keys .= ' . $value['set_function'] . "'" . $current . "', '" . $key . "');");
			} else {
			  $keys .= tep_draw_input_field($key, $current);
			}
			
			$keys .= '<br /><br />';
		}
		return $keys;
	}
	
	protected function install($config = null)
	{ // install passed setting or all of them
		$configs = $this->keys;
		$sort = 1;
			if (isset($config)) {
			  $cfg_query = tep_db_query('SELECT MAX(sort_order) AS last_sort FROM ' . TABLE_CONFIGURATION . ' WHERE configuration_group_id = ' . $this->group_id);
			  $cfg_row = tep_db_fetch_array($cfg_query);
				$sort = $cfg_row['last_sort'] + 1;
		  if (isset($configs[$config])) {
			$configs = array($config => $configs[$config]);
		  } else {
			$configs = array();
		  }
		}
		foreach ($configs as $key => $data) {
		  $sql_data_array = array('configuration_key' => $key,
								  'configuration_value' => (isset($data['value']) ? $data['value'] : ''),
								  'sort_order' => $sort,
								  'date_added' => 'now()');
	
		  if (isset($data['hidden'] && $data['hidden'] === true)) {
				$sql_data_array['configuration_group_id'] = 6;
		  } else {
				$sql_data_array['configuration_group_id'] = $this->config_group;
			}
		  if (isset($data['title'])) {
				$sql_data_array['configuration_title'] = $data['title'];
		  }
		  if (isset($data['desc'])) {
				$sql_data_array['configuration_description'] = $data['desc'];
		  }
		  if (isset($data['set_func'])) {
				$sql_data_array['set_function'] = $data['set_func'];
		  }
		  if (isset($data['use_func'])) {
				$sql_data_array['use_function'] = $data['use_func'];
		  }
		  tep_db_perform(TABLE_CONFIGURATION, $sql_data_array);
				$sort++;
		}
	}
	
/*	protected function getConfigs() 
	{ // Define this in child (it's the point). Template:
		return array(
			'CONFIG_PARAM' => array(
						 'title' => 'name to be used for it in admin',
						 'desc' => 'explanation to be used in admin',
						 'value' => 'True',
						 'use_func' => 'sew_use_me',
						 'set_func' => 'tep_cfg_select_option(array(\'true\', \'false\'), '),
						 'hidden' => false,
		);
	
	} */
}