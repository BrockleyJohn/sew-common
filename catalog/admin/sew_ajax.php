<?php
/*************************************************************************
** YOU ARE STRONGLY RECOMMENDED TO PASSWORD PROTECT THE ADMIN DIRECTORY **
**************************************************************************
  sew_ajax
	an ajax handler which pulls in scripts depending on action
  
	Author: John Ferguson @BrockleyJohn john@sewebsites.net

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2017 SEwebsites

  Released under the GNU General Public License
*/
 
  require('includes/application_top.php');
	$logging = '';
//	$log = ((isset($_POST['log']) || isset($_POST['log'])) ? true : false);
	if (isset($_POST['pagename']) && $_POST['pagename'] <> '') {
	  $pagename = filter_input ( INPUT_POST, 'pagename', FILTER_SANITIZE_URL );
	} elseif (isset($_GET['pagename']) && $_GET['pagename'] <> '') {
	  $pagename = filter_input ( INPUT_GET, 'pagename', FILTER_SANITIZE_URL );
	}
	if (strlen($pagename)) {
	  $logging .= 'including includes/languages/' . $language . '/' . basename($pagename)."\n";
  if (file_exists('includes/languages/' . $language . '/' . basename($pagename))) {
    include('includes/languages/' . $language . '/' . basename($pagename));
  }
	}
 // include_once('includes/languages/' . $language . '/sew_ajax.php');

	$result = array();
	$action = (isset($_POST['action']) ? $_POST['action'] : $_GET['action']);

  if (file_exists('sew_ajax/' . $action  . '.php')) {
		if (file_exists('includes/languages/' . $language . '/sew_ajax/' . $action  . '.php')) include('includes/languages/' . $language . '/sew_ajax/' . $action  . '.php');
		include('sew_ajax/' . $action  . '.php');
	} else {
		$result['status'] = 'fail';
		$result['error'] = sprintf(UNHANDLED_ACTION,$action);
	}
	if (array_key_exists('error',$result)) {
	  $text = '';
		foreach($_POST as $key => $value) {
		  $text .= $key.'-'.$value.',';
		}
		if (strlen($text)) substr($text,-1);
		$result['post'] = $text;
	  $text = '';
		foreach($_GET as $key => $value) {
		  $text .= $key.'-'.$value.',';
		}
		if (strlen($text)) substr($text,-1);
		$result['get'] = $text;
		$result['log'] = $logging;
	}
	echo json_encode($result);

  require('includes/application_bottom.php');