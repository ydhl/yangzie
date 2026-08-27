<?php

namespace yangzie;

/**
 * 会话封装
 *
 * @liizii
 */
class YZE_Session_Context extends YZE_Object {
    /**
     * 单例实例
     *
     * @var YZE_Session_Context|null
     */
    private static $instance;

    /**
     * 私有构造函数，禁止外部直接实例化，请使用 get_instance() 获取单例
     */
    private function __construct() {
    }

    /**
     * 获取会话上下文单例
     *
     * @return YZE_Session_Context 会话上下文实例
     */
    public static function get_instance() {
        if (! isset ( self::$instance )) {
            $c = __CLASS__;
            self::$instance = new $c ();
        }
        return self::$instance;
    }

    /**
     * 从会话中获取指定 key 的数据
     *
     * @param string $key 会话数据键名
     * @return mixed 会话中的数据值，不存在时返回 null
     * @author liizii
     * @since 2009-12-10
     */
    public function get($key) {
        // ai@2026-05-27 替换 @ 抑制符，使用 ?? null 显式处理
        return $_SESSION['yze'][$key] ?? null;
    }

    /**
     * 在会话中设置指定 key 的数据
     *
     * @param string $key   会话数据键名
     * @param mixed  $value 需要设置的数据值
     * @return void
     * @author liizii
     * @since 2009-12-10
     */
    public function set($key, $value) {
        $_SESSION ['yze'][$key] = $value;
    }

    /**
     * 判断会话中是否存在指定的 key
     *
     * @param string $key 会话数据键名
     * @return bool 存在返回 true，否则返回 false
     */
    public function has($key) {
        // ai@2026-05-27 替换 @ 抑制符，使用 ?? [] 显式处理
        return array_key_exists($key, $_SESSION['yze'] ?? []);
    }

    /**
     * 删除会话中指定的 key，如果不指定 key 则删除全部通过 set 设置的数据
     *
     * @param string|null $key 要删除的会话数据键名，为 null 时清空全部
     * @return $this 返回当前会话上下文，支持链式调用
     * @author leeboo
     */
    public function destory($key = null) {
        if ($key) {
            unset ( $_SESSION ['yze'][$key] );
        } else {
            unset ( $_SESSION ['yze']);
        }
        return $this;
    }
}
?>
