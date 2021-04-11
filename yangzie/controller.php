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
 * 定义响应是否可以在浏览器上缓存:YZE_HttpCache
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
     * @var YZE_HttpCache
     */
    protected $cache_config;
    protected $session;
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
        $datas = $this->get_datas ();
        return @$datas [$name];
    }

    /**
     *
     * 取得缓存的数据与设置的视图数据
     *
     * @return Array array()
     */
    public function get_datas() {
        $request = $this->request;
        return $this->view_data;
    }
    public final function has_response_cache() {
        $cahce_file = YZE_APP_CACHES_PATH . $this->get_response_guid ();
        if (file_exists ( $cahce_file ) && $this->get_response_guid ()) {
            return file_get_contents ( $cahce_file );
        }
        return null;
    }

    /**
     * 如果该控制器的输出需要缓存（生成静态文件），该方法返回生成的换成的文件名，该文件名需要唯一，并且是根据所请求
     * 的信息来生成，保证在形同的请求信息下生成的文件名要一样
     *
     * @author leeboo
     * @return
     *
     */
    public function get_response_guid() {
        // pass
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

        $view_data  = $this->get_datas ();

        $class_name = strtolower ( get_class ( $this ) );
        $ref  = new \ReflectionObject ( $this );
        if($this->view){
        	$tpl  = $this->view;
        }else{
        	$tpl  = substr ( str_replace ( $ref->getNamespaceName () . "\\", "", $class_name ), 0, - 11 ) . "-" . $method;
        }

        $view = $view_tpl ? $view_tpl : $request->view_path () . "/" . $tpl;

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

        return $this->wrapResponse ( $this->$method () );
    }
    protected function wrapResponse($response) {
        $request = $this->request;
        if (! $response) {
            $response = $this->getResponse ();
        }

        if (is_a ( $response, "YZE_Cacheable" )) {
            $response->set_cache_config ( $this->cache_config ); // 内容协商的缓存控制
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
        \yangzie\YZE_Hook::do_hook ( YZE_ACTION_BEFORE_POST, $this );
        $request = $this->request;
        $method = $request->the_method ();
        $redirect = new YZE_Redirect ( $request->the_full_uri (), $this, $this->get_datas () );

        $response = $this->$method ();
        $format = $request->get_output_format();

        if (strcasecmp ( $format, "json" ) == 0) {
            $this->layout = "";
            return $response ?: $redirect;
        }

        if (strcasecmp ( $format, "iframe" ) == 0) {
            $this->layout = "";
            $res = $response ?: $redirect;
            return new YZE_Notpl_View ( "<script>window.parent.yze_iframe_form_submitCallback(" . json_encode ( $res->get_datas() ) . ");</script>", $this );
        }

        // 如果控制器中的方法没有return Redirect，默认通过get转到当前的uri
        if (! $response && !$this->view) {
            $response = $redirect;
        }else if($this->view){
        	$response = $this->getResponse();
        }

        return $response;
    }


    /**
     * 更新数据时检查数据是否是最新的
     *
     * @access public
     * @author liizii, <libol007@gmail.com>
     * @return YZE_IResponse
     */
    public final function check_model() {
        \yangzie\YZE_Hook::do_hook ( YZE_ACTION_BEFORE_PUT, $this );
        $request = $this->request;

        $yze_model_id = $request->get_from_post ( "yze_model_id" );
        $yze_modify_version = $request->get_from_post ( "yze_modify_version" );
        $yze_model_name = $request->get_from_post ( "yze_model_name" );
        $yze_module_name = $request->get_from_post ( "yze_module_name" );

        $model = $yze_model_name::find_by_id ( $yze_model_id );

        if (! $model) {
            throw new YZE_Resource_Not_Found_Exception ( __ ( "您要修改的内容不存在" ) );
        }

        if ($yze_modify_version != $model->get_version_value ()) {
            throw new YZE_Model_Update_Conflict_Exception ( vsprintf ( __ ( "数据已经在%s被更新了, 你编辑的数据是旧的，请刷新后重试" ), array (
                    $model->get_version_value ()
            ) ) );
        }
    }

    public final function do_exception(YZE_RuntimeException $e) {
        $request = $this->request;
        $request->setException($e);
        \yangzie\YZE_Hook::do_hook ( YZE_ACTION_BEFORE_DO_EXCEPTION, $this );
        $format = $request->get_output_format();
        $response = $this->exception ( $e );

        if($request->is_post()){
        	if (! $response && strcasecmp ( $format, "json" ) == 0) {
        		$this->layout = "";
        		return YZE_JSON_View::error($this, $e->getMessage());
        	}

        	if (! $response && strcasecmp ( $format, "iframe" ) == 0) {
        		$this->layout = "";
        		$response = YZE_JSON_View::error($this, $e->getMessage());
        		return new YZE_Notpl_View ( "<script>window.parent.yze_iframe_form_submitCallback(" . json_encode ( $response->get_datas() ) . ");</script>", $this );
        	}
        }else if (! $response) {
            $this->set_View_Data ( "exception", $e );
            $response = $this->getResponse ( YZE_APP_VIEWS_INC . "500" );
        }

        return $response;
    }

    /**
     * 根据控制器的处理逻辑，清空控制器在会话上下文中保存的数据
     */
    public function cleanup() {
        $request = $this->request;

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
}
class Yze_Default_Controller extends YZE_Resource_Controller {
    public function index() {
        $this->set_View_Data ( "yze_page_title", __ ( "Yangzie 简单的PHP开发框架" ) );
        return new YZE_Simple_View ( YANGZIE . "/welcome", $this->get_datas (), $this );
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
