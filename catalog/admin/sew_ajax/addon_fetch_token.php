<?php
	$error = false;
	
	if ((! isset($_POST['addon_code'])) || (! isset($_POST['version'])) || (! isset($_POST['token']))) {
		$error = true;
		$result['error'] = ERROR_MISSING_DATA;
	} else {
		$addon_code = filter_input ( INPUT_POST, 'addon_code', FILTER_SANITIZE_URL );
		$addon_version = filter_input ( INPUT_POST, 'version', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
		$token = preg_replace('/^a-zA-Z0-9_/','',$_POST['token']);
		$user_token = ($token == 'TOKEN_SET' ? SEW_ADDONS_USER_TOKEN : $token);
		if ($token <> 'TOKEN_SET') {
			if (defined('SEW_ADDONS_USER_TOKEN')) {
				tep_db_query('update configuration set configuration_value = "' . $token . '" where configuration_key = "SEW_ADDONS_USER_TOKEN"');
			} else {
        		define('SEW_ADDONS_USER_TOKEN',$token);
            	sew_add_config('SEW user token','SEW_ADDONS_USER_TOKEN',$token,'token for addon shop - validated and updated automatically');
			}
		}

		if (file_exists('SEW/' . $addon_code . '-version.php')) {
			include 'SEW/' . $addon_code . '-version.php';
			$addon = new \SEWC\addonVersion($addon_code, $addon_codebase, $addon_title, $addon_author);
			if ($addon->getAppToken($user_token)) {
				$result['status'] = 'ok';
			} else {
				$error = true;
				$result['error'] = ERROR_ACTION_FAILED;
			}
		} else {
			$error = true;
			$result['error'] = ERROR_MISSING_DATA;
		}
	}
