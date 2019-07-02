<?php  /*
	Superclass for content modules, saving repeated code
	Author John Ferguson @BrockleyJohn oscommerce@sewebsites.net
*/
class sew_content_module {

    var $name_bit;
    var $code;
    var $group;
    var $title;
    var $description;
    var $sort_order;
    var $enabled = false;
	
	function __construct() {
      $this->title = constant('MODULE_CONTENT_' . $this->name_bit . '_TITLE');
      $this->description = constant('MODULE_CONTENT_' . $this->name_bit . '_DESCRIPTION');

      if ( defined('MODULE_CONTENT_' . $this->name_bit . '_STATUS')) {
        $this->sort_order = constant('MODULE_CONTENT_' . $this->name_bit . '_SORT_ORDER');
        $this->enabled = (constant('MODULE_CONTENT_' . $this->name_bit . '_STATUS') == 'True');
      }
	}

    function isEnabled() {
      return $this->enabled;
    }

    function check() {
      return defined(constant('MODULE_CONTENT_' . $this->name_bit . '_STATUS'));
    }

    function remove() {
      tep_db_query("delete from configuration where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function install($parameter = null) {
      $params = $this->getParams();

      if (isset($parameter)) {
        if (isset($params[$parameter])) {
          $params = array($parameter => $params[$parameter]);
        } else {
          $params = array();
        }
      }

      foreach ($params as $key => $data) {
        $sql_data_array = array('configuration_title' => $data['title'],
                                'configuration_key' => $key,
                                'configuration_value' => (isset($data['value']) ? $data['value'] : ''),
                                'configuration_description' => $data['desc'],
                                'configuration_group_id' => '6',
                                'sort_order' => '0',
                                'date_added' => 'now()');

        if (isset($data['set_func'])) {
          $sql_data_array['set_function'] = $data['set_func'];
        }

        if (isset($data['use_func'])) {
          $sql_data_array['use_function'] = $data['use_func'];
        }

        tep_db_perform('configuration', $sql_data_array);
      }
    }

    function keys() {
      $keys = array_keys($this->getParams());

      if ($this->check()) {
        foreach ($keys as $key) {
          if (!defined($key)) {
            $this->install($key);
          }
        }
      }

      return $keys;
    }

}