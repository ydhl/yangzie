<?php
/**
 * 职责是担任HTTP客户端，负责构造OAuth get，post请求，并原样返回所请求的结果，请求的结果作何处理，由调用者决定
 * 
 * @author leeboo
 *
 */
class YDHLHttpClient{

	/* Contains the last HTTP status code returned. */
	private $http_code;

	/* Contains the last API call. */
	private $url;

	/* Set timeout default. */
	private $timeout = 120;

	/* Set connect timeout. */
	private $connecttimeout = 30;

	/* Verify SSL Cert. */
	private $ssl_verifypeer = FALSE;

	/* Respons format. */
	private $format = 'json';

	/* Decode returned json data. */
	private $decode_json = TRUE;

	/* Contains the last HTTP headers returned. */
	private $http_info;

	/* Set the useragnet. */
	private $useragent = 'ydhl';
	
	private $boundary = "ydhljfdafe1lqirgfmfn4rewpo";
	
	private $header = array();
	
	
	public function __construct($header = array()){
		$this->header = $header;
	}

	public function get($url, $params = array())
	{
		
		if($params){
			//url已经有?的情况
			$url .= (strrpos($url, "?")!==FALSE ? "&"  : "?").OAuthUtil::build_http_query($params);
		}
		//echo $url;
		$response = $this->http($url,'GET');
		return $response;
	}

	function post($url, $params = array(), $multi = false) {
		
		$query = "";
		if($multi){
			
			$query = OAuthUtil::build_http_query_multi($params);
		}else{
			$query = OAuthUtil::build_http_query($params);
		}
		$response = $this->http($url,'POST', $query, $multi);
		return $response;
	}
	/**
	 * Make an HTTP request
	 *
	 * @return API results
	 */
	function http($url, $method, $postfields = NULL, $multi = false) {
		$this->http_info = array();
		$ci = curl_init();
		/* Curl settings */
		curl_setopt($ci, CURLOPT_USERAGENT, $this->useragent);
		curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, $this->connecttimeout);
		curl_setopt($ci, CURLOPT_TIMEOUT, $this->timeout);
		curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);
		
		
		curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, $this->ssl_verifypeer);
		curl_setopt($ci, CURLOPT_HEADER, FALSE);
		curl_setopt($ci, CURLINFO_HEADER_OUT , true);

		switch ($method) {
			case 'POST':
				curl_setopt($ci, CURLOPT_POST, TRUE);
				if (!empty($postfields)) {
					curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
				}
				break;
			case 'DELETE':
				curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
				if (!empty($postfields)) {
					$url = "{$url}?{$postfields}";
				}
		}
		
		$header_array  = $this->header;
		$header_array[] = "Expect: ";
		
		if( $multi )
		{
			$header_array[] = "Content-Type: multipart/form-data; boundary=" . $this->boundary;
		} 
		
		curl_setopt($ci, CURLOPT_HTTPHEADER, $header_array );

		//echo $url;
		curl_setopt($ci, CURLOPT_URL, $url);
		
		$response = curl_exec($ci);
		$curl_info = curl_getinfo($ci);

		$this->http_code = $curl_info['http_code'];
		$this->http_info = array_merge($this->http_info, $curl_info);
		$this->url = $url;
		curl_close ($ci);
		//var_dump($url);var_dump($curl_info);var_dump($response);
		return $response;
	}

}