<?php
/**
-
  wrapper for addon versioning and delivery

  Author John Ferguson (@BrockleyJohn) john@sewebsites.net
  
	copyright  (c) 2017 SEwebsites

 * checks the distribution model in database & installs/updates if ness
 * ditto for the addon model if required
 * addon version / variant handling:
 * 
 */
namespace SEWC;

global $language;
include_once(DIR_FS_ADMIN . 'includes/languages/' . $language . '/sew_addons.php');

class addonVersion
{
	public $app_code;
	public $last_check = false;
	private $db = false;
	const WORK_DIR = 'SEW/work'; 
	const ADDON_DIR = 'SEW/addons/';
	
	public function __construct($code, $addon_codebase, $addon_title, $addon_author, $model_class = null) {
		$model = new distroModel(); 
		if (tep_not_null($model_class) && class_exists($model_class)) {
			$model = new $model_class();
		}
		$query = tep_db_query('SELECT * FROM sew_addons WHERE app_code = "' . tep_db_input($code) . '"');
		if (tep_db_num_rows($query)) {
			$this->db = true;
			$this->loadFromDb(tep_db_fetch_array($query));
		} else {
			$this->app_code = tep_db_input($code);
			$this->version_name = tep_db_input($addon_codebase);
			$this->version_title = tep_db_input($addon_title);
			$this->version_author = tep_db_input($addon_author);
		}
		$this->getKey();
	}
	
	public function includes($seq) {
		$return = array();
		if ((!isset($this->app_key)) || (!tep_not_null($this->app_key))) {
			$return[] = self::ADDON_DIR . $this->app_code . '-' . $seq . '.php';
		} else {
			echo 'app key "' . $this->app_key . '"';
			$return = $this->proincludes($seq);
		}
		return $return;
	}
	
  public function check() {
		$request = new \SEWC\sewApiRequest();
		$response = $request->getVersionInfo($this->app_code);
		if ($response->OK) {
			$sql_data_array = array();
			$sql_data_array['last_check'] = 'now()';
			if ($response->data->version_name <> $this->version_name) {
				$sql_data_array['update_available'] = true;
				$sql_data_array['version_available'] = $response->data->version_name;
			}
			if ($this->db) {
				tep_db_perform('sew_addons', $sql_data_array, 'update', 'version_id = ' . (int)$this->version_id);
			} else {
				$sql_data_array['app_code'] = $response->data->app_code;
				$sql_data_array['version_name'] = $this->version_name;
				$sql_data_array['version_title'] = $response->data->version_title;
				$sql_data_array['version_author'] = $response->data->version_author;
				$sql_data_array['pro_cost'] = $response->data->pro_cost;
				tep_db_perform('sew_addons', $sql_data_array);
			}
			return true;
		} else {
			return false;
		}
  }
	
	public function badge() {
		$output = '<div><style scoped>#vbutton, #vfresh, #vupdate, #buypro, #prokey {background: #225522; color: #ffffff; font-weight: bold; display: inline-block; padding: 10px; border-radius: 5px; margin:5px; cursor:pointer;} #vfresh, #vupdate {background: #99ff99; color: #222222; border:1px solid;} #vbadge {font-weight: bold; display: inline-block; padding: 10px; border-radius: 5px; margin:5px; border:1px solid;} .loadingActive, .failed, .succeeded { background-image: url(images/loading.gif) !important; background-repeat: no-repeat !important; background-position: left center !important; padding-left:40px !important; } .failed {background-image: url(images/ms_error.png) !important; } .succeeded {background-image: url(images/ms_success.png) !important; } #vbutton { float:right; } #vinfo, #pinfo {width: 45px; height: 45px; cursor:pointer;} #pinfo { float:right; background-image: url(images/ms_info.png); background-repeat: no-repeat; background-position: center center; } #loading {width: 45px; height: 45px;} </style>';
		$output .= '<div id="vbadge">' . $this->version_title . '<br />Version: ' . $this->version_name . '<br />Author: ' . $this->version_author . '</div><br />';
		if ($this->update_available) $output .= '<div id="vupdate">' . SEW_UPDATE_AVAILABLE . ' - Version: ' . $this->version_available . ' - ' .SEW_CLICK_TO_INSTALL . '</div><br />';
		if ((!isset($this->app_key)) || (!tep_not_null($this->app_key))) {
			$output .= '<div id="vbutton">Go Pro ';
			if (isset($this->pro_cost)) { $output .= ' - Only &pound;' . $this->pro_cost; }
			$output .= '</div><div id="pinfo"></div>';
			$ptitle = SEW_GO_PRO;
			$pbuy = SEW_BUY_PRO;
			$pkey = SEW_PRO_KEY;
			$output .= <<<EOT
<div id="pdialog" style="display:none;" title="$ptitle">
<div id="buypro">$pbuy</div>
<div id="prokey">$pkey</div>
<div id="loading"></div>
</div>
EOT;
		} else {
			$output .= '<div id="vfresh">Pro Version - rebuild</div>';
		}
		$output .= '</div>'; 
		return $output;
	}
	
	public function install($filename) {
		$work_dir = DIR_FS_ADMIN . self::WORK_DIR . '/';
		if (!file_exists($work_dir . $filename)) {
			echo sprintf(SEW_FILE_NOT_FOUND,$filename);
			return false;
		}
		$unzip_dir = $work_dir . $this->app_code;
		if (file_exists($unzip_dir)) {
			if (! $this->deldir($unzip_dir)) {
				echo sprintf(SEW_FAILED_TO_DELETE,$unzip_dir);
				return false;
			}
		}
		mkdir($unzip_dir);
		$unzip_dir .= '/';
			
		$zip = new \ZipArchive();
		if ($zip->open($work_dir . $filename) === true) {
			$zip->extractTo($unzip_dir);
			$zip->close();
		}
		unset($zip);

		if ((!file_exists($unzip_dir . 'update.zip')) || (!file_exists($unzip_dir . 'update.sig'))) {
			echo SEW_UNPACK_FAILED;
			return false;
		}
		
		if (! $publicKeyId = openssl_get_publickey(file_get_contents(DIR_FS_ADMIN . 'SEW/sew.pem'))) {
			echo SEW_CERT_FAILED;
			return false;
		}
		$verify = openssl_verify(file_get_contents($unzip_dir . 'update.zip'), file_get_contents($unzip_dir . 'update.sig'), file_get_contents(DIR_FS_ADMIN . 'SEW/sew.pem'), OPENSSL_ALGO_SHA256);
		switch ($verify) {
			case 0 :
				echo SEW_CHECK_FAILED;
				return false;
			case -1 :
				echo SEW_CHECK_ERROR;
				return false;
		}
		
		mkdir($unzip_dir . 'unzip');
		$zip = new \ZipArchive();
		if ($zip->open($unzip_dir . 'update.zip') === true) {
			$zip->extractTo($unzip_dir . 'unzip');
			$zip->close();
		}
		unset($zip);

        // check that everything necessary can be overwritten
		$errors = array();

		$update_files = $this->getDirectoryContents($unzip_dir . 'unzip');
		
		foreach ($update_files as $file) {
        	$pathname = substr($file, strlen($unzip_dir . 'unzip/'));
			
			if (substr($pathname, 0, 8) == 'catalog/') {
				if (!$this->isWritable(DIR_FS_CATALOG . substr($pathname, 8)) || !$this->isWritable(DIR_FS_CATALOG . dirname(substr($pathname, 8)))) {
                	$errors[] = $this->displayPath(DIR_FS_CATALOG . substr($pathname, 8));
                }
			} elseif (substr($pathname, 0, 6) == 'admin/') {
				if (!$this->isWritable(DIR_FS_ADMIN . substr($pathname, 8)) || !$this->isWritable(DIR_FS_ADMIN . dirname(substr($pathname, 8)))) {
                	$errors[] = $this->displayPath(DIR_FS_ADMIN . substr($pathname, 8));
                }
			}
		}
		
		if (empty($errors)) {
			foreach ($update_files as $file) {
	        	$pathname = substr($file, strlen($unzip_dir . 'unzip/'));

                if (substr($pathname, 0, 8) == 'catalog/') {
					$target = dirname(substr($pathname, 8));
					
					if ($target == '.') {
						$target = '';
					}
					
					if (!file_exists(DIR_FS_CATALOG . $target)) {
						mkdir(DIR_FS_CATALOG . $target, 0777, true);
					}
					
					if (!empty($target) && (substr($target, -1) != DIRECTORY_SEPARATOR)) {
						$target .= DIRECTORY_SEPARATOR;
					}
					
					if (!copy($file, DIR_FS_CATALOG . $target . basename($pathname))) {
						$errors[] = DIR_FS_CATALOG . $target . basename($pathname);
						break;
					}
				} elseif (substr($pathname, 0, 6) == 'admin/') {
					$target = dirname(substr($pathname, 6));
					
					if ($target == '.') {
						$target = '';
					}
					
					if (!file_exists(DIR_FS_ADMIN . $target)) {
						mkdir(DIR_FS_ADMIN . $target, 0777, true);
					}
					
					if (!empty($target) && (substr($target, -1) != DIRECTORY_SEPARATOR)) {
						$target .= DIRECTORY_SEPARATOR;
					}
					
					if (!copy($file, DIR_FS_ADMIN . $target . basename($pathname))) {
						$errors[] = DIR_FS_ADMIN . $target . basename($pathname);
						break;
					}
				}
			}
		}
		
		if (!empty($errors)) {
			foreach ($errors as $error) {
				echo $error . "\n";
			}
			return false;
		} else {
			$this->deldir($unzip_dir . 'unzip');
			$sql_data_array = array();
			$sql_data_array['version_name'] = $this->version_available;
			$sql_data_array['update_available'] = false;
			$sql_data_array['version_available'] = 'null';
			tep_db_perform('sew_addons', $sql_data_array, 'update', 'version_id = ' . (int)$this->version_id);
			return true;
		}

	}
	
	public function getUpdate($addon_version) {
		if ($addon_version == $this->version_available) {
			$work_dir = DIR_FS_ADMIN . self::WORK_DIR;
			if (!file_exists($work_dir)) {
				mkdir($work_dir);
			}
			if (!is_writable($work_dir)) {
				echo SEW_WORK_DIR_NOT_WRITABLE;
				return false;
			}
			$request = new \SEWC\sewApiRequest();
			$response = $request->getUpdateFileName($this->app_code,$this->version_available);
			if ($response->OK) {
				$source = $response->data->filename;
				$filename = basename($source);
				$local = $work_dir . '/' . $filename;
				if (! file_exists($local)) {
					$ch = curl_init($request->urlroot . $source);
					$fp = fopen($local, 'wb');
					curl_setopt($ch, CURLOPT_FILE, $fp);
					curl_setopt($ch, CURLOPT_HEADER, 0);
					curl_exec($ch);
					curl_close($ch);
					fclose($fp);
				}
				return $filename;
			} else {
				echo SEW_SERVER_NOT_CONTACTED;
				return false;
			}
		} else {
			echo ERROR_INCONSISTENT_DATA;
			return false;
		}
	}
	
	public function getInfo($detail) {
		$request = new \SEWC\sewApiRequest();
		$response = $request->showVersionInfo($this->app_code,$this->version_name,$detail);
		if ($response->OK) {
			return $response->data->html;
		} else {
			echo SEW_REQUEST_FAILED;
			return false;
		}
	}
	
	public function getPro($addon_version) {
		if ($addon_version == $this->version_name) {
			$work_dir = DIR_FS_ADMIN . self::WORK_DIR;
			if (!file_exists($work_dir)) {
				mkdir($work_dir);
			}
			if (!is_writable($work_dir)) {
				echo SEW_WORK_DIR_NOT_WRITABLE;
				return false;
			}
			$request = new \SEWC\sewApiRequest();
			$response = $request->getProFileName($this->app_code,$this->version_name,SEW_ADDONS_USER_TOKEN,$this->app_key);
			if ($response->OK) {
				$source = $response->data->filename;
				$filename = basename($source);
				$local = $work_dir . '/' . $filename;
				if (! file_exists($local)) {
					$ch = curl_init($request->urlroot . $source);
					$fp = fopen($local, 'wb');
					curl_setopt($ch, CURLOPT_FILE, $fp);
					curl_setopt($ch, CURLOPT_HEADER, 0);
					curl_exec($ch);
					curl_close($ch);
					fclose($fp);
				}
				return $filename;
			} else {
				echo SEW_SERVER_NOT_CONTACTED;
				return false;
			}
		} else {
			echo ERROR_INCONSISTENT_DATA;
			return false;
		}
	}
	
	public function getAppToken($user_token) {
		$request = new \SEWC\sewApiRequest();
		$response = $request->getAppToken($this->app_code,$user_token);
		if ($response->OK && $response->data->status == 'ok') {
			$this->storeKey($response->data->token);
			return true;
		}
	}
	
	public function scripts() {
		$code = $this->app_code;
		$version = $this->version_available;
		$fetching = SEW_UPDATE_FETCHING;
		$keyfetching = SEW_APP_KEY_FETCHING;
		$profetching = SEW_PRO_FETCHING;
		$installing = SEW_UPDATE_INSTALLING;
		$success = SEW_UPDATE_SUCCEEDED;
		$failed = SEW_REQUEST_FAILED;
		$tokenpage = 'https://sewebsites.net/oscommerce/user_token.php?products_model=' . $this->app_code;
		$output = <<<EOS
<script>
	var popWindow;
	function popDialog(param) {
		var dialog = $('<div style="display:none" class="loading"><style scoped>.loading { background: url(images/loading.gif) center no-repeat !important}</style></div>').appendTo('body');
        dialog.dialog({
			// add a close listener to prevent adding multiple divs to the document
			close: function(event, ui) {
				// remove div with all data and events
				dialog.remove();
			},
			modal: true
		});
		
		var data = new FormData();
		data.append('addon_code','$code');
		data.append('version','$version');
		data.append('detail',param);
		data.append('action','addon_view_details');

		//create a new XMLHttpRequest
		var xhr = new XMLHttpRequest();     
		xhr.open('POST', 'sew_ajax.php', true);  
		xhr.send(data);
		xhr.onload = function () {
			//get response and show the status
			var response = JSON.parse(xhr.responseText);
			if(xhr.status === 200 && response.status == 'ok'){
				// remove the loading class
				dialog.removeClass('loading');
				dialog.append(response.html);
			}else{
				dialog.removeClass('loading');
				// Handle errors here
				console.log('ERRORS: ' + response.error);
				console.log('LOG: ' + response.log);
				console.log('POST: ' + response.post);
				console.log('GET: ' + response.get);
			}
		};

	}
	function receiveMessage(event) {
		if (event.origin !== "https://sewebsites.net") return;
		if (event.source !== popWindow) return;
		var token = event.data;
		popWindow.close();
		getAppKey(token);
	  // ...
	}
	function getAppKey(token) {
		$('#loading').removeClass();
		$('#loading').addClass('loadingActive');
		$('#loading').text('$keyfetching');
		var data = new FormData();
		data.append('addon_code','$code');
		data.append('version','$version');
		data.append('token',token);
		data.append('action','addon_fetch_token');

		//create a new XMLHttpRequest
		var xhr = new XMLHttpRequest();     
		xhr.open('POST', 'sew_ajax.php', true);  
		xhr.send(data);
		xhr.onload = function () {
			//get response and show the status
			var response = JSON.parse(xhr.responseText);
			$('#loading').removeClass();
			if(xhr.status === 200 && response.status == 'ok'){

				$('#loading').addClass('loadingActive');
				$('#loading').text('$profetching');
				data.set('action','addon_fetch_pro');

				var xhr2 = new XMLHttpRequest();     
				xhr2.open('POST', 'sew_ajax.php', true);  
				xhr2.send(data);
				xhr2.onload = function () {
					//get response and show the status
					var response = JSON.parse(xhr2.responseText);
					$('#loading').removeClass();
					if(xhr2.status === 200 && response.status == 'ok'){
						$('#loading').addClass('succeeded');
						window.location.reload(true);
					}else{
						$('#loading').addClass('failed');
						// Handle errors here
						console.log('ERRORS: ' + response.error);
						console.log('LOG: ' + response.log);
						console.log('POST: ' + response.post);
						console.log('GET: ' + response.get);
					}
				};
			} else {
				$('#loading').addClass('failed');
				$('#loading').text('$failed');
				console.log('ERRORS: ' + response.error);
				console.log('LOG: ' + response.log);
				console.log('POST: ' + response.post);
				console.log('GET: ' + response.get);
			}
		}
	}
	$(function() {
	  $('a.disabled').on('click',function(e) {
		e.preventDefault();
	  });
	  $('#pinfo').on('click',function(e) {
		popDialog('pro');
	  });
EOS;
	    if (defined('SEW_ADDONS_USER_TOKEN') && strlen(SEW_ADDONS_USER_TOKEN)) {
			$output .= <<<EOS
	  $('#prokey').on('click',function(e) {
		getAppKey('TOKEN_SET');
	  });
EOS;
		} else {
			$output .= <<<EOS
	  $('#prokey').on('click',function(e) {
		popWindow = window.open('$tokenpage', 'SEWaddons');
		window.addEventListener("message", receiveMessage, false);
	  });
EOS;
		}
		if ((!isset($this->app_key)) || (!tep_not_null($this->app_key))) {
			$buypage = 'https://sewebsites.net/oscommerce/product_by_model.php?products_model=' . $this->app_code;
			$output .= <<<EOS
		$('#vbutton').on('click',function(e) {
			$('#pdialog').dialog('open');
		});
		$('#pdialog').dialog({
//			modal: true,
			width: 250,
			autoOpen: false
		});
/*		$('body').append('<div class="overlayOuter"> <style scoped>.overlayOuter{ background:#000; opacity:0.7; display:none; height:100%; left:0; position:absolute; top:0; width:100%; z-index:100001; } .overlayInner{ position:absolute; top:5%; left:5%; width:500px; z-index:100001; }</style> <div class="overlayInner"> </div> </div>');
		$("#pdialog").on("click", "#buypro", function(){
			$('.overlayInner').load('$buypage', 
			   // the following is the callback   
			   function(){ $('.overlayOuter').fadeIn(300); })
		}); */
		$("#pdialog").on("click", "#buypro", function(){
			var win = window.open('$buypage', '_blank');
			if (win) { win.focus(); }
			else { alert('popups blocked'); }
		}); 
EOS;
		}

		$output .= <<<EOS
	  $('#vupdate').on('click',function(e) {
			$('#vupdate').removeClass();
			$('#vupdate').addClass('loadingActive');
			$('#vupdate').text('$fetching');
			var data = new FormData();
			data.append('addon_code','$code');
			data.append('version','$version');
			data.append('action','addon_fetch_update');

            //create a new XMLHttpRequest
            var xhr = new XMLHttpRequest();     
            xhr.open('POST', 'sew_ajax.php', true);  
            xhr.send(data);
            xhr.onload = function () {
                //get response and show the status
                var response = JSON.parse(xhr.responseText);
                if(xhr.status === 200 && response.status == 'ok'){
					$('#vupdate').text('$installing');
					data.set('action','addon_install_update');
					data.append('filename',response.filename);

					var xhr2 = new XMLHttpRequest();     
					xhr2.open('POST', 'sew_ajax.php', true);  
					xhr2.send(data);
					xhr2.onload = function () {
						//get response and show the status
						var response = JSON.parse(xhr2.responseText);
						$('#vupdate').removeClass();
						if(xhr2.status === 200 && response.status == 'ok'){
							$('#vupdate').addClass('succeeded');
                			window.location.reload(true);
						}else{
							$('#vupdate').addClass('failed');
							// Handle errors here
							console.log('ERRORS: ' + response.error);
							console.log('LOG: ' + response.log);
							console.log('POST: ' + response.post);
							console.log('GET: ' + response.get);
						}
					};
				}else{
					$('#vupdate').removeClass();
					$('#vupdate').addClass('failed');
					// Handle errors here
					console.log('ERRORS: ' + response.error);
					console.log('LOG: ' + response.log);
					console.log('POST: ' + response.post);
					console.log('GET: ' + response.get);
                }
            };
	  });
EOS;
		if (!isset($this->last_check) || (time() - strtotime($this->last_check) > 60 * 60 * 24)) {
			$output .= <<<EOS
			var data = new FormData();
			data.append('addon_code','$code');
			data.append('action','addon_get_version');
            //create a new XMLHttpRequest
            var xhr = new XMLHttpRequest();     
            
            xhr.open('POST', 'sew_ajax.php', true);  
            xhr.send(data);
            xhr.onload = function () {
                //get response and show the status
                var response = JSON.parse(xhr.responseText);
                if(xhr.status === 200 && response.status == 'ok'){
                	window.location.reload(true);
				}else{
					// Handle errors here
					console.log('ERRORS: ' + response.error);
					console.log('LOG: ' + response.log);
					console.log('POST: ' + response.post);
					console.log('GET: ' + response.get);
                }
            };
EOS;
		}
		$output .= <<<EOS
	});
</script>
EOS;
		return $output;
	}
    
    private function getKey() {
        if (! defined('SEW_ADDONS_USER_TOKEN')) {
        	define('SEW_ADDONS_USER_TOKEN','');
            sew_add_config('SEW user token','SEW_ADDONS_USER_TOKEN','','token for addon shop - validated and updated automatically');
        }
        if (! defined('SEW_APPS_TOKENS')) {
        	define('SEW_APPS_TOKENS','');
            sew_add_config('SEW apps keys','SEW_APPS_TOKENS','','apps keys for this store - validated and updated automatically');
        } elseif (strlen(SEW_APPS_TOKENS)) {
        	$token_list = unserialize(SEW_APPS_TOKENS);
            if (array_key_exists($this->app_code,$token_list)) {
            	$this->app_key = $token_list[$this->app_code];
            }
        }
    }
    
    private function storeKey($token) {
    	$this->app_key = $token;
        if (! defined('SEW_APPS_TOKENS')) {
        	$token_list = array($this->app_code => $token);
            sew_add_config('SEW apps keys','SEW_APPS_TOKENS',serialize($token_list),'apps keys for this store - validated and updated automatically');
        } else {
        	if (strlen(SEW_APPS_TOKENS)) {
                $token_list = unserialize(SEW_APPS_TOKENS);
                $token_list[$this->app_code] = $token;
            } else {
        		$token_list = array($this->app_code => $token);
            }
            $var = serialize($token_list);
            tep_db_query("update configuration set configuration_value = '$var' where configuration_key = 'SEW_APPS_TOKENS'");
        }
    }
    
    private function deldir($dir) {
      foreach ( scandir($dir) as $file ) {
        if ( !in_array($file, array('.', '..')) ) {
          if ( is_dir($dir . '/' . $file) ) {
            $this->deldir($dir . '/' . $file);
          } else {
            unlink($dir . '/' . $file);
          }
        }
      }

      return rmdir($dir);
    }

	private function loadFromDb($row) {
		foreach ($row as $key => $value) {
			$this->{$key} = $value;
		}
	}
    
    //pinched from paypal app
    private function getDirectoryContents($base, &$result = array()) {
      foreach ( scandir($base) as $file ) {
        if ( ($file == '.') || ($file == '..') ) {
          continue;
        }

        $pathname = $base . '/' . $file;

        if ( is_dir($pathname) ) {
          $this->getDirectoryContents($pathname, $result);
        } else {
          $result[] = str_replace('\\', '/', $pathname); // Unix style directory separator "/"
        }
      }

      return $result;
    }

    private function isWritable($location) {
      if ( !file_exists($location) ) {
        while ( true ) {
          $location = dirname($location);

          if ( file_exists($location) ) {
            break;
          }
        }
      }

      return is_writable($location);
    }
    
}

if (! function_exists('sew_add_config')) {
	function sew_add_config($title,$key,$value,$description,$group = 6,$sort = 0,$set_func = null,$use_func = null) {
        $sql_data_array = array('configuration_title' => $title,
                                'configuration_key' => $key,
                                'configuration_value' => $value,
                                'configuration_description' => $description,
                                'configuration_group_id' => $group,
                                'sort_order' => $sort,
                                'date_added' => 'now()');
        if (tep_not_null($set_func)) {
            $sql_data_array['set_function'] = $set_func;
        }
        if (tep_not_null($use_func)) {
            $sql_data_array['set_function'] = $use_func;
        }
        tep_db_perform('configuration',$sql_data_array);
    }
}