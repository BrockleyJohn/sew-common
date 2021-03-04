<?php
/*
  wrapper for common functions
  to load without code change
  Phoenix 1.0.7+
  
  version 0.1
  author @BrockleyJohn oscommerce@sewebsites.net
  August 2020
  
  copyright (c) SEwebsites 2020

  released under MIT licence without warranty express or implied
  
*/

class sewc_sewc extends sewc {
  
  const PFX = 'SEWC';
  const DNAME = 'sewc';
  const TRANSLATIONS = 'sew_common.php';
  
  static function register()
  {
    spl_autoload_register(function ($class) {
      sewc_sewc::autoload($class);
    });
  }
  
}
  