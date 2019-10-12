<?php 
/*************************************************************
* 
* Utility to run from cron and tidy log files for osCommerce
* run at midnight to rename and compress a day's output
* eg. to logfilename.190930.log.gz
*
* with settings in config - to install copy to admin directory
* then run to create settings
*
* set up a cron job to run daily at midnight
* eg. 
  0 0 * * * usr/local/bin/php72 -q /usr/sites/mydir/public_html/admin/sew_cron_tidy_logs.php >> tidy.log
*
* author John Ferguson @BrockleyJohn oscommerce@sewebsites.net
*
* copyright SEwebsites (c) 2019
*
* released under MIT licence without warranty express or implied
*
****************************************************************/

// check how it's being run
$sapi_name = (strlen(PHP_SAPI) > 3 ? substr(PHP_SAPI,0,3) : PHP_SAPI);

if ($sapi_name == 'cli')  {
// from cron or command line
// make sure you're running in current directory which should be admin
  chdir(__DIR__);

  require('includes/configure.php');
  require('includes/database_tables.php');
  require('includes/functions/database.php');
	// make a connection to the database... now
  tep_db_connect() or die('Unable to connect to database server!');
	// set application wide parameters
  $configuration_query = tep_db_query('select configuration_key as cfgKey, configuration_value as cfgValue from ' . TABLE_CONFIGURATION);
  while ($configuration = tep_db_fetch_array($configuration_query)) {
	if (! defined($configuration['cfgKey'])) define($configuration['cfgKey'], $configuration['cfgValue']);
  }
  require('includes/functions/general.php');
  include('includes/classes/language.php');
  $lng = new language();
	
  if (isset($_GET['language']) && tep_not_null($_GET['language'])) {
	$lng->set_language($_GET['language']);
  } else {
	$lng->get_browser_language();
  }
	
  $language = $lng->language['directory'];
  $languages_id = $lng->language['id'];
  require('includes/languages/' . $language . '.php');
  $current_page = 'ase_action_processor.php';
  if (file_exists('includes/languages/' . $language . '/' . $current_page)) {
	include('includes/languages/' . $language . '/' . $current_page);
  }
  
} else { // being run by an admin
  
  require('includes/application_top.php');
  
}

require_once('includes/functions/sew_general_functions.php');
  
  
  