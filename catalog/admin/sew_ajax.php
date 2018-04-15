<?php
/************************************************************************************
** YOU ARE STRONGLY RECOMMENDED TO RENAME AND PASSWORD PROTECT THE ADMIN DIRECTORY **
*************************************************************************************
  sew_ajax
	an ajax handler which pulls in scripts depending on action
  
	Author: John Ferguson @BrockleyJohn john@sewebsites.net

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2017 SEwebsites

  Released under the GNU General Public License
*/
  require('includes/application_top.php');
  
  require_once('includes/functions/sew_general_functions.php'); // in case not in app top...
  
  // app top will have diverted any not signed in, but
  // only process requests from pages from this site
  $address = ($request_type == 'NONSSL' ? 'http' : 'https') . '://' . $_SERVER['SERVER_NAME'];
  if (isset($_SERVER['HTTP_ORIGIN'])) {
	if (strpos($address, $_SERVER['HTTP_ORIGIN']) !== 0) {
		header('HTTP/1.0 403 Forbidden', true, 403);
		header('Content-Type: application/json; charset=utf-8');
		exit(json_encode([
			'error' => 'Invalid Origin header: ' . $_SERVER['HTTP_ORIGIN']
		]));
	}
  } elseif (isset($_SERVER['HTTP_REFERER'])) { // no origin so check the referrer
	if (parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) <> $_SERVER['SERVER_NAME']) { // slightly better than not bothering
		header('HTTP/1.0 403 Forbidden', true, 403);
		header('Content-Type: application/json; charset=utf-8');
		exit(json_encode([
			'error' => 'Invalid Referer header: ' . $_SERVER['HTTP_REFERER']
		]));
	}
  } else { 
	header('HTTP/1.0 403 Forbidden', true, 403);
	header('Content-Type: application/json; charset=utf-8');
	exit(json_encode(['error' => 'No Origin/Referer headers']));
  }  

  function clean_var($var,$filter) {
    $return = '';
	if (isset($_POST[$var]) && $_POST[$var] <> '') {
	  $return = filter_input ( INPUT_POST, $var, $filter );
	} elseif (isset($_GET[$var]) && $_GET[$var] <> '') {
	  $return = filter_input ( INPUT_GET, $var, $filter );
	}
	return $return;
  }
 
	$logging = '';
//	$log = ((isset($_POST['log']) || isset($_POST['log'])) ? true : false);

	$pagename = clean_var('pagename',FILTER_SANITIZE_URL);
	$action = clean_var('action',FILTER_SANITIZE_URL);

	if (strlen($pagename)) {
	  $logging .= 'including includes/languages/' . $language . '/' . basename($pagename)."\n";
	  if (file_exists('includes/languages/' . $language . '/' . basename($pagename))) {
		include('includes/languages/' . $language . '/' . basename($pagename));
	  }
	}
 // include_once('includes/languages/' . $language . '/sew_ajax.php');

	$result = array();

  ob_start();
  if (strlen($action) && file_exists('sew_ajax/' . $action  . '.php')) {
		if (file_exists('includes/languages/' . $language . '/sew_ajax/' . $action  . '.php')) include('includes/languages/' . $language . '/sew_ajax/' . $action  . '.php');
		include('sew_ajax/' . $action  . '.php');
	} else {
		$result['status'] = 'fail';
		$result['error'] = sprintf(UNHANDLED_ACTION,$action);
	}
	$logging .= ob_get_clean() . "\n";
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

	header('Content-Type: application/json; charset=utf-8');
	echo json_encode($result);

  require('includes/application_bottom.php');
