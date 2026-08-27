<?php

namespace yangzie;

/**
 * 运行时异常
 * Class YZE_RuntimeException
 * @package yangzie
 */
class YZE_RuntimeException extends \Exception {
	/**
	 * @param string|null $message 异常消息
	 * @param int         $code    错误码，默认 500
	 */
    public function __construct($message = null, $code=500) {
        parent::__construct ( $message, intval($code),null );
    }
}
/**
 * 严重异常
 *
 * @author apple
 *
 */
class YZE_FatalException extends YZE_RuntimeException {
	/**
	 * @param string|null $message 异常消息
	 * @param int         $code    错误码，默认 500
	 */
    public function __construct($message = null, $code=500) {
        parent::__construct ( $message, intval($code));
    }
}

/**
 * 中止类型的异常，错误码500，这种异常不会进入controller的do exception中进行处理，比如
 * YZE_Need_Signin_Exception、YZE_Permission_Deny_Exception
 * Class YZE_Suspend_Exception
 * @package yangzie
 */
class YZE_Suspend_Exception extends YZE_FatalException{
	/**
	 * @param string|null $message 异常消息
	 * @param int         $code    错误码，默认 500
	 */
    public function __construct($message = null, $code=500) {
        parent::__construct ( $message, intval($code));
    }
}

/**
 * 需要登录的异常，属于中止类型异常（YZE_Suspend_Exception），
 * 抛出后直接中止请求流程，不会进入 controller 的异常处理方法
 *
 * @package yangzie
 */
class YZE_Need_Signin_Exception extends YZE_Suspend_Exception {
	/**
	 * @param string|null $message 异常消息
	 * @param int         $code    错误码，默认 500
	 */
    public function __construct($message = null, $code=500) {
        parent::__construct ( $message, intval($code));
    }
}

/**
 * 权限不足的异常，属于中止类型异常（YZE_Suspend_Exception），
 * 抛出后直接中止请求流程，不会进入 controller 的异常处理方法
 *
 * @package yangzie
 */
class YZE_Permission_Deny_Exception extends YZE_Suspend_Exception {
	/**
	 * @param string|null $message 异常消息
	 * @param int         $code    错误码，默认 500
	 */
    public function __construct($message = null, $code=500) {
        parent::__construct ( $message, intval($code));
    }
}

/**
 * 对于一些请求，所请求的对象不存在时的异常，这时将使用Error_Controller来处理
 * 所以是不可恢复的，由于请求的内容不存在，恢复后显示也是错的.
 *
 *
 * @author liizii
 *
 */
class YZE_Resource_Not_Found_Exception extends YZE_RuntimeException {
	/**
	 * @param string|null $message 异常消息
	 * @param int         $code    错误码，默认 404
	 */
    public function __construct($message = null, $code=404) {
        parent::__construct ( $message, intval($code));
    }
}
/**
 * 数据库访问异常，默认错误码 404
 *
 * @package yangzie
 */
class YZE_DBAException extends YZE_RuntimeException {
	/**
	 * @param string|null $message 异常消息
	 * @param int         $code    错误码，默认 404
	 */
	public function __construct($message = null, $code=404) {
	    parent::__construct ( $message, intval($code));
    }
}
/**
 * Http 302 response
 *
 * @author liizii
 *
 */
class YZE_Not_Modified_Exception extends YZE_RuntimeException {
	/**
	 * @param string|null $message 异常消息
	 * @param int         $code    错误码，默认 302
	 */
    public function __construct($message = null, $code=302) {
        parent::__construct ( $message, intval($code));
    }
}
/**
 * Model 更新冲突异常，默认错误码 500
 * 通常在乐观锁冲突（如并发修改同一记录）时抛出
 *
 * @package yangzie
 */
class YZE_Model_Update_Conflict_Exception extends YZE_RuntimeException {
	/**
	 * @param string|null $message 异常消息
	 * @param int         $code    错误码，默认 500
	 */
    public function __construct($message = null,$code=500) {
        parent::__construct ( $message, intval($code));
    }
}
?>
