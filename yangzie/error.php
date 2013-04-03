<?php
/**
 * 定义系统使用到的异常处理，把一个请求中产生的异常分为了两类：
 * 可恢复的异常 YZE_Resume_Exception：
 * 	通常这些异常指在一个请求方法处理失败后返回之前的请求方法，以便重新请求。
 * 	比如一个表单的提交，首先是get请求得到表单界面，然后表单提交的时候通过post提交，
 * 	如果post处理出现的异常是可恢复的，那么异常处理是把状态保存下来，并恢复到之前的get请求，
 * 	也就是重新请求表单界面，并在上面把异常提示出来，用户判断是那里出问题后重新post提交表单。
 * 
 * 不可恢复的异常 YZE_Unresume_Exception：
 *  指出现异常后，不需要回到之前的请求去，以便重试的异常处理。
 * 	出现这些异常后，代码的控制权将交给Error_Controller，它根据发现的异常输出错误，
 * 	这里没有重新请求的动作，错误在当前的请求中就输出出来了
 * 	一些没有视图输出的情况也是通过不可恢复的异常来实现的，比如输出302 not modified头
 * 
 * @author liizii
 *
 */

/**
 * 不可恢复的异常处理, 显示异常处理界面
 * @author liizii
 *
 */
class YZE_Unresume_Exception extends \Exception{
	/**
	 * 返回异常的错误号，与http状态码相对应，如500 404等
	 * 
	 * @author leeboo
	 * 
	 * @return integerr
	 */
	public function error_number(){return 500;}
}
class YZE_View_Not_Found_Exception extends YZE_Unresume_Exception{
	public function error_number(){return 404;}
}
class YZE_Layout_Not_Found_Exception extends YZE_Unresume_Exception{
	public function error_number(){return 404;}
}

class YZE_Action_Not_Found_Exception extends YZE_Unresume_Exception{
	public function error_number(){return 404;}
}
class YZE_Controller_Not_Found_Exception extends YZE_Unresume_Exception{
	public function error_number(){return 404;}
}
class YZE_Not_Found_Class_Exception extends YZE_Unresume_Exception{
	public function error_number(){return 404;}
}
class YZE_Permission_Deny_Exception extends YZE_Unresume_Exception{
	public function error_number(){return 500;}
}
/**
 * 身份验证失败,表示需要用户登录
 * 
 * @author liizii
 *
 */
class YZE_Auth_Failed_Exception extends YZE_Unresume_Exception{
	public function error_number(){return 500;}
}

/**
 * 对于一些请求，所请求的对象不存在时的异常，这时将使用Error_Controller来处理
 * 所以是不可恢复的，由于请求的内容不存在，恢复后显示也是错的.
 * 
 * 在validate中验证时，可抛出该异常
 * 
 * @author liizii
 *
 */
class YZE_Resource_Not_Found_Exception extends YZE_Unresume_Exception{
	public function error_number(){return 404;}
}

class YZE_DBAException extends YZE_Unresume_Exception{
	public function error_number(){return 500;}
}
class YZE_RuntimeException extends YZE_Unresume_Exception{
	public function error_number(){return 500;}
}
/**
 * Http 302 response
 * @author liizii
 *
 */
class YZE_Not_Modified_Exception extends YZE_Unresume_Exception{
	public function error_number(){return 302;}
}

/**
 * 可恢复的异常处理， 显示原界面
 * @author liizii
 *
 */
class YZE_Resume_Exception extends \Exception{}
class YZE_Request_Validate_Failed extends YZE_Resume_Exception{
	public $fields;
	/**
	 *
	 * @param unknown_type $fields 关联数组，键为表单项名，值为错误消息
	 * @param unknown_type $message
	 * @param unknown_type $code
	 * @param unknown_type $previous
	 */
	public function __construct($fields, $message, $code=0)
	{
		parent::__construct($message, $code);
		$this->fields = $fields;
	}
}
class YZE_Model_Update_Conflict_Exception extends YZE_Resume_Exception{}
class YZE_Form_Token_Validate_Exception extends YZE_Resume_Exception{}
?>