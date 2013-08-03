<?php
/**
 * 定义异常及错误码
 *
 * @author liizii
 *
 */

class YZE_Permission_Deny_Exception extends YZE_RuntimeException{
	public function __construct ($message = null) {
		parent::__construct($message, 500);
	}
	public function isResumeable(){
		return false;
	}
}
/**
 * 身份验证失败,表示需要用户登录
 *
 * @author liizii
 *
 */
class YZE_Auth_Failed_Exception extends YZE_RuntimeException{
	public function __construct ($message = null) {
		parent::__construct($message, 500);
	}
	public function isResumeable(){
		return false;
	}
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
class YZE_Resource_Not_Found_Exception extends YZE_RuntimeException{
	public function __construct ($message = null) {
		parent::__construct($message, 404);
	}
	public function isResumeable(){
		return false;
	}
}

class YZE_DBAException extends YZE_RuntimeException{
	public function __construct ($message = null) {
		parent::__construct($message, 500);
	}
	public function isResumeable(){
		return false;
	}
}
class YZE_RuntimeException extends Exception{
	public function __construct ($message = null) {
		parent::__construct($message, 500);
	}
	public function isResumeable(){
		return true;
	}
}


/**
 * Http 302 response
 * @author liizii
 *
 */
class YZE_Not_Modified_Exception extends YZE_RuntimeException{
	public function __construct ($message = null) {
		parent::__construct($message, 302);
	}
}


class YZE_Request_Validate_Failed extends YZE_RuntimeException{
	public $fields;
	/**
	 *
	 * @param unknown_type $fields 关联数组，键为表单项名，值为错误消息
	 * @param unknown_type $message
	 * @param unknown_type $code
	 * @param unknown_type $previous
	 */
	public function __construct($fields, $message)
	{
		parent::__construct($message, 500);
		$this->fields = $fields;
	}

}
class YZE_Model_Update_Conflict_Exception extends YZE_RuntimeException{
	public function __construct ($message = null) {
		parent::__construct($message, 500);
	}
}
class YZE_Form_Token_Validate_Exception extends YZE_RuntimeException{
	public function __construct ($message = null) {
		parent::__construct($message, 500);
	}
}
?>