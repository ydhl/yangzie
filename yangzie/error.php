<?php

namespace yangzie;

/**
 * 定义异常及错误码
 *
 * @author liizii
 *        
 */


class YZE_RuntimeException extends \Exception {
    public function __construct($message = null) {
        parent::__construct ( $message, 500 );
    }
}
/**
 * 严重异常
 *
 * @author apple
 *
 */
class YZE_FatalException extends YZE_RuntimeException {
    public function __construct($message = null) {
        parent::__construct ( $message, 500 );
    }
}


class YZE_Suspend_Exception extends YZE_FatalException{
    public function __construct($message = null) {
        parent::__construct ( $message, 500 );
    }
}

class YZE_Need_Signin_Exception extends YZE_Suspend_Exception {
    public function __construct($message = null) {
        parent::__construct ( $message, 500 );
    }
}

class YZE_Permission_Deny_Exception extends YZE_Suspend_Exception {
    public function __construct($message = null) {
        parent::__construct ( $message, 500 );
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
    public function __construct($message = null) {
        parent::__construct ( $message, 404 );
    }
}
class YZE_DBAException extends YZE_RuntimeException {
    public function __construct($message = null) {
        parent::__construct ( $message, 500 );
    }
}
/**
 * Http 302 response
 * 
 * @author liizii
 *        
 */
class YZE_Not_Modified_Exception extends YZE_RuntimeException {
    public function __construct($message = null) {
        parent::__construct ( $message, 302 );
    }
}
class YZE_Model_Update_Conflict_Exception extends YZE_RuntimeException {
    public function __construct($message = null) {
        parent::__construct ( $message, 500 );
    }
}
?>