<?php
/**
 * Super Simple Web Services Client to Consume REST-ful APIs
 *
 * Actions: GET, POST, PUT, DELETE, OPTIONS
 *
 * GETS and DELETES can use 2 request styles:
 * A. Route style /user/1
 * B. Or use Traditional /user?id=1 style urls by passing the base route
 * and a params hash as the optional second argument
 *
 * POSTs and PUTs pass info as a hash via the optional second argument
 *
 * Dependencies: PHP 5.3+, curl_lib
 * @author Kayla Rose Martin
 * @since Dec, 2010
 * @license MIT
 */
class KMHttp {
	private $_host;
	private $_headers;
	private $_response;
	
	protected $_lib_opts = array(
		//Return result instead of output to buffer
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_HEADER => 0,
		//Adhere to Redirects
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_MAXREDIRS      => 5
	);
	
	//Available Actions
	const POST 		= 'POST';
	const GET 		= 'GET';
	const PUT 	 	= 'PUT';
	const DELETE 	= 'DELETE';
	const OPTIONS 	= 'OPTIONS';
	
	/**
	 * Constructors
	 */
	//Standard Constructor $c = new KMHttp($host); $d = new KMHttp;
	public function __construct($host = NULL)
	{
		self::_satisfy_or_die();
		if($host)
		{
			return $this->set_host($host);
		}
		return $this;
	}
	//Static Constructor $c = KMHttp::at($host);
	public static function at($host)
	{
		return new KMHttp($host);
	}
	
	/**
	 * Accessors w Chainable Setters
	 */
	public function host()
	{
		return $this->_host;
	}
	public function set_host($host)
	{
		$this->_host = $host;
		return $this;
	}
	public function headers()
	{
		return $this->_headers;
	}
	public function set_headers($headers)
	{
		$this->_headers = $headers;
		return $this;
	}
	
	/**
	 * Available User Actions
	 */
	public function options($route, $params = array())
	{
		return $this->_do(self::OPTIONS, $route, $params);
	}
	public function get($route, $params = array())
	{
		return $this->_do(self::GET, $route, $params);
	}
	public function post($route, $params = array())
	{
		return $this->_do(self::POST, $route, $params);
	}
	public function put($route, $params = array())
	{
		return $this->_do(self::PUT, $route, $params);
	}
	public function delete($route, $params = array())
	{
		return $this->_do(self::DELETE, $route, $params);
	}
	
	/**
	 * Action History
	 */
	public function last_status()
	{
		if($this->_response && $headers = $this->_response['headers'])
		{
			return (isset($headers['http_code'])) ?  $headers['http_code'] : NULL;
		}
		return NULL;
	}
	public function last_response()
	{
		if($this->_response && $body = $this->_response['body'])
		{
			return $body;
		}
		return NULL;
	}
	
	
	
	/**
	 * Implementation Details
	 */
	//Action Performer
	protected function _do($verb, $route, $params)
	{
		$session = curl_init();
		$url = $this->_url_for_request($verb, $route, $params);
		
		//Set Request URL and default options
		curl_setopt($session, CURLOPT_URL, $url);
		curl_setopt_array($session, $this->_lib_opts);
		
		//Support PUT, POST, and DELETE
		if($this->_is_custom_action($verb))
		{
			curl_setopt($session, CURLOPT_CUSTOMREQUEST, $verb);
		}
		//Support POST and PUT data
		if($this->_is_poststyle_action($verb))
		{
			curl_setopt($session, CURLOPT_POSTFIELDS, $params);
		}
		//Support Custom Headers
		$headers = $this->headers();
		if($headers && !empty($headers))
		{
			curl_setopt($session, CURLOPT_HTTPHEADER, $headers);
		}
		if( ! $data = curl_exec($session))
	    {
	        trigger_error(curl_error($session));
	    }
		$this->_response = array('headers' => curl_getinfo($session), 'body' => $data);
		curl_close($session);
		return $data;
	}
	
	//Verb Helpers
	protected function _url_for_request($verb, $route, $params)
	{
		//Support traditional /user?id=1 style for GET and DELETE
		if((strtoupper($verb) === self::GET || strtoupper($verb) === self::DELETE) && !empty($params))
		{
			$route = $route . '?' . http_build_query($params);
		}
		if($this->host())
		{
			//If we have a common base host setup vs the user padding in full htt://host.com/user/1 urls
			$route = $this->host() . $route;
		}
		return $route;
	}
	protected function _is_custom_action($verb)
	{
		return (strtoupper($verb) === self::PUT || strtoupper($verb) === self::DELETE || strtoupper($verb) === self::POST);
	}
	protected function _is_poststyle_action($verb)
	{
		return (strtoupper($verb) === self::POST || strtoupper($verb) === self::PUT);
	}
	
	//Env. Validators
	protected static function _satisfy_or_die()
	{
		if(!function_exists('curl_init'))
		{
			echo 'Your environment does not satisfy this libraries requirements. Missing dependency: cURL PHP Library';
			die(187);
		}
		return TRUE;
	}
}