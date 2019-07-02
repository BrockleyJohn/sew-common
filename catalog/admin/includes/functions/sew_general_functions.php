<?php
/**
-
	functions for configuration settings used across multiple addons
	(instead of updating functions/general.php)
	formatting functions required across admin
  
  namespace autoloader for SEwebsites common classes SEWC

  v2.0 29/5/18 updated for EZV4 and featured specials
  
  Author John Ferguson (@BrockleyJohn) oscommerce@sewebsites.net
  
	copyright  (c) 2018 SEwebsites

 *
 */

// SEWC namespace autoloader
spl_autoload_register(function ($class) {

    $prefix = 'SEWC\\';

    // base directory for the namespace prefix
    $base_dir = DIR_FS_ADMIN . 'SEW/common/';

    // does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // no, move to the next registered autoloader
        return;
    }

    // get the relative class name
    $relative_class = substr($class, $len);

    // replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // if the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});


	// Function to suppress view/edit processing for certain vars
  if( !function_exists( 'sew_cfg_do_nothing' ) ) {
    function sew_cfg_do_nothing() {
      return '';
    }
  }
	
	// Function to process view processing for certain vars
  if( !function_exists( 'sew_cfg_piped_array' ) ) {
    function sew_cfg_piped_array($var) {
      return implode(", ", explode('|', $var));
    }
  }
	
	// Function used within addons to set a config var to the passed value
	function sew_set_config_var($key, $value) {
		if (tep_not_null($key)) {
			$sql_data_array = array('configuration_value' => tep_db_input($value));
			$check_query = tep_db_query('select configuration_key from ' . TABLE_CONFIGURATION . ' where configuration_key = "' . tep_db_input($key) . '"');
			if (tep_db_num_rows($check_query)) {
				tep_db_perform(TABLE_CONFIGURATION,$sql_data_array,'update','configuration_key="' . tep_db_input($key) . '"');
				return true;
			} else {
				return false;
			}
		}
	}
	
	// Function used to unpack a var containing nested keyed arrays with three separators eg. type1;0,1,2|type2;3,4,5,6|type3;7,8,9
	function sew_unpack_nested_config_var($var,$sep1 = '|',$sep2 = ';',$sep3 = ',') {
		$return = array();
		if (defined($var)) {
			$retun = sew_unpack_nested_value(constant($var),$sep1,$sep2,$sep3);
		}
		return $return;
	}
	
	// Function used to unpack a value containing nested keyed arrays with up to three separators eg. type1;0,1,2|type2;3,4,5,6|type3;7,8,9
	function sew_unpack_nested_value($val,$sep1 = '|',$sep2 = ';',$sep3 = ',') {
		$return = array();
		if (tep_not_null($val)) {
			$unpack_set = explode($sep1,$val);
			for ($i = 0; $i < $n = count($unpack_set); $i++) {
				$unpack_type = explode($sep2,$unpack_set[$i]);
				if (strpos($val,$sep3)) {
					$return[$unpack_type[0]] = explode($sep3,$unpack_type[1]);
				} else {
					$return[$unpack_type[0]] = $unpack_type[1];
				}
			}
		}
		return $return;
	}
	
	// Generalised function for jquery processing general config vars that are an imploded array 
	/* example use
	function sew_cfg_ez_update_rules($values) {
		$possibles = array (
			'products_quantity',
			'products_price',
			'manufacturers_id',
			'products_gtin',
			'products_model',
			'products_weight',
			'products_tax_class_id',
			'products_name',
			'products_description'
		);
		return sew_cfg_chkbox_array($values, 'EASIFY_PRODUCT_UPDATE_RULES', $possibles, '|');
	}
	*/
	function sew_cfg_chkbox_array($values, $possibles, $separator = ';') {
		$value_array = explode($separator,$values);
		$output = '';
		foreach ($possibles as $value) {
		  $output .= tep_draw_checkbox_field('possible_value[]', $value, in_array($value, $value_array)) . '&nbsp;' . tep_output_string($value) . '<br />';
		}

		if (!empty($output)) {
		  $output = '<br />' . substr($output, 0, -6);
		}

		$output .= tep_draw_hidden_field('configuration_value', '', 'id="cfg_key"');
	
		$output .= '<script>
					function sew_cfg_array_value() {
					  var cfg_selected_values = \'\';
	
					  if ($(\'input[name="possible_value[]"]\').length > 0) {
						$(\'input[name="possible_value[]"]:checked\').each(function() {
						  cfg_selected_values += $(this).attr(\'value\') + \'' . $separator . '\';
						});
	
						if (cfg_selected_values.length > 0) {
						  cfg_selected_values = cfg_selected_values.substring(0, cfg_selected_values.length - 1);
						}
					  }
	
					  $(\'#cfg_key\').val(cfg_selected_values);
					}
	
					$(function() {
					  sew_cfg_array_value();
	
					  if ($(\'input[name="possible_value[]"]\').length > 0) {
						$(\'input[name="possible_value[]"]\').change(function() {
						  sew_cfg_array_value();
						});
					  }
					});
					</script>';
	
		return $output;

	}

	// Function to flip a 2d array
	function sew_2d_flip($in) {
		if (is_array($in) && count($in)) {
			$out = array();
			foreach ($in as $key => $values) {
				if (is_array($values)) {
					for ($i = 0; $i < $n = count($values); $i++) {
						$out[(string)$values[$i]] = $key;
					}
				} else {
					$out[(string)$values] = $key;
				}
			}
			return $out;
		} else {
			return $in;
		}
	}
	

	// return all possible orders_status
	function sew_get_orders_statuses() {
	  global $languages_id;

    $statuses_array = array();
    $statuses_query = tep_db_query("select orders_status_id, orders_status_name from " . TABLE_ORDERS_STATUS . " where language_id = '" . (int)$languages_id . "' order by orders_status_name");
    while ($statuses = tep_db_fetch_array($statuses_query)) {
      $statuses_array[] = array('id' => $statuses['orders_status_id'],
                                'text' => $statuses['orders_status_name']);
    }
		return $statuses_array;
	}
	
  // Function returns list of top level osc categories
  function sew_cfg_pull_down_top_categories($id, $key = '') {
    global $languages_id;
    $name = (($key) ? 'configuration[' . $key . ']' : 'configuration_value');
    $list_array = array(array('id' => '0', 'text' => TEXT_TOP));
    $list_query = tep_db_query("SELECT categories_id, categories_name FROM " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd WHERE c.parent_id = 0 AND cd.categories_id = c.categories_id AND cd.language_id = '" . (int)$languages_id . "' ORDER BY categories_name");
    while ($items = tep_db_fetch_array($list_query)) {
      $list_array[] = array('id' => $items['categories_id'],
                            'text' => $items['categories_name']);
    }
    return tep_draw_pull_down_menu($name, $list_array, $id);
	}
	
  // Function returns a list of installed payment methods for drop-down
	// works in admin language
	function sew_payment_module_drop_down() {
		$modules = sew_cfg_get_installed_payment_methods();
		$list_array = array();
		for ($i = 0; $i < $n = count($modules); $i++) {
			$list_array[] = array('id' => $modules[$i]['code'],
								'text' => $modules[$i]['title']);
		}
		return $list_array;
	}  

  // Function returns an array of details on installed shipping methods
	// works in admin language
	function sew_cfg_get_installed_shipping_methods($language = null) {
		return sew_cfg_get_installed_modules('shipping',$language);
	}
	
  // Function returns an array of details on installed payment methods
	// works in admin language
	function sew_cfg_get_installed_payment_methods($language = null) {
		return sew_cfg_get_installed_modules('payment',$language);
	}

  // Function returns an array of details on installed modules of type
	// works in admin language
	function sew_cfg_get_installed_modules($set, $module_language = null) {
	  global $language, $cfgModules;
		$return = array();
		if (is_null($module_language)) $module_language = $language;
    $modules = $cfgModules->getAll();
		$module_type = $cfgModules->get($set, 'code');
		$module_directory = $cfgModules->get($set, 'directory');
		$module_language_directory = $cfgModules->get($set, 'language_directory');
		$module_key = $cfgModules->get($set, 'key');;
    $modules_installed = (defined($module_key) ? explode(';', constant($module_key)) : array());

    $file_extension = '.php';
		$directory_array = array();
		if ($dir = @dir($module_directory)) {
			while ($file = $dir->read()) {
				if (!is_dir($module_directory . $file)) {
					if (substr($file, strrpos($file, '.')) == $file_extension) {
            if (in_array($file, $modules_installed)) {
              $directory_array[] = $file;
						}
					}
				}
			}
			sort($directory_array);
			$dir->close();
		}

		for ($i=0, $n=sizeof($directory_array); $i<$n; $i++) {
			$file = $directory_array[$i];
	
			include_once($module_language_directory . $module_language . '/modules/' . $module_type . '/' . $file);
			include_once($module_directory . $file);
	
			$class = substr($file, 0, strrpos($file, '.'));
			if (tep_class_exists($class)) {
				$module = new $class;
				if ($module->check() > 0) { // is it really installed?

					$module_info = array('code' => $module->code,
															 'title' => $module->title,
															 'status' => ($module->enabled ? 1 : 0),
															 'public' => (isset($module->public_title) ? $module->public_title : $module->title)
												);

          $return[] = $module_info;
				}
			}
		}
		return $return;
	}
	
	function sew_load_content_module($name, $group, $module_language = null) {
	  global $language;
	  
	  if (tep_class_exists($name)) return new $name();
	  if (is_null($module_language)) $module_language = $language;
	  $file = $name.'.php';

	  if (file_exists(DIR_FS_CATALOG_MODULES . 'content/' . $group . '/' . $file)) {
        if ( file_exists(DIR_FS_CATALOG_LANGUAGES . $module_language . '/modules/content/' . $group . '/' . $file) ) {
          include(DIR_FS_CATALOG_LANGUAGES . $module_language . '/modules/content/' . $group . '/' . $file);
        }

        include(DIR_FS_CATALOG_MODULES . 'content/' . $group . '/' . $file);
	    if (tep_class_exists($name)) return new $name();
	  }
	}
	
	function sew_get_product_images($id) {
		$files = [];
		if ((int)$id > 0) {
			$query = tep_db_query('select p.products_image, pi.image from products p left join products_images pi on pi.products_id = p.products_id where p.products_id = "' . (int)$id . '"');
			while ($row = tep_db_fetch_array($query)) {
			  if (count($files) == 0) {
			    $files[] = $row['products_image'];
			  }
			  $files[] = $row['image'];
			}
		}
		return $files;
	}
	
		if ($dir = @dir($module_directory)) {
			while ($file = $dir->read()) {
				if (!is_dir($module_directory . $file)) {
					if (substr($file, strrpos($file, '.')) == $file_extension) {
            if (in_array($file, $modules_installed)) {
              $directory_array[] = $file;
						}
					}
				}
			}
			sort($directory_array);
			$dir->close();
		}

	function sew_files_in_image_dir($path, $add_dir = false) {
		$files = [];
		if ($dir = @dir(DIR_FS_CATALOG . DIR_WS_CATALOG_IMAGES . $path)) {
			while ($file = $dir->read()) {
				if (!is_dir(DIR_FS_CATALOG . DIR_WS_CATALOG_IMAGES . $path . '/' . $file)) {
					if ($image = getimagesize(DIR_FS_CATALOG . DIR_WS_CATALOG_IMAGES . $path . '/' . $file)) {
						$files[] = ($add_dir ? $path . '/' : '') . $file;
					}
				}
			}
		}
		return $files;
	}
	
	function sew_draw_checkbox_field($name, $value = '', $checked = false, $compare = '', $params = null)
	{
		$select = tep_draw_selection_field($name, 'checkbox', $value, $checked, $compare);
		return substr($select,0,-2) . $params . ' />';
	}
	
  function sew_draw_radio_field($name, $value = '', $checked = false, $compare = '', $params = null) {
    $select =  tep_draw_selection_field($name, 'radio', $value, $checked, $compare);
	return substr($select,0,-2) . $params . ' />';
  }

	function sew_ajax_styles() 
	{
	  return '#progress, #result {padding: 8px 10px 10px 40px;} .working {background: url("https://cdnjs.cloudflare.com/ajax/libs/owl-carousel/1.3.3/AjaxLoader.gif") no-repeat left top;} .success {background: url(images/ms_success.png) no-repeat left top;} .failed {background: url(images/ms_error.png) no-repeat left top;} ';
	}