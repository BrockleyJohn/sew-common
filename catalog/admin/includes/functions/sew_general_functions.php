<?php
/**
-
	functions for configuration settings used across multiple addons
	(instead of updating functions/general.php)
  namespace autoloader

  Author John Ferguson (@BrockleyJohn) john@sewebsites.net
  
	copyright  (c) 2017 SEwebsites

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

  // Function returns an array of details on installed payment methods
	// works in admin language
	function sew_cfg_get_installed_payment_methods($payment_language = null) {
	  global $language, $cfgModules;
		$payments = array();
		if (is_null($payment_language)) $payment_language = $language;
    $modules = $cfgModules->getAll();
		$set = 'payment';
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
	
			include_once($module_language_directory . $payment_language . '/modules/' . $module_type . '/' . $file);
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

          $payments[] = $module_info;
				}
			}
		}
		return $payments;
	}
	
	function sew_ajax_styles() 
	{
	  return '#progress, #result {padding: 8px 10px 10px 40px;} .working {background: url("https://cdnjs.cloudflare.com/ajax/libs/owl-carousel/1.3.3/AjaxLoader.gif") no-repeat left top;} .success {background: url(images/ms_success.png) no-repeat left top;} .failed {background: url(images/ms_error.png) no-repeat left top;} ';
	}