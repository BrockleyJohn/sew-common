<?php
	$error = false;
	
	if (! isset($_POST['addon_code'])) {
		$error = true;
		$result['error'] = ERROR_MISSING_DATA;
	} else {
		$addon_code = filter_input ( INPUT_POST, 'addon_code', FILTER_SANITIZE_URL );
		$addon_version = filter_input ( INPUT_POST, 'version', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
		$detail = filter_input ( INPUT_POST, 'detail', FILTER_SANITIZE_URL); 
		if (file_exists('SEW/' . $addon_code . '-version.php')) {
			include 'SEW/' . $addon_code . '-version.php';
			$addon = new \SEWC\addonVersion($addon_code, $addon_codebase, $addon_title, $addon_author);
			if ($return = $addon->getInfo($detail)) {
				$result['status'] = 'ok';
				$result['html'] = $return;
			}
		} else {
			$error = true;
			$result['error'] = ERROR_MISSING_DATA;
		}
	}
