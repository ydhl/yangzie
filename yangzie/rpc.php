<?php 
namespace yangzie;

/**
 * RPC客户端
 * 
 * @author leeboo
 *
 */
class YangzieRPC extends YZE_Object{
	private $http_code;

	private $url;

	private $timeout = 30;

	private $connecttimeout = 30;

	private $ssl_verifypeer = FALSE;

	private $http_info;

	private $useragent = 'yangzie';

	private $boundary = "yze.rpc";

	
	public function __construct(){
		
	}


	/**
	 * 执行rpc调用
	 *
	 * @param unknown $remote
	 * @param unknown $callable
	 * @param unknown $args
	 */
	public function invoke( $callable, $args, $remote="localhost"){
		if( ! $remote || strcmp($remote,"localhost")===0){
			return $this->invoke_local($callable, $args);
		}
		return $this->invoke_remote($remote, $callable, $args);
	}
	
	/**
	 * 访问yze.rpc
	 * 
	 * @author leeboo
	 * 
	 * @param unknown $remote
	 * @param unknown $callable
	 * @param unknown $args
	 * @return Ambigous <\yangzie\API, mixed>
	 * 
	 * @return
	 */
	private function invoke_remote($remote, $callable, $args){
		return $this->post($remote, array("yze_method"=>"rpc", "callable"=>$callable, "args"=>json_encode($args)));
	}
	
	private function invoke_local($callable, $args){
		return call_user_func_array($callable, $args);
	}
	
	
	private function post($url, $params) {
		$query = $this->build_http_query_multi($params);
		return $this->http($url, $query);
	}

	private function build_http_query_multi($params) {
		if (!$params) return '';

		// Urlencode both keys and values
		$keys = array_keys($params);
		$values = array_values($params);
		$params = array_combine($keys, $values);

		// Parameters are sorted by name, using lexicographical byte value ordering.
		// Ref: Spec: 9.1.1 (1)
		uksort($params, 'strcmp');
		$pairs = array();
		$this->boundary = $boundary = uniqid('------------------');
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
	private function http($url, $postfields = NULL) {
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

		$header_array = array("Content-Type: multipart/form-data; boundary=" . $this->boundary , "Expect: ");
		curl_setopt($ci, CURLOPT_HTTPHEADER, $header_array );
		curl_setopt($ci, CURLINFO_HEADER_OUT, TRUE );

		curl_setopt($ci, CURLOPT_URL, rtrim($url, "/")."/yze.rpc");
		$response = curl_exec($ci);
		$curl_info = curl_getinfo($ci);
		$this->http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
		$this->http_info = array_merge($this->http_info, curl_getinfo($ci));
		$this->url = $url;
// 		print_r($curl_info);
		curl_close ($ci);
		return $response;
	}

}
?>