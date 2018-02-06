<?php
/**
-
  wrapper for SEwebsites addon distribution api responses

  Author John Ferguson (@BrockleyJohn) john@sewebsites.net
  
	copyright  (c) 2017 SEwebsites

 * make api calls and handle responses
 * 
 */
namespace SEWC;

class sewApiResponse
{
	public $OK = false;
	
	public function __construct() {
	
	}
	
	public function check($result) {
		$this->data = json_decode($result);
		$error = json_last_error();
		if (! $this->OK = ($error == JSON_ERROR_NONE)) {
			$this->setJsonError($error);
		}
		return $this->OK;
	}
	
	private function setJsonError($error) {
		$codes = array(
			'JSON_ERROR_DEPTH' => JSON_ERROR_DEPTH,
			'JSON_ERROR_STATE_MISMATCH' => JSON_ERROR_STATE_MISMATCH,
			'JSON_ERROR_CTRL_CHAR' => JSON_ERROR_CTRL_CHAR,
			'JSON_ERROR_SYNTAX' => JSON_ERROR_SYNTAX,
			'JSON_ERROR_UTF8' => JSON_ERROR_UTF8,
			'JSON_ERROR_RECURSION' => JSON_ERROR_RECURSION,
			'JSON_ERROR_INF_OR_NAN' => JSON_ERROR_INF_OR_NAN,
			'JSON_ERROR_UNSUPPORTED_TYPE' => JSON_ERROR_UNSUPPORTED_TYPE,
			'JSON_ERROR_INVALID_PROPERTY_NAME' => JSON_ERROR_INVALID_PROPERTY_NAME,
			'JSON_ERROR_UTF16' => JSON_ERROR_UTF16,
		);
		foreach ($codes as $key => $value) {
			if ($value == $error) {
				$this->error = $key;
				return;
			}
		}
	}
}