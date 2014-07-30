<?php
namespace yangzie;

/**
 * 数据验证基类
 * 
 * @category Framework
 * @package  Yangzie
 * @author   liizii <libol007@gmail.com>
 * @license  http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version  SVN: $Id$
 * @link     http://yangzie.yidianhulian.com
 *
 */
abstract class YZEValidate extends YZE_Object
{
    const REQUIRED = "REQUIRED";
    const NOT_EMPTY = "not_empty";
    const BETWEEN = "between";
    const ALPHANUMERIC = "alphaNumeric";
    const DATE_FORMAT = "date_format";
    const IS_EMAIL = "is_email";
    const EQUAL = "equal";
    const VERIFY_CODE = "verify_code";
    const REGEX = "regex";
    
    const DS_GET = "get";
    const DS_POST = "post";
    const DS_COOKIE = "cookie";
    const DS_SESSION = "session";
    
   /**
    * array(arg_name => array(
    * 		function,extra_value,message
    * ))
    * @var unknown
    */
   protected $validates = array();
   /**
    * 验证的结果
    * @var array(arg=>message);
    */
   protected $validate_result = array();

   protected  $request;
   
   public function __construct($request=null){
   	$this->request = $request ? $request : YZE_Request::get_instance();
   }
   
    /**
     * 验证方法，取得模块对于某个uri设置的数据验证规则，并一一验证它们
     *
     * @return bool
     *
     * @param string $request_method get|post|put|delete
     * 
     * @throws YZE_Request_Validate_Failed
     * 
     * @category Framework
     * @package  Yangzie
     * @author   liizii <libol007@gmail.com>
     */
    public function do_validate($request_method="get"){
    	foreach ((array)@$this->validates as $name => $rules) {
    		$function = $rules['function'];
    		$this->$function($request_method, $name, $rules['extra_value']);
    	}
	
        if ($this->validate_result) {
        	throw strcasecmp($request_method,"get")==0 ? new YZE_FatalException(join(PHP_EOL, $this->validate_result)) : new YZE_Request_Validate_Failed($this, join(PHP_EOL, $this->validate_result));
        }

        return true;
    }
    public function set_error_message($name, $msg, $append=false)
    {
    	if($append){
    		@$this->validate_result[$name] .= $msg;
    	}else{
    		@$this->validate_result[$name] = $msg;
    	}
    	return $this;
    }
    /**
     * 定义规则
     * 
     * @return void
     * 
     * @category Framework
     * @package  Yangzie
     * @author   liizii <libol007@gmail.com>
     */
    public abstract function init_get_validates();
    public abstract function init_post_validates();
    public abstract function init_put_validates();
    public abstract function init_delete_validates();
   
    /**
     * 绑定数据与验证规则
     * 
     * @param string $arg_name       要验证数据的名字
     * @param string $assert_function  验证方法 参数是$request_method, $name, $extra_value, 验证成功返回true，验证失败调用set_error_message($name, $message)设置错误消息
     * @param unknow $extra_value 需要用到的其它值
     * @param string $message    出错时显示的内容，如果$assert_function是自定义方法，则消息由这个方法返回，忽略该参数
     * 
     * @return bool
     * 
     * @category Framework
     * @package  Yangzie
     * @author   liizii <libol007@gmail.com>
     */
    public function assert($arg_name, $assert_function, $extra_value, $message)
    {
        $this->validates[$arg_name]['extra_value'] 	= $extra_value;
        $this->validates[$arg_name]['message'] 	= $message;
        $this->validates[$arg_name]['function'] 	= $assert_function;
        return $this;
    }

    /**
     * 设置正则验证
     * 
     * @author leeboo
     * 
     * @param unknown_type $name 验证的数据名
     * @param unknown_type $regular 验证表达式
     * @param unknown_type $message 错误消息
     * @return YZEValidate
     * 
     * @return
     */
    public function set_regular_validate_rule($name, $regular, $message){
    	$this->validates[$arg_name]['extra_value'] 	= $regular;
        $this->validates[$arg_name]['message'] 		= $message;
        $this->validates[$arg_name]['function'] 		= "regular_validate";
        
    	return $this;
    }
    
    /**
     * 取得某个字段的错误消息
     * 
     * @param unknown $request_method
     * @param unknown $name
     * 
     * 
     */
    public function get_validates(){
    	return $this->validates;
    }
    
    /**
     * 数据必需要提供
     * 
     * @param string $method 要验证的数据提交上来的方法 get，post
     * @param string $name   要验证的数据的名字
     * @param unknow $rule   用于对数据进行验证比较的值
     * 
     * @return   bool
     * 
     * @category Framework
     * @package  Yangzie
     * @author   liizii <libol007@gmail.com>
     */
    public function required($method, $name, $rule){
        if( ! array_key_exists($name, $this->get_datas($method))){
        	$this->set_error_message($name, $this->validates[$name]['message']);
        	return false;
        }
        return true;
    }
    
    public function regex($method, $name, $rule){
    	$datas = $this->get_datas($method);
    	if ( ! preg_match($rule, $datas[$name]) ){
    		$this->set_error_message($name, $this->validates[$name]['message']);
    		return false;
    	}
    	return false;
    }
    
    public function not_empty($method, $name, $rule){
    	$datas = $this->get_datas($method);
    	if (empty($datas[$name])){
    		$this->set_error_message($name, $this->validates[$name]['message']);
    		return false;
    	}
    	return true;
    }

    public function is_email($method, $name, $rule){
        $datas = $this->get_datas($method);
        if( ! filter_var(@$datas[$name], FILTER_VALIDATE_EMAIL)){
        	$this->set_error_message($name, $this->validates[$name]['message']);
        	return false;
        }
        return true;
    }

    public function equal($method, $name, $rule){
        $datas = $this->get_datas($method);
        if (@$datas[$name] != $rule){
        	$this->set_error_message($name, $this->validates[$name]['message']);
        	return false;
        }
        return true;
    }
    
    public function verify_code($method, $name, $rule){
    	$datas = $this->get_datas($method);
        if ( $rule != @$datas[$name] ){
        	$this->set_error_message($name, $this->validates[$name]['message']);
        	return false;
        }
        return true;
    }
    
    public function date_format($method, $name, $rule){
	$datas = $this->get_datas($method);
	if ( ! strtotime($datas[$name]) ){
       		$this->set_error_message($name, $this->validates[$name]['message']);
       		return false;
       }
       return true;
    }
    
    /**
     * 数据必需数字
     * 
     * @param string $method 要验证的数据提交上来的方法 get，post，cookie，session
     * @param string $name   要验证的数据的名字
     * @param unknow $rule   用于对数据进行验证比较的值
     * 
     * @return   bool
     * 
     * @category Framework
     * @package  Yangzie
     * @author   liizii <libol007@gmail.com>
     */
    public function alphanumeric($method, $name, $rule)
    {
        $datas = $this->get_datas($method);
        if ( ! is_numeric(@$datas[$name])){
        	$this->set_error_message($name, $this->validates[$name]['message']);
        	return false;
        }
        return true;
    }
    
    /**
     * 数据位于两个数之间
     * 
     * @param string $method 要验证的数据提交上来的方法 get，post，cookie，session
     * @param string $name   要验证的数据的名字
     * @param unknow $rule   用于对数据进行验证比较的值
     * 
     * @return   bool
     * 
     * @category Framework
     * @package  Yangzie
     * @author   liizii <libol007@gmail.com>
     */
    public function between($method, $name, $rule)
    {
        $datas = $this->get_datas($method);
        $number = intval(@$datas[$name]);
        if( $number>=$rule[0] && $number<=$rule[1] ){
        	return true;
        }
        $this->set_error_message($name, $this->validates[$name]['message']);
        return false;
    }
    
    /**
     * 取得请求的数据
     * 
     * @param string $method 要验证的数据的来源 get，post，cookie，session
     * 
     * @return   unknow
     * 
     * @category Framework
     * @package  Yangzie
     * @author   liizii <libol007@gmail.com>
     */
    public function get_datas($gpcs)
    {
        switch (strtolower($gpcs)){
        case "get":return $this->request->the_get_datas();
        case "post":
        case "put":return $this->request->the_post_datas();;
        case "cookie":return $_COOKIE;
        case "session":return $_SESSION;
        case "file":$_FILES;
        default:return array();
        }
    }
}
?>
