<?php 
namespace yangzie;

/**
 * 
 * @param unknown $remote
 * @param unknown $callable
 * @param unknown $args
 */
function yangzie_rpc_invoke($remote="localhost", $callable, $args){
	if( ! $remote || strcmp($remote,"localhost")===0){
		return yangzie_rpc_invoke_local($callable, $args);
	}
	return yangzie_rpc_invoke_remote($callable, $args);
}

function yangzie_rpc_invoke_remote($callable, $args){
	$client = new RPCClient();
}

function yangzie_rpc_invoke_local($callable, $args){

}


class RPCClient{

	/* Contains the last HTTP status code returned. */
	public $http_code;

	/* Contains the last API call. */
	public $url;

	/* Set timeout default. */
	public $timeout = 30;

	/* Set connect timeout. */
	public $connecttimeout = 30;

	/* Verify SSL Cert. */
	public $ssl_verifypeer = FALSE;


	/* Contains the last HTTP headers returned. */
	public $http_info;

	/* Set the useragnet. */
	public $useragent = 'yangzie';

	static $boundary = "yze.rpc";

	function post($url, $params) {
		$query = self::build_http_query_multi($params);
		return $this->http($url,'POST', $query);
	}

	public static function build_http_query_multi($params) {
		if (!$params) return '';

		// Urlencode both keys and values
		$keys = array_keys($params);
		$values = array_values($params);
		$params = array_combine($keys, $values);

		// Parameters are sorted by name, using lexicographical byte value ordering.
		// Ref: Spec: 9.1.1 (1)
		uksort($params, 'strcmp');
		$pairs = array();
		self::$boundary = $boundary = uniqid('------------------');
		$MPboundary = '--'.$boundary;
		$endMPboundary = $MPboundary. '--';
		$multipartbody = '';

		foreach ($params as $parameter => $value)
		{
			if( in_array($parameter,array("pic","image")) && $value{0} == '@' )
			{
				$url = ltrim( $value , '@' );
				$content = file_get_contents( $url );
				$filename = reset( explode( '?' , basename( $url ) ));
				$mime = self::get_image_mime($url);

				$multipartbody .= $MPboundary . "\r\n";
				$multipartbody .= 'Content-Disposition: form-data; name="' . $parameter . '"; filename="' . $filename . '"'. "\r\n";
				$multipartbody .= 'Content-Type: '. $mime . "\r\n\r\n";
				$multipartbody .= $content. "\r\n";
			}
			else
			{
				$multipartbody .= $MPboundary . "\r\n";
				$multipartbody .= 'content-disposition: form-data; name="'.$parameter."\"\r\n\r\n";
				$multipartbody .= $value."\r\n";
			}
		}
		$multipartbody .=  $endMPboundary;
		return $multipartbody;

	}
	
	/**
	 * Make an HTTP request
	 *
	 * @return API results
	 */
	function http($url, $method, $postfields = NULL) {
		$this->http_info = array();
		$ci = curl_init();
		/* Curl settings */
		curl_setopt($ci, CURLOPT_USERAGENT, $this->useragent);
		curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, $this->connecttimeout);
		curl_setopt($ci, CURLOPT_TIMEOUT, $this->timeout);
		curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ci, CURLOPT_HTTPHEADER, array('Expect:'));
		curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, $this->ssl_verifypeer);
		curl_setopt($ci, CURLOPT_HEADER, FALSE);

		curl_setopt($ci, CURLOPT_POST, TRUE);
		if (!empty($postfields)) {
			curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
		}

		$header_array = array("Content-Type: multipart/form-data; boundary=" . self::$boundary , "Expect: ");
		curl_setopt($ci, CURLOPT_HTTPHEADER, $header_array );
		curl_setopt($ci, CURLINFO_HEADER_OUT, TRUE );

		curl_setopt($ci, CURLOPT_URL, $url);
		$response = curl_exec($ci);
		$curl_info = curl_getinfo($ci);
		$this->http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
		$this->http_info = array_merge($this->http_info, curl_getinfo($ci));
		$this->url = $url;
		
		curl_close ($ci);
		return $response;
	}

}
?>