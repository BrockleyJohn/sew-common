<?php
/**
-
  wrapper for osc config requirements for addons / upgrades
  
  v2.0 Phoenix compatibility Dec 2020

  Author John Ferguson (@BrockleyJohn) oscommerce@sewebsites.net
  
	copyright  (c) 2017 SEwebsites

 * specialisations carry the actual configuration entries needed
 * installs the required configs into the osc database
 * caters for adding in extra ones in an update
 */
namespace SEWC;

class addonConfig
{
  /* const GROUP_NAME...; // name of the configuration group for the addon
	const GROUP_DESC...; // ditto 
	const VERSION_VAR...; // if it's different then configs differ 
    const FILE_VERSION..; // check when files change
  */
  protected $group_id;
  protected $keys;
  protected $versioned = false;
  
  protected function __construct()
  {
    $this->group_id = $this->getConfigGroup();
    $this->keys = $this->getConfigs();
    $class = get_called_class();
    $const = '\\' . $class . '::VERSION_VAR';
    if (defined($const)) {
      $this->versioned = true;
      $this->keys[static::VERSION_VAR] = [
        'title' => 'Addon version',
        'desc' => 'Used to manage automatic updates',
        'value' => static::FILE_VERSION,
        'use_func' => '\\' . $class . '::useVersion',
        'set_func' => '\\' . $class . '::uninstallOptions('
      ];
      if ((! defined(static::VERSION_VAR)) || constant(static::VERSION_VAR) != static::FILE_VERSION) {
        $done = $this->checkConfigs(array_keys($this->keys)[0]);
//php7.3  $done = $this->checkConfigs(array_key_first($this->keys));
        if ($done && basename($GLOBALS['PHP_SELF']) == 'configuration.php') {
          $GLOBALS['messageStack']->add(CONFIGS_ADDED_PLEASE_CHECK);
        }
      }
    }
  }
  
  public static function useVersion($var)
  // config use function for addon version var:
  // check for latest version & update if necessary
  // display current version and uninstall button
  {
    $class = '\\' . get_called_class();
    $cfg = new $class(); // instantiates which checks for updates
    return sprintf(ADDON_VERSION_UNINSTALL, $var);
  }
  
  public static function uninstallOptions($var)
  // config set function for addon version var:
  // uninstall button(s)
  {
    $class = '\\' . get_called_class();
    $cfg = new $class(); // instantiates which checks for updates
    return '<a title="coming soon">[ Remove settings ] [ Remove settings and database changes ]</a>';
  }
  
  public function shout()
  // handy for testing!
  {
    return 'oi oi!';
  }

  protected function getConfigGroup()
  {
    if (!strlen(tep_db_input(static::GROUP_NAME))) return false;
    $cfg_grp_query = tep_db_query('SELECT * FROM configuration_group WHERE configuration_group_title = "' . tep_db_input(static::GROUP_NAME) . '"');
    if (tep_db_num_rows($cfg_grp_query)) {
      $cfg_grp_row = tep_db_fetch_array($cfg_grp_query);
      $cfg_grp_id = $cfg_grp_row['configuration_group_id'];
    } else {
      $cfg_grp_query = tep_db_query('SELECT MAX(sort_order) AS last_sort FROM configuration_group');
      $cfg_grp_row = tep_db_fetch_array($cfg_grp_query);
      $sql_data_array = array('configuration_group_title' => tep_db_input(static::GROUP_NAME), 'sort_order' => $cfg_grp_row['last_sort'] + 1);
      if ( tep_not_null(tep_db_input(static::GROUP_DESC)) ) $sql_data_array['configuration_group_description'] = tep_db_input(static::GROUP_DESC);
      tep_db_perform('configuration_group', $sql_data_array);
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
          if ($done && $this->versioned) {
            \sewc::set_config_var(static::VERSION_VAR, static::FILE_VERSION);
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
	
	protected function startConfigDialog($title,$button,$action)
	{
		$return = '<script><!--
	$(document).ready(function() {
	
		$( "#config_dbox" ).dialog({
			autoOpen: false,
			title: "'.$title.'",
			width : "auto",
			height : 350,
			position : { my: "top", at: "top", of: "#contentText" }
		});
	
		$(".config_settings").click(function() {
			$("#config_dbox").dialog("open");
		});
	
		$(".config_button").click(function() {
			$("#config_dbox").dialog("close");
		//	$("#config_form").submit();
		});
	
	}); //end of document ready function
	//--></script>
	';
		$return .= '</form><span class="config_settings">' . tep_draw_button($button, 'gear','','',array('type'=>'reset'))."</span>\n";
		$return .= '<div id="config_dbox"><style type="text/css" scoped>.center {text-align: center;} '.\sewc::ajax_styles().'</style>'.tep_draw_form('config_form', 'sew_utils.php', '' , 'post', 'enctype="multipart/form-data" id="config_form"').tep_draw_hidden_field('action',$action).tep_draw_hidden_field('return','configuration.php').tep_draw_hidden_field('param_string','gID='.$this->group_id.(isset($_GET['cID']) ? '&cID='.$_GET['cID'] : ''));
	  return $return;
	}
	
	protected function endConfigDialog()
	{
		return '<span class="config_button">' . tep_draw_button(IMAGE_SAVE, 'disk').'</span><span id="result"></span><span id="progress"></span></div>';
	}
	
	protected function unpackBangPipe($key)
	{ // break into arrays format type1!0!1!2|type2!3!4!5!6|type3!7|type4
	  $return = array();
		if (defined($key)) {
		  $rows = explode('|',constant($key));
			if (count($rows)) {
			  foreach ($rows as $row) {
				  $map = explode('!',$row); // [0] type [1+] order statuses
					$values = array();
					for ($i = 1; $i < $n = count($map); $i++) {
						$values[] = $map[$i];
					}
					$return[$map[0]] = $values;
				}
			}
		}
		return $return;
	}
	
	protected function setVar($key,$value)
	{
	  return tep_db_query('UPDATE ' . 'configuration' . ' SET configuration_value = "' . tep_db_input($value) . '" WHERE configuration_key = "' . tep_db_input($key) . '"');
	}
	
	protected function install($config = null)
	{ // install passed setting or all of them
		$configs = $this->keys;
		$sort = 1;
		$cfg_query = tep_db_query('SELECT MAX(sort_order) AS last_sort FROM ' . 'configuration' . ' WHERE configuration_group_id = ' . $this->group_id);
		$cfg_row = tep_db_fetch_array($cfg_query);
		$sort = $cfg_row['last_sort'] + 1;
		if (isset($config)) {
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
	
		  if (isset($data['hidden']) && $data['hidden'] === true) {
				$sql_data_array['configuration_group_id'] = 6;
		  } else {
				$sql_data_array['configuration_group_id'] = $this->group_id;
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
		  tep_db_perform('configuration', $sql_data_array);
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