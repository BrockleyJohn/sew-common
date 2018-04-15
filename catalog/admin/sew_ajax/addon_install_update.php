<?php
	$error = false;
	
	if ((! isset($_POST['addon_code'])) || (! isset($_POST['version']))) {
		$error = true;
		$result['error'] = ERROR_MISSING_DATA;
	} else {
		$addon_code = filter_input ( INPUT_POST, 'addon_code', FILTER_SANITIZE_URL );
		$addon_version = filter_input ( INPUT_POST, 'version', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
		$filename = filter_input ( INPUT_POST, 'filename', FILTER_SANITIZE_URL );
		if (file_exists('SEW/' . $addon_code . '-version.php')) {
			include 'SEW/' . $addon_code . '-version.php';
			$addon = new \SEWC\addonVersion($addon_code, $addon_codebase, $addon_title, $addon_author);
			if ($addon->install($filename)) {
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
