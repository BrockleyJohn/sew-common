<?php
	$error = false;
	
	if (! isset($_POST['addon_code'])) {
		$error = true;
		$result['error'] = ERROR_MISSING_DATA;
	} else {
		$addon_code = filter_input ( INPUT_POST, 'addon_code', FILTER_SANITIZE_URL );
		if (file_exists('SEW/' . $addon_code . '-version.php')) {
			include 'SEW/' . $addon_code . '-version.php';
			$addon = new \SEWC\addonVersion($addon_code, $addon_codebase, $addon_title, $addon_author);
			if ($addon->check()) {
				$result['status'] = 'ok';
			}
		} else {
			$error = true;
			$result['error'] = ERROR_MISSING_DATA;
		}
	}
