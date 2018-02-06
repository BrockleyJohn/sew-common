<?php
	$error = false;
	
	if ((! isset($_POST['addon_code'])) || (! isset($_POST['version']))) {
		$error = true;
		$result['error'] = ERROR_MISSING_DATA;
	} else {
		$addon_code = filter_input ( INPUT_POST, 'addon_code', FILTER_SANITIZE_URL );
		$addon_version = filter_input ( INPUT_POST, 'version', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
		if (file_exists('SEW/' . $addon_code . '-version.php')) {
			include 'SEW/' . $addon_code . '-version.php';
			$addon = new \SEWC\addonVersion($addon_code, $addon_codebase, $addon_title, $addon_author);
			if ($filename = $addon->getUpdate($addon_version)) {
				$result['status'] = 'ok';
				$result['filename'] = $filename;
			} else {
				$error = true;
				$result['error'] = ERROR_ACTION_FAILED;
			}
		} else {
			$error = true;
			$result['error'] = ERROR_MISSING_DATA;
		}
	}
