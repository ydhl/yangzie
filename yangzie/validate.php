<?php
/**
 * 数据验证基类
 * 
 * @category Framework
 * @package  Yangzie
 * @author   liizii <libol007@gmail.com>
 * @license  http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version  SVN: $Id$
 * @link     http://www.yangzie.net
 *
 */

/**
 * Enter description here ...
 * 
 * @category Framework
 * @package  Yangzie
 * @author   liizii <libol007@gmail.com>
 * @license  http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version  SVN: $Id$
 * @link     http://www.yangzie.net
 *
 */
abstract class YZEValidate extends YangzieObject
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
    
    public $validates = array();

    /**
     * 验证方法，取得模块对于某个uri设置的数据验证规则，并一一验证它们
     *
     * @return bool
     *
     * @param string $request_method get|post|put|delete
     * 
     * @throws Request_Validate_Failed
     * 
     * @category Framework
     * @package  Yangzie
     * @author   liizii <libol007@gmail.com>
     */
    public function do_validate($request_method="get")
    {
    	$failed = array();
    	foreach ((array)@$this->validates[$request_method] as $name => $rules) {
    		foreach ($rules as $rule => $rule_data) {
    			if (!$this->$rule($request_method, $name, $rule_data['value'])) {
    				$failed[$name] = $this->validates[$request_method][$name][$rule]['message'];
    			}
    		}
    	}
        if ($failed) {
        	throw new Request_Validate_Failed($failed, join(PHP_EOL, $failed));
        }

        return true;
    }
    public function set_error_message($method, $name, $rule, $msg)
    {
    	$this->validates[$method][$name][$rule]['message'] = $msg;
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
     * @param string $request_method      请求方法（Get,Post），浏览器上的PUT，DELETE也是POST请求 
     * @param string $name       要验证数据的名字
     * @param string $rule_name  验证规则名字
     * @param unknow $rule_value 用于对数据进行验证比较的值
     * @param string $message    出错时显示的内容
     * 
     * @return bool
     * 
     * @category Framework
     * @package  Yangzie
     * @author   liizii <libol007@gmail.com>
     */
    public function set_validate_rule($request_method, $name, $rule_name, $rule_value, $message)
    {
        $this->validates[$request_method][$name][$rule_name]['value'] = $rule_value;
        $this->validates[$request_method][$name][$rule_name]['message'] = $message;
        return $this;
    }

    /**
     * 设置正则验证
     * 
     * @author leeboo
     * 
     * @param unknown_type $request_method 请求方法，同时数据也从这里取得
     * @param unknown_type $name 验证的数据中
     * @param unknown_type $regular 验证表达式
     * @param unknown_type $message 错误消息
     * @return YZEValidate
     * 
     * @return
     */
    public function set_regular_validate_rule($request_method, $name, $regular, $message)
    {
    	$this->validates[$request_method][$name]["regular_validate"]['value'] = $regular;
    	$this->validates[$request_method][$name]["regular_validate"]['message'] = $message;
    	return $this;
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
    public function required($method, $name, $rule)
    {
        return array_key_exists($name, $this->get_datas($method));
    }
    
    public function regex($method, $name, $rule)
    {
    	$datas = $this->get_datas($method);
    	return preg_match($rule, $datas[$name]) ? true : false;
    }
    
    public function not_empty($method, $name, $rule)
    {
    	$datas = $this->get_datas($method);
    	return !empty($datas[$name]);
    }

    public function is_email($method, $name, $rule)
    {
        $datas = $this->get_datas($method);
        return filter_var(@$datas[$name], FILTER_VALIDATE_EMAIL);
    }

    public function equal($method, $name, $rule)
    {
        $datas = $this->get_datas($method);
        return @$datas[$name]==$rule;
    }
    
    public function verify_code($method, $name, $rule)
    {
    	$datas = $this->get_datas($method);
        return $rule == @$datas[$name];
    }
    
    public function date_format($method, $name, $rule)
    {
        $datas = $this->get_datas($method);
       return strtotime($datas[$name]);
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
        return is_numeric(@$datas[$name]);
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
        return $number>=$rule[0] && $number<=$rule[1];
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
        case "get":return $_GET;
        case "post":
        case "put":return $_POST;
        case "cookie":return $_COOKIE;
        case "session":return $_SESSION;
        case "file":$_FILES;
        default:return array();
        }
    }
}
?>
