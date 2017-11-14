<?php
/* sew_utils.php 
  version 2.2
  Author John Ferguson (@BrockleyJohn) john@sewebsites.net
	
	handler for switching config vars and such
  
	v2.2 restrict values of action to contain within specified directory
	v2.1 action language files added
	v2.0 actions for utility handler separated to include files
	
	copyright  (c) 2017 osCommerce

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Released under the GNU General Public License
*/

  require('includes/application_top.php');

	$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
	$return = filter_input(INPUT_POST, 'return', FILTER_SANITIZE_STRING);
	
	if (! strlen($return) && strlen($action)) {
	  echo MISSING_PARAMETER;
		exit;
	}

	if (basename($action) <> $action || strpos($action,'.')) {
	  echo UNHANDLED_ACTION;
		exit;
	}

	if (file_exists('includes/sew_actions/'.$action.'.php')) {
		if (file_exists('includes/languages/' . $language . '/sew_actions/' . $action . '.php')) include('includes/languages/' . $language . '/sew_actions/' . $action . '.php');
	  include('includes/sew_actions/'.$action.'.php');
	} else {
    $messageStack->add_session(sprintf(UNHANDLED_ACTION,$action), 'error');
	}
	
  $params = (isset($_POST['param_string']) ? $_POST['param_string'] : '');
	tep_redirect(tep_href_link($return, $params));
?>