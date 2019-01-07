<?php
/**
-
	superclass for module settings to save repeating method definitions and
	extended to support a settings page
  
  v1.0 1/10/18 initial version
  
  Author John Ferguson (@BrockleyJohn) john@sewebsites.net
  
	copyright  (c) 2018 SEwebsites

 *
 */

 class sew_module_with_settings {
 
    function isEnabled() {
      return $this->enabled;
    }

    function remove() {
      tep_db_query("delete from configuration where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function install($parameter = null) {
      if (is_callable( [$this, 'install_db'] )) {
	    $this->install_db();
	  }
	  
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

        tep_db_perform(TABLE_CONFIGURATION, $sql_data_array);
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

    function settings_page() {
	  $output = [];
	  $settings = $this->getParams();
	  
      if ($this->check()) {
        foreach ($settings as $var => $setting) {
          $key = tep_db_prepare_input($var);

          $key_value_query = tep_db_query("select configuration_title, configuration_value, configuration_description, use_function, set_function from " . TABLE_CONFIGURATION . " where configuration_key = '" . tep_db_input($key) . "'");
          $key_value = tep_db_fetch_array($key_value_query);

		  $sql_data_array = [];
          if ($setting['title'] <> $key_value['configuration_title']) $sql_data_array['configuration_title'] = $setting['title'];
		  if ($setting['desc'] <> $key_value['configuration_description']) $sql_data_array['configuration_description'] = $setting['desc'];
		  if (array_key_exists('set_func',$setting) && $setting['set_func'] <> $key_value['set_function']) $sql_data_array['set_function'] = $setting['set_func'];
		  if (array_key_exists('use_func',$setting) && $setting['use_func'] <> $key_value['use_function']) $sql_data_array['use_function'] = $setting['use_func'];
		  
		  if (count($sql_data_array)) { // update setting parameters from module to db
		    tep_db_perform(TABLE_CONFIGURATION, $sql_data_array, 'update', 'configuration_key = "' . tep_db_input($key) . '"');
		  }
		  
          if (array_key_exists('settings',$setting) && $setting['settings'] == 'True') {
			$setting_out = '<strong>' . $setting['title'] . '</strong><br />' . $setting['desc'] . '<br />';
	
			if ($setting['setting_type']) {
			  switch ($setting['setting_type']) {
			    case 'editor' :
				  $setting_out .= tep_draw_textarea_field('configuration['.$key.']', null, 120, 20, $key_value['configuration_value'],'class="editor"');
				  break;
			    case 'image' :
				  $file = $key_value['configuration_value'];
				  $setting_out .= '<div style="float:left; text-align:center;"><img height="100" src="' . HTTPS_CATALOG_SERVER . DIR_WS_CATALOG_IMAGES . $file . '"/><br/>' . $file . "</div>\n";
				  $setting_out .= '<div id="'.$key.'_Images" style="clear:left;">'.TEXT_CHOOSE_IMAGE."<br/>\n".'<div id="'.$key.'_imageList">';
				  if ($setting['page_set_func'] && function_exists($setting['page_set_func'])) {
					eval('$image_setting = ' . $setting['page_set_func'] . '();');
					if ($image_setting && is_array($image_setting) && array_key_exists('list',$image_setting)) {
					  if (array_key_exists('error',$image_setting) && strlen($image_setting['error'])) {
					    $setting_out .= $image_setting['error'];
					  }
					  foreach ($image_setting['list'] as $file) {
				        $setting_out .= '<div style="float:left; text-align:center;" class="selectable" data-config-key="'.$key.'" data-config-value="'.$file.'">' . sew_draw_radio_field('image_'.$key, $file, false, null, 'class="selector"') . '<img height="100" src="' . HTTPS_CATALOG_SERVER . DIR_WS_CATALOG_IMAGES . $file . '"/><br/>' . $file . "</div>\n";
					  }
					}
					$setting_out .= '</div></div>';
					$setting_out .= '<div id="upload_'.$key.'" style="clear:left;">' . TEXT_OR_UPLOAD . "<br/>\n" . tep_draw_input_field($name, '', 'accept="image/*" id="fileinput_'.$key.'" data-config-key="'.$key.'"', false, 'file') . tep_draw_hidden_field($key.'_dir',$image_setting['dir'], 'id="'.$key.'_dir"') . tep_draw_hidden_field('configuration['.$key.']',constant($key),'id="'.$key.'_value"') . '<img id="output_'.$key.'" height="100"/><div class="progress" id="progress_'.$key.'"></div>'; 
					$setting_out .= <<<EOD
<script type="text/javascript">
//  var files;
/*	var loadFile = function(event) {
//    var output = document.getElementById('output');
//    output.src = URL.createObjectURL(event.target.files[0]); 
	  $('#output').attr('src',URL.createObjectURL(event.target.files[0])); */
$('.selectable').on('click', function () {
	var config = $(this).data('config-key');
	var keyvalue = $(this).data('config-value');
	$('.selector',this).prop('checked', true);
	$('#' + config + '_value').val(keyvalue);
}); 
$('input[type=file]').on('change', fileUpload);
//});		
function fileUpload() {		
	  var config = $(this).data('config-key');
	  if ($('#progress_' + config).hasClass('failed')) $('#progress_' + config).removeClass('failed');
	  if ($('#progress_' + config).hasClass('success')) $('#progress_' + config).removeClass('success');
	  $('#uploadDir').val($('#' + config + '_dir').val());
	  $('#progress_' + config).addClass('working');
//		var form = $("#ajaxForm")[0];
		var params = '';
//		var files = event.target.files;
//		var files = $(this).prop('files');
		var files = $('#fileinput_' + config).prop('files');
		var data = new FormData();
		$('form#ajaxForm input[type=hidden]').each(function(){
		  var input = $(this);
			if (params.length > 0) {
			  params += '&';
			} else {
			  params = '?';
			}
			params += input.attr('name') + '=' + input.val();
//      console.log( input.attr('name') + ':' + input.val());
		});
		for (var i = 0; i < files.length; i++){
		  var file = files[i];
				data.append('image', file, file.name);
//				data.append('imagename['+ i +']', file, file.name);
	    $('#output_' + config).attr('src',URL.createObjectURL(file)); 
		}
/*		$.each(event.target.files, function()
    {
        var file  = $(this);
				data.append('image', file, file.name);
        console.log( key + ':' + value);
    });
		$.each(event.target.files, function(key, value)
    {
        data.append(key, value);
        console.log( key + ':' + value);
    }); */
   /* $.ajax({
        url: 'sew_ajax.php',
        type: 'POST',
        data: data,
        cache: false,
        dataType: 'json',
        processData: false, // Don't process the files
        contentType: false, // Set content type to false as jQuery will tell the server its a query string request
        success: function(data, textStatus, jqXHR)
        {
	          $('#progress').removeClass('working');
            if(typeof data.error === 'undefined')
            {
	          $('#progress').addClass('success');
                // Success so call function to process the form
                submitForm(event, data);
            }
            else
            {
							$('#progress').addClass('failed');
                // Handle errors here
                console.log('ERRORS: ' + data.error);
                console.log('POST: ' + data.post);
            }
        },
        error: function(jqXHR, textStatus, errorThrown)
        {
            // Handle errors here
            console.log('ERRORS: ' + textStatus);
	          $('#progress').removeClass('working');
	          $('#progress').addClass('failed');
        }
    }); */
            //create a new XMLHttpRequest
            var xhr = new XMLHttpRequest();     
            
            //post file data for upload
            xhr.open('POST', 'sew_ajax.php' + params, true);  
            xhr.send(data);
            xhr.onload = function () {
			          $('#progress_' + config).removeClass('working');
                //get response and show the uploading status
                var response = JSON.parse(xhr.responseText);
                if (xhr.status === 200 && response.status == 'ok') {
									$('#progress_' + config).addClass('success');
                  $("#" + config + "_imageList").append(response.images);
									$('#fileinput_' + config).val(''); 
	    		$('#output_' + config).attr('src',''); 
				$("#" + config + "_value").val();
//                }else if(response.status == 'type_err'){
//                    $("#dropBox").html("Please choose an images file. Click to upload another.");
                } else {
									$('#progress_' + config).addClass('failed');
									// Handle errors here
									console.log('REQ STATUS: ' + xhr.status);
									console.log('RESP STATUS: ' + response.status);
									console.log('ERRORS: ' + response.error);
									console.log('LOG: ' + response.log);
									console.log('POST: ' + response.post);
									console.log('GET: ' + response.get);
//                    $("#dropBox").html("Some problem occured, please try again.");
                }
            };

  }
</script>
            </div>
EOD;
          /* <div>
          echo TEXT_PAGE_IMAGES."<br>\n".' <div id="pageImages"><div id="progress"></div><div id="imageList">';

					echo sew_get_page_images($slug);
						</div></div> */
					
				  }
				  break;
			  }
			} elseif ($setting['set_func']) {
			  eval('$setting_out .= ' . $setting['set_func'] . "'" . $key_value['configuration_value'] . "', '" . $key . "');");
			} else {
			  $setting_out .= tep_draw_input_field('configuration[' . $key . ']', $key_value['configuration_value']);
			}
			
			$output[] = $setting_out;
	
          }
        }
      }
	  
	  return $output;
	
	}

 }