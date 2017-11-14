<?php
/*
  $Id: sew_check.php JAF
	version 2.0 July 2017 
  
  check / upgrade database for model changes

	Author John Ferguson @BrockleyJohn john@sewebsites.net
	Copyright (c) 2017 sewebsites.net

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Released under the GNU General Public License
*/

  require('includes/application_top.php');

//if necessary, perform database changes
	$model = new \SEWC\oscModel();
  require('includes/template_top.php');
?>

<!-- body_text //-->
    <table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
            <td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><?php echo CHECK_TEXT; ?>
        </td>
      </tr>
      <tr>
        <td>
        </td>
      </tr>
      <tr>
        <td>
        </td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr><td><?php

?>
          </td><td width="50"></td></tr>
        </table></td>
      </tr>
      
    </table>
<!-- body_text_eof //-->
<?php require('includes/template_bottom.php');
  require('includes/application_bottom.php'); ?>