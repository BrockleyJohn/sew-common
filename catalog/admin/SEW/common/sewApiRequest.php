<?php
/**
-
  wrapper for SEwebsites addon distribution api requests

  Author John Ferguson (@BrockleyJohn) john@sewebsites.net
  
	copyright  (c) 2017 SEwebsites

 * make api calls and handle responses
 * 
 */
namespace SEWC;

class sewApiRequest
{
	public $urlroot = 'https://api.sewebsites.net/';
	private $method = 'GET';
	private $url;
	private $query;
	private $bodyType = 'xml';
	
	public function __construct() {
	
	}
	
	public function getVersionInfo($addon) {
		$this->url = 'apps/' . $addon;
		return $this->doRequest();
	}
	
	public function showVersionInfo($addon,$version,$detail) {
		$this->url = 'apps/' . $addon . '?version=' . $version . '&detail=' . $detail;
		return $this->doRequest();
	}
	
	public function getUpdateFileName($addon,$version) {
		$this->url = 'apps/' . $addon . '?version=' . $version . '&type=incr';
		return $this->doRequest();
	}
	
	public function getAppToken($addon_code,$user_token) {
		$this->urlroot = 'https://sewebsites.net/oscommerce/';
		$this->url = 'app_token.php';
		$this->method = 'POST';
		$this->bodyType = 'form';
		$this->params = array('user_token' => $user_token, 'app_code' => $addon_code);
		return $this->doRequest();
	}
	
	public function getProFileName($app_code,$version_name,$user_key,$app_key) {
	
		$this->params = array('action' => $app_code, 'version' => $version_name, 'user_key' => $user_key, 'app_key' => $app_key);
	}
	
	private function doRequest() {
		$response = new sewApiResponse();
		
		$url = $this->urlroot . $this->url;
		if ($this->method == 'GET') $url .= $this->query;
		
		//initialise a CURL session
		$connection = curl_init();
		curl_setopt($connection, CURLOPT_URL, $url);
		curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($connection, CURLOPT_SSL_VERIFYHOST, 0);
		
		//set the headers using the array of headers
//		curl_setopt($connection, CURLOPT_HTTPHEADER, $this->headers);
		
		if ($this->method == 'POST') {
			//set method
			curl_setopt($connection, CURLOPT_POST, 1);
			
			//set the XML body of the request
			if ($this->bodyType == 'xml') {
				curl_setopt($connection, CURLOPT_POSTFIELDS, $this->XMLbody);
			} elseif ($this->bodyType == 'form') {
				curl_setopt($connection, CURLOPT_POSTFIELDS, http_build_query($this->params));
			}
		}
		
		//set it to return the transfer as a string from curl_exec
		curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);
		
		//Send the Request
		$result = curl_exec($connection);
		
		//close the connection
		curl_close($connection);

		$response->check($result);
		
		return $response;
	}
}