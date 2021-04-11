<?php

namespace yangzie;

/**
 * 会话封装
 *
 * @liizii
 */
class YZE_Session_Context {
    private static $instance;
    private function __construct() {
    }

    /**
     *
     * @return YZE_Session_Context
     */
    public static function get_instance() {
        if (! isset ( self::$instance )) {
            $c = __CLASS__;
            self::$instance = new $c ();
        }
        return self::$instance;
    }

    /**
     * 在会话中保存数据，为了避免与一些系统使用会话冲突，key都hash过
     *
     * @param
     *            $key
     * @return unknown_type
     * @author liizii
     * @since 2009-12-10
     */
    public function get($key) {
        return @$_SESSION ['yze'][sha1 ( $key )];
    }
    public function set($key, $value) {
        $_SESSION ['yze'][sha1 ( $key )] = $value;
    }
    public function has($key) {
        return array_key_exists ( sha1 ( $key ), @$_SESSION ['yze']);
    }
    /**
     * 删除指定的key，如果不指定key则全部session都将被删除
     *
     * @author leeboo
     *
     * @param string $key
     *
     * @return
     *
     */
    public function destory($key = null) {
        if ($key) {
            unset ( $_SESSION ['yze'][sha1 ( $key )] );
        } else {
            unset ( $_SESSION ['yze']);
        }
    }
}
?>
