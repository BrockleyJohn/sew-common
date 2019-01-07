<?php
    $error = false;
    $files = array();
		$html = '';
		$text = '';

    
//    require('includes/functions/static_pages.php');
//    $slug = filter_input ( INPUT_POST, 'slug', FILTER_SANITIZE_URL );
    $dir = filter_input ( INPUT_GET, 'dir', FILTER_SANITIZE_URL );
		$uploaddir = DIR_FS_CATALOG_IMAGES . $dir;
		if (! is_dir($uploaddir) ) {
	    $logging .= 'directory ' . $uploaddir . ' does not exist ' . "\n";
		  if (! @mkdir($uploaddir, 0775, true)) {
    	  $result['error'] = sprintf(ERROR_CREATING_DIR, $uploaddir);
			}
		}
//		} else {
//			require(DIR_FS_ADMIN.'includes/classes/upload.php');
/*			foreach($_FILES as $key => $value) {
				$t = new upload($key);
				$t->set_destination($uploaddir);
				if ($t->parse() && $t->save()) {
					$files[] = $uploaddir .$key;
				} else {
					$text .= ($error ? ', ' . $key : $key);
					$error = true;
				}
			} */
			foreach($_FILES as $file) {
				if(move_uploaded_file($file['tmp_name'], $uploaddir . '/' . $file['name'])) {
					$work = $dir . '/' . $file['name'];
					$html .= '<div style="float:left; text-align:center;" class="selectable"><img height="100" src="' . HTTPS_CATALOG_SERVER . DIR_WS_CATALOG_IMAGES . $work . '"/><br/>' . $work . "</div>\n";
					$dir . '/' . $file['name'];
	        $logging .= 'uploaded ' . $uploaddir . '/' . $file['name'] . "\n";
				} else {
					$text .= ($error ? ', ' . $file['name'] : $file['name']);
					$error = true;
				}
			} 
		  if ($error) {
			  $result['error'] = sprintf(ERROR_CREATING, $text);
			} else {
//			  $result['error'] = 'log anyway';
			  $result['status'] = 'ok';
			  $result['images'] = $html;
			}
//		}
		
