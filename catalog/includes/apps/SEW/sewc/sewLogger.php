<?php
/**
-
  generic logging class - appends a record to a log file

  Author John Ferguson (@BrockleyJohn) john@sewebsites.net
  
	copyright  (c) 2019 SEwebsites
	
	usage:
	\SEWC\sewLogger::Log('write this string to the log file');
	
	or perhaps
	cronLogger::Log('write this string to the specific pseudocron log file');

 * specialisations can override with another file name and / or directory
 */
namespace SEWC;

class sewLogger
{
  const LOGGING = true;
  const LOGPATH = DIR_FS_ADMIN . 'logs';
  const LOGFILE = 'logfile';
  const LOGEXT = '.txt';
  const MICRO = false; // whether or not to include microseconds in the line timestamp

  public static function Log($text)
  {
    if (static::LOGGING) {
	  $log_dir = rtrim(static::LOGPATH,'/\\');
	  if (!file_exists($log_dir)) mkdir($log_dir);
	  $log_file = $log_dir . DIRECTORY_SEPARATOR . static::LOGFILE . static::LOGEXT;
	  if ($file_ptr = @fopen($log_file, 'a')) 
	  {
        if (is_array($text)) {
            $text = print_r($text, true);
        }
		$rec = date('d-m-y H:i:s');
		if (static::MICRO) $rec .= '.' . substr((string) microtime(), 1, 6);
		fwrite($file_ptr,$rec . ' - ' . $text . "\n");
		fclose($file_ptr);
	  } else {
	    throw new \Exception(ERROR_DESTINATION_NOT_WRITEABLE . ' ' . $log_file);
	  }
	}
  }
}
