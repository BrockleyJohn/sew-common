<?php
/*
  wrapper for functions for working with templates
  
  author: John Ferguson @BrockleyJohn oscommerce@sewebsites.net
*/

class sewc_tpl {

  public static function image($img_file, $alt = '', $bs_css = '', $width = '', $height = '', $parameters = '', $responsive = true) 
  // load an image from the template - wrapper for tep_image
  {
    $tpl = defined('TEMPLATE_SELECTION') ? TEMPLATE_SELECTION : 'default';
    return tep_image("templates/{$tpl}/images/" . $img_file, $alt, $width, $height, $parameters, $responsive, $bs_css);
  }

}