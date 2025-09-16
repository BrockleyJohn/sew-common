<?php
/*******************************************************
*
* register any required autoloaders for sew addons
*
* author: @BrockleyJohn oscommerce@sewebsites.net
*
* copyright (c) SE Websites 2020
*
* released under MIT licence without warranty express or implied
*
********************************************************/

class hook_admin_siteWide_sewcAddons {
  var $appTop = null;
  
  function listen_injectAppTop() 
  {
    sewc::register();
  }
  
}
