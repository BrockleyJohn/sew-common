<?php
/*
  wrapper for config functions, autoloaders and other utilities
  to load without code change
  Phoenix 1.0.7+
  
  version 0.3
  author @BrockleyJohn oscommerce@sewebsites.net
  Feburuary 2021
  
  copyright (c) SEwebsites 2021

  released under MIT licence without warranty express or implied
  
*/

class sewc {
  
  const TRANSLATIONS = ''; // set in extension to filename if addon language file, e.g. addon_language_file.php

  static function shout()
  // when you don't think the class gets loaded, try sewc::shout();
  {
    echo "oi oi<br>";
  }
  
  static function ajax_styles() 
  {
    return '#progress, #result {padding: 8px 10px 10px 40px;} .working {background: url("https://cdnjs.cloudflare.com/ajax/libs/owl-carousel/1.3.3/AjaxLoader.gif") no-repeat left top;} .success {background: url(images/ms_success.png) no-repeat left top;} .failed {background: url(images/ms_error.png) no-repeat left top;} ';
  }  

  static function d2_flip($in) 
  // Function to flip a 2d array
  {
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

  static function cfg_do_nothing()
  // suppress view / edit for config vars
  {
    return '';
  }
  
  static function cfg_check_output_dir($path) 
  // config view function eg. for log settings
  {
    $return = $path . ' - ';
    if (tep_is_writable($path)) $return .= 'OK';
    else $return .= 'BAD';
    return $return;
  }
  
  static function cfg_chkbox_array($values, $possibles, $separator = ';') 
  // return array of checkboxes from list of possible selections and current value
  {
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
  
  static function cfg_piped_array($var) 
  // config view present | as comma-separated
  {
    return implode(", ", explode('|', $var));
  }
  
  static function cfg_pull_down_top_categories($id, $key = '') 
  // Function for config settings returns list of top level osc categories
  {
    global $languages_id;
    $name = (($key) ? 'configuration[' . $key . ']' : 'configuration_value');
    $list_array = array(array('id' => '0', 'text' => TEXT_TOP));
    $list_query = tep_db_query("SELECT c.categories_id, categories_name FROM categories c, categories_description cd WHERE c.parent_id = 0 AND cd.categories_id = c.categories_id AND cd.language_id = '" . (int)$languages_id . "' ORDER BY categories_name");
    while ($items = tep_db_fetch_array($list_query)) {
      $list_array[] = [
        'id' => $items['categories_id'],
        'text' => $items['categories_name']
      ];
    }
    return tep_draw_pull_down_menu($name, $list_array, $id);
  }

  static function cfg_get_installed_modules($set, $module_language = null)
  // Function returns an array of details on installed modules of type
  // works in admin language
  {
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

   /*   include_once($module_language_directory . $module_language . '/modules/' . $module_type . '/' . $file); 
      include_once($module_directory . $file); */

      $class = substr($file, 0, strrpos($file, '.'));
      if (class_exists($class)) {
        $module = new $class;
        if ($module->check() > 0) { // is it really installed?

          $module_info = [
            'code' => $module->code,
            'title' => $module->title,
            'status' => ($module->enabled ? 1 : 0),
            'public' => (isset($module->public_title) ? $module->public_title : $module->title)
          ];

          $return[] = $module_info;
        }
      }
    }
    return $return;
  }
	
  static function cfg_get_installed_shipping_methods($language = null) 
  // Function returns an array of details on installed shipping methods
  // works in admin language
  {
    return sewc::cfg_get_installed_modules('shipping',$language);
  }
	
  static function cfg_get_installed_payment_methods($language = null) 
  // Function returns an array of details on installed payment methods
  // works in admin language
  {
    return sewc::cfg_get_installed_modules('payment',$language);
  }

  static function cfg_version_delete($class, $var)
  // Function for addon version variables - returns value of version and an uninstall button
  {
    return $var . ' [Uninstall]';
  }
  
  static function country_iso_2_from_name($name)
  // for use eg with order details to get the country code from name  
  {
    $q = tep_db_query(sprintf("select countries_iso_code_2 from countries where countries_name = '%s'", tep_db_input($name)));
    if ($r = tep_db_fetch_array($q)) {
      return $r['countries_iso_code_2'];
    }
  }
  
  static function gtin_valid_check($code) {
    $total_even = 0; $total_odd = 0;
    if (strlen($code) < 8) return false;
    for ($i = 0; $i < $n = strlen($code); $i++) {
      $digit = substr($code,strlen($code) -1 -$i,1);
      if (! is_numeric($digit)) return false;
      if ($i & 1) { // bitcheck - is it odd?
        $total_odd += $digit;
      } elseif ($i > 0) {
        $total_even += $digit;
      } else {
        $check = $digit;
      }
    }
    $total = $total_odd * 3 + $total_even;
    $calc_check = 10 - ($total % 10);
    if ($calc_check == 10) $calc_check = 0;
    return $calc_check == $check;
  }
  
  static function get_orders_statuses() 
  // return all possible orders_status
  {
    global $languages_id;

    $statuses_array = array();
    $statuses_query = tep_db_query("select orders_status_id, orders_status_name from orders_status where language_id = '" . (int)$languages_id . "' order by orders_status_name");
    while ($statuses = tep_db_fetch_array($statuses_query)) {
      $statuses_array[] = [
        'id' => $statuses['orders_status_id'],
        'text' => $statuses['orders_status_name']
      ];
    }
    return $statuses_array;
  }
	
  static function payment_module_drop_down() 
  // Function returns a list of installed payment methods for drop-down
  // works in admin language
  {
    $modules = sewc::cfg_get_installed_payment_methods();
    $list_array = array();
    for ($i = 0; $i < $n = count($modules); $i++) {
      $list_array[] = [
      'id' => $modules[$i]['code'],
      'text' => $modules[$i]['title']
      ];
    }
    return $list_array;
  }  
  
  static function register()
  // set up autoloaders for each subdirectory of apps/SEW  
  {
    $dirs = glob(dirname(__DIR__).'/apps/SEW/*', GLOB_ONLYDIR);
    foreach ($dirs as $dir) {
      $class = 'sewc_' . strtolower(basename($dir));
      if (class_exists($class) && is_callable($class, 'register')) {
        $class::register();
        $class::translations();
      }
    }
  }
  
  static function set_config_var($key, $value) 
  // Function used within addons to set a config var to the passed value
  {
    if (tep_not_null($key)) {
      $sql_data_array = array('configuration_value' => tep_db_input($value));
      $check_query = tep_db_query('select configuration_key from configuration where configuration_key = "' . tep_db_input($key) . '"');
      if (tep_db_num_rows($check_query)) {
        tep_db_perform('configuration', $sql_data_array, 'update', 'configuration_key="' . tep_db_input($key) . '"');
        return true;
      } else {
        return false;
      }
    }
  }
	
  static function translations()
  // if defined and file exists, load translations for addon
  {
    global $language;
    if (static::TRANSLATIONS != '') {
      $filename = DIR_FS_CATALOG . 'includes/languages/' . $language . '/' . static::TRANSLATIONS;
      if (file_exists($filename)) {
        include_once($filename);
      }
    }
  }
  
  static function autoload($class)
    // extend this class for apps to set consts and spl_autoload_register this
  {
    $prefix = static::PFX . '\\';

    // base directory for the namespace prefix - if you decide to put them all somewhere else, change this line
    $base_dir = DIR_FS_CATALOG . 'includes/apps/SEW/' . static::DNAME . '/';

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
  }
}