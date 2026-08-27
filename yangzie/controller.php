<?php

namespace yangzie;

/**
 * 资源控制器抽象基类，提供控制器的处理机制，子类控制器的action映射到具体的uri，具体处理请求<br/>
 * 同一个url的request method映射到不同的action，<br/>
 * 比如GET /user 映射到User_Controller:index<br/>
 * 比如POST /user 映射到User_Controller:post_index<br/>
 * 比如DELETE /user 映射到User_Controller:delete_index<br/>
 * 也就是非get请求，则在action前面加上REQUEST_METHOD_<br/>
 * <br/><br/>
 * 对于OPTIONS请求，由于OPTIONS不是请求具体的业务逻辑只是对服务器的询问，只需要返回对应的header，任何实际输出内容都会被忽略，
 * 所以不需要有对应的options_action方法，只需要在request_headers中根据options询问的情况进行应答即可<br/>
 * 比如Access-Control-Request-Headers: content-type,x-product,<br/>
 * Access-Control-Request-Method: POST<br/>
 * 那么只需要返回对应的允许的header即可：Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization, token, Redirect, x-product
 * <br/><br/>
 * 可通过request->get_from_server()来获取http的头部，但是有所区别，
 * 比如如果request的headers是Access-Control-Request-Headers: content-type,x-product
 * 那么则这样取：$request->get_from_server('HTTP_ACCESS_CONTROL_REQUEST_HEADERS')
 *
 * @category Framework
 * @package Yangzie
 * @author liizii
 * @link yangzie.yidianhulian.com
 */
abstract class YZE_Resource_Controller extends YZE_Object {
    /**
     * 视图数据集合，key 为变量名，value 为变量值
     *
     * @var array
     */
    protected $view_data = array ();

    /**
     * 布局名，如 tpl、mob、json，对应对 app/vendor/layouts/{layout}.layout.php 文件
     *
     * @var string
     */
    protected $layout = 'tpl';

    /**
     * 视图模板名，非空时优先使用该模板而非默认模板
     *
     * @var string
     */
    protected $view = "";

    /**
     * 当前请求实例
     *
     * @var YZE_Request
     */
    protected $request;

    /**
     * 当前请求所在模块
     *
     * @var YZE_Base_Module
     */
    protected $module;
    /**
     * 返回当前请求对响应对象
     *
     * @author leeboo
     * @param string $view_tpl 模板的路径
     * @param string $format
     * @return \yangzie\YZE_Simple_View
     */
    private function get_Response($view_tpl = null, $format = null) {
        $request = $this->request;
        $method  = $request->the_method();
        if(!$request->is_get()){
            $method = preg_replace("/[^_]+?_/", "", $method, 1);
        }

        $view_data  = $this->view_data;

        if (!$view_tpl){
            $class_name = strtolower ( get_class ( $this ) );
            $ref  = new \ReflectionObject ( $this );
            if($this->view){
                $tpl  = $this->view;
            }else{
                $tpl  = substr ( str_replace ( $ref->getNamespaceName () . "\\", "", $class_name ), 0, - 11 ) . "-" . $method;
            }

            $view = $request->view_path () . "/" . $tpl;
        }else{
            $view = $view_tpl;
        }

        if (! $format) {
            $format = $request->get_output_format ();
        }
        return new YZE_Simple_View ( $view, $view_data, $this, $format );
    }

    /**
     * 构造函数，初始化请求与模块实例，并根据请求输出格式设置布局
     *
     * @param YZE_Request|null $request 请求实例，为 null 时取全局请求实例
     */
    public function __construct($request = null) {
        $this->request = $request ?: YZE_Request::get_instance ();
        $this->module = $this->request->module_instance ();
        // init layout
        if ($this->request->get_output_format ()) {
            $this->layout = $this->request->get_output_format ();
        }
    }

    /**
     * 当前请求实例
     * @return YZE_Request
     */
    /**
     * 获取当前请求实例
     *
     * @return YZE_Request 当前请求对象
     */
    public function get_Request() {
        return $this->request;
    }

    /**
     * 获取布局名，比如 tpl，则对应的是 app/vendor/layouts/tpl.layout.php 文件
     *
     * @return string 布局名
     */
    public function get_Layout() {
        return $this->layout;
    }

    /**
     * 设置视图数据
     *
     * @param string $name  视图变量名
     * @param mixed  $value 视图变量值
     * @return YZE_Resource_Controller 返回当前控制器对象，支持链式调用
     */
    public function set_View_Data($name, $value) {
        $this->view_data [$name] = $value;
        return $this;
    }

    /**
     * 获取视图数据
     *
     * @param string $name 视图变量名
     * @return mixed 视图变量值，不存在时返回 null
     */
    public function get_View_Data($name) {
        // ai@2026-05-27 替换 @ 抑制符，使用 ?? null 显式处理
        return $this->view_data[$name] ?? null;
    }


    /**
     * 子类重载设置响应头，可根据当前请求的信息做出区别对待
     * 比如
     * <pre>
     * [
     * "Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization, token, Redirect",
     * "Access-Control-Allow-Methods: GET, POST, PUT,DELETE,OPTIONS,PATCH",
     * "Access-Control-Allow-Origin: *"
     * ]
     * </pre>
     */
    public function response_headers(){
        return [];
    }

    /**
     * 调用映射的action方法并返回响应
     * @return YZE_Redirect|YZE_Simple_View
     */
    public final function handle_request(){
        $request = $this->request;
        $method = $request->the_method ();
        $redirect = new YZE_Redirect ( $request->the_full_uri (), $this, $this->view_data );

        $response = $this->$method ();
        if (! $response) {
            $response = $this->get_Response ();
        }

        $format = $request->get_output_format();

        if (strcasecmp ( $format, "json" ) == 0) {
            $this->layout = "";
        }
        return $response?:$redirect;
    }

    /**
     * 在action处理过程中出现的异常进入该方法，之类需要重载exception方法做具体的异常处理
     *
     * @param \Exception $e
     * @return YZE_IResponse|YZE_JSON_View|YZE_Simple_View
     */
    public final function do_exception(\Exception $e) {
        $request = $this->request;
        $request->set_Exception($e);
        \yangzie\YZE_Hook::do_hook ( YZE_HOOK_BEFORE_DO_EXCEPTION, $this );
        $format = $request->get_output_format();
        $response = $this->exception ( $e );

        if (strcasecmp ( $format, "json" ) == 0) {
            $this->layout = "";
            return YZE_JSON_View::error($this, $e->getMessage(), $e->getCode());
        }else if (! $response) {
            $this->set_View_Data ( "exception", $e );
            $response = $this->get_Response ( YZE_APP_VIEWS_INC . "500" );
        }

        return $response;
    }

    /**
     * 子类重载该方法对请求处理过程中出现对异常进行处理
     *
     * @author leeboo
     * @param Exception $e
     * @return YZE_IResponse
     */
    public function exception(\Exception $e) {
    }

    /**
     * 获取 action 上指定注解的值
     * <pre>
     * //@ test testvalue
     * public function index()
     * get_Annotation('index', 'test') 将返回 testvalue
     * </pre>
     * @param string $action     方法名
     * @param string $annotation 要检查的注解名
     * @return string|null 注解值，方法不存在或没有该注解时返回 null
     */
    public function get_Annotation($action, $annotation){
        try{
            $ref = new \ReflectionObject ($this);
            $methodRef = $ref->getMethod($action);
            if (!$methodRef) return null;

            $comment = $methodRef->getDocComment();
            preg_match("/@{$annotation}\s(?P<name>.+)/i", $comment, $matches);
            // ai@2026-05-27 替换 @ 抑制符，使用 ?? null 显式处理
            return $matches['name'] ?? null;
        }catch (\Exception $e){
            return null;
        }
    }

    /**
     * 判断 action 上是否有指定注解
     *
     * @param string $action     方法名
     * @param string $annotation 要检查的注解名
     * @return bool 存在该注解返回 true，否则返回 false
     */
    public function has_Annotation($action, $annotation){
        try{
            $ref  = new \ReflectionObject ( $this );
            $methodRef = $ref->getMethod($action);
            if (!$methodRef) return false;

            $comment = $methodRef->getDocComment();
            return preg_match("/@{$annotation}/i", $comment) ? true : false;
        }catch (\Exception $e) {
            return false;
        }
    }

}
/**
 * 默认控制器，当请求无法匹配到任何模块与控制器时使用
 *
 * @package yangzie
 */
class Yze_Default_Controller extends YZE_Resource_Controller {
    /**
     * 显示框架欢迎页
     *
     * @return YZE_Simple_View 欢迎页视图
     */
    public function index() {
        $this->set_View_Data ( "yze_page_title", __ ( "Yangzie Framework" ) );
        return new YZE_Simple_View ( YANGZIE . "welcome", $this->view_data, $this );
    }
}

/**
 * 异常控制器，用于统一处理请求过程中的异常并输出对应的错误页面
 *
 * @package yangzie
 */
class YZE_Exception_Controller extends YZE_Resource_Controller {
    /**
     * 需要展示的异常对象
     *
     * @var \Exception|null
     */
    private $exception;

    /**
     * 根据异常错误码输出对应的错误页面
     *
     * @return YZE_Simple_View 错误页面视图
     */
    public function index() {
        $this->layout = "error";
        $this->output_status_code ( $this->exception ? $this->exception->getCode () : 0 );

        if (! $this->exception) {
            return new YZE_Simple_View ( YZE_APP_VIEWS_INC . "500", array (
                    "exception" => $this->exception
            ), $this );
        }

        $errorCode = $this->exception->getCode ();
        if (!file_exists(YZE_APP_VIEWS_INC . $errorCode .".tpl.php")){
            $errorCode = 500;
        }
        return new YZE_Simple_View ( YZE_APP_VIEWS_INC . $errorCode, array (
                "exception" => $this->exception
        ), $this );
    }
    /**
     * 保存异常并输出错误页面
     *
     * @param \Exception $e 异常对象
     * @return YZE_Simple_View 错误页面视图
     */
    public function exception(\Exception $e) {
        $this->exception = $e;
        return $this->index ();
    }

    /**
     * 根据错误码输出对应的 http 状态码响应头
     *
     * @param int $error_number 错误码，404 或 500
     * @return void
     */
    private function output_status_code($error_number) {
        switch ($error_number) {
            case 404 :
                header ( "HTTP/1.0 404 Not Found" );
                return;
            case 500 :
            default:
                header ( "HTTP/1.0 500 Internal Server Error" );
        }
    }
}
?>
