<?php

namespace yangzie;

/**
 * 资源控制器抽象基类，提供控制器的处理机制，子类控制器映射到具体的uri，具体处理请求
 * action在子类中定义，该类为post，get，put，delete的请求做预处理，然后调用到对应的action
 * 子类的action如果没有返回YZE_IResponse则这里默认返回对应的Simple_View，对于POST请求，如果没有指定response，则默认然后redirect，刷新当前uri
 * 为view提供设置view中要使用的数据的方法。
 * 负责对post请求进行验证处理：多人同时修改，重复提交表单
 * 提供get，post，put，delete的hook
 * 定义视图的layout
 *
 * @category Framework
 * @package Yangzie
 * @author liizii, <libol007@gmail.com>
 * @license http://www.php.net/license/3_01.txt PHP License 3.01
 * @link yangzie.yidianhulian.com
 */
abstract class YZE_Resource_Controller extends YZE_Object {
    protected $view_data = array ();
    protected $layout = 'tpl';
    protected $view = "";

    /**
     *
     * @var YZE_Request
     */
    protected $request;
    /**
     * 所在模块
     *
     * @var YZE_Base_Module
     */
    protected $module;
    public function __construct($request = null) {
        $this->request = $request ? $request : YZE_Request::get_instance ();
        $this->module = $this->request->module_obj ();
        // init layout
        if ($this->request->get_output_format ()) {
            $this->layout = $this->request->get_output_format ();
        }
    }
    public function getRequest() {
        return $this->request;
    }
    public function get_Layout() {
        return $this->layout;
    }
    public function set_View_Data($name, $value) {
        $this->view_data [$name] = $value;
        return $this;
    }
    /**
     * 取得视图数据
     */
    public function get_View_Data($name) {
        return @$this->view_data [$name];
    }

    /**
     *
     * @author leeboo
     *
     * @param string $view_tpl
     *            模板的路径
     * @param string $format
     * @return \yangzie\YZE_Simple_View
     *
     * @return
     *
     */
    protected function getResponse($view_tpl = null, $format = null) {
        $request = $this->request;
        $method  = $request->the_method();
        if($request->is_post()){
        	$method = substr($method, 5);
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
     * 处理get方法.get方法用于显示界面,给出响应，如果该url有异常，则进入exception处理
     *
     * @access public
     * @author liizii, <libol007@gmail.com>
     * @return YZE_IResponse
     */
    public final function do_Get() {
        $request = $this->request;
        $method = $request->the_method ();
        $response = $this->$method ();
        if (! $response) {
            $response = $this->getResponse ();
        }

        $format = $request->get_output_format();

        if (strcasecmp ( $format, "json" ) == 0) {
            $this->layout = "";
            return $response;
        }

        return $response;
    }

    /**
     * post方法.用于处理用户数据提交,提交成功后重定向
     *
     * @access public
     * @author liizii, <libol007@gmail.com>
     * @return YZE_IResponse
     */
    public final function do_Post() {
        $request = $this->request;
        $method = $request->the_method ();
        $redirect = new YZE_Redirect ( $request->the_full_uri (), $this, $this->view_data );

        $response = $this->$method ();
        $format = $request->get_output_format();

        if (strcasecmp ( $format, "json" ) == 0) {
            $this->layout = "";
            return $response ?: $redirect;
        }

        // 如果控制器中的方法没有return Redirect，默认通过get转到当前的uri
        if (! $response && !$this->view) {
            $response = $redirect;
        }else if($this->view){
        	$response = $this->getResponse();
        }

        return $response;
    }

    public final function do_exception(YZE_RuntimeException $e) {
        $request = $this->request;
        $request->set_Exception($e);
        \yangzie\YZE_Hook::do_hook ( YZE_ACTION_BEFORE_DO_EXCEPTION, $this );
        $format = $request->get_output_format();
        $response = $this->exception ( $e );

        if (strcasecmp ( $format, "json" ) == 0) {
            $this->layout = "";
            return YZE_JSON_View::error($this, $e->getMessage());
        }else if (! $response) {
            $this->set_View_Data ( "exception", $e );
            $response = $this->getResponse ( YZE_APP_VIEWS_INC . "500" );
        }

        return $response;
    }

    /**
     * 出现不可恢复的异常后的处理, 如何处理
     *
     * @author leeboo
     *
     * @param Exception $e
     *
     * @return YZE_IResponse
     */
    public function exception(YZE_RuntimeException $e) {
    }

    /**
     * 获取action上指定注解的值
     * @param $action
     */
    public function getAnnotatiaon($action, $annotation){
        $ref  = new \ReflectionObject ( $this );
        $methodRef = $ref->getMethod($action);
        if (!$methodRef) return $action;

        $comment = $methodRef->getDocComment();
        preg_match("/@{$annotation}\s(?P<name>.+)/i", $comment, $matches);
        return $matches['name'] ?: $action;
    }

    /**
     * 判断action上是否有指定注解
     * @param $action
     */
    public function hasAnnotatiaon($action, $annotation){
        $ref  = new \ReflectionObject ( $this );
        $methodRef = $ref->getMethod($action);
        if (!$methodRef) return $action;

        $comment = $methodRef->getDocComment();
        return preg_match("/@{$annotation}/i", $comment, $matches);
    }

}
class Yze_Default_Controller extends YZE_Resource_Controller {
    public function index() {
        $this->set_View_Data ( "yze_page_title", __ ( "Yangzie Framework" ) );
        return new YZE_Simple_View ( YANGZIE . "/welcome", $this->view_data, $this );
    }
}
class YZE_Exception_Controller extends YZE_Resource_Controller {
    private $exception;

    public function index() {
        $this->layout = "error";
        $this->output_status_code ( $this->exception ? $this->exception->getCode () : 0 );

        if (! $this->exception) {
            return new YZE_Simple_View ( YZE_APP_VIEWS_INC . "500", array (
                    "exception" => $this->exception
            ), $this );
        }

        return new YZE_Simple_View ( YZE_APP_VIEWS_INC . $this->exception->getCode (), array (
                "exception" => $this->exception
        ), $this );
    }
    public function exception(YZE_RuntimeException $e) {
        $this->exception = $e;
        return $this->index ();
    }


    private function output_status_code($error_number) {
        switch ($error_number) {
            case 404 :
                header ( "HTTP/1.0 404 Not Found" );
                return;
            case 500 :
            default:
                header ( "HTTP/1.0 500 Internal Server Error" );
                return;
        }
    }
}
?>
