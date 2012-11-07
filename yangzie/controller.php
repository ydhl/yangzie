<?php
/**
 * 资源控制器抽象基类，提供控制器的处理机制，子类控制器映射到具体的uri，具体处理请求的
 * action在子类中定义，该类为post，get，put，delete的请求做预处理，然后调用到对应的action
 * 子类的action如果没有返回IResponse则这里默认返回对应的Simple_View
 * 为view提供设置view中要使用的数据的方法。
 * 负责对post请求进行验证处理：多人同时修改，重复提交表单
 * 提供get，post，put，delete的hook
 * 定义视图的layout
 * 定义响应是否可以在浏览器上缓存:HttpCache
 *
 * @category Framework
 * @package  Yangzie
 * @author   liizii, <libol007@gmail.com>
 * @license  http://www.php.net/license/3_01.txt  PHP License 3.01
 * @link     www.yangzie.net
 */
abstract class Resource_Controller extends YangzieObject
{
    public $token;
    protected $view_data = array();
    protected $layout = 'default';
    /**
     * 控制器使用的model，格式：array("Model_Name","module_name.model_name")
     * @var array
     */
    protected $models = array();
    protected $module_name = "";

    /**
     * @var HttpCache
     */
    protected $cache_config;

    
    public function __construct()
    {
    	foreach ((array)$this->models as $model) {
    		if (stripos($model, ".")) {
    			$pathinfo = explode(".", $model);
    			include_once $pathinfo[0]."/models/".strtolower($pathinfo[1]).".class.php";
    			continue;
    		}
    		if ( @!include_once "{$this->module_name}/models/".strtolower($model).".class.php" ){
    			continue;
    		}
    		if (@!include_once APP_MODELS_INC."/".strtolower($model).".class.php") {
    			continue;
    		}
    	}
		
		
    	//init layout
    	$request = Request::get_instance();
    	if($request->get_output_format()){
    		$this->layout = $request->get_output_format();
    	}
    }
    
    public function get_Layout()
    {
        return $this->layout;
    }
    public function set_View_Data($name,$value)
    {
        $this->view_data[$name] = $value;
        return $this;
    }
    /**
     * 取得视图数据
     */
    public function get_View_Data()
    {
        return $this->view_data;
    }
    
    /**
     * 
     * 取得缓存的数据与设置的试图数据
     * 
     * @return Array array("cache"=>,"view"=>)
     */
    public function get_data()
    {
    	$request = Request::get_instance();
        $view_data['cache'] = Session::get_instance()->get_uri_datas($request->the_uri());
        $view_data['view'] = $this->get_view_data();
        return $view_data;
    }

    /**
     * 处理get方法.get方法用于显示界面,给出响应
     *
     * @access public
     * @author liizii, <libol007@gmail.com>
     * @return IResponse
     */
    public final function do_Get()
    {
        //设置请求token
        do_action(YZE_BEFORE_GET, $this);
        $request = Request::get_instance();
        Session::get_instance()->set_request_token($request->the_uri(), $request->the_request_token());
        if (!method_exists($this, "get")) {
            throw new Action_Not_Found_Exception("get");
        }

        $response = $this->get();
        
        $view_data['cache'] = Session::get_instance()->get_uri_datas($request->the_uri());
        $view_data['view'] = $this->get_view_data();
        if (!$response) {
        	$view = Request::get_instance()->view_path()."/".$request->controller();
			$format = $request->get_output_format();
        	if($format){
        		$view .= ".{$format}";
        	}
            $response = new Simple_View($view, $view_data, $this);
        }
        if(is_a($response, "View_Adapter")){
        	$response->check_view();
        }
        if (is_a($response, "Cacheable")) {
            $response->set_cache_config($this->cache_config);//内容协商的缓存控制
        }
        return $response;
    }

    /**
     * post方法.用于处理用户数据提交,提交成功后重定向
     *
     * @access public
     * @author liizii, <libol007@gmail.com>
     * @return IResponse
     */
    public final function do_Post()
    {
        do_action(YZE_BEFORE_POST, $this);
        return $this->_handle_post();
    }

    /**
     * put方法,更新数据
     *
     * @access public
     * @author liizii, <libol007@gmail.com>
     * @return IResponse
     */
    public final function do_Put()
    {
        do_action(YZE_BEFORE_PUT,$this);
        $request = Request::get_instance();
        $session = Session::get_instance();
        //多人同时提交表单
        $yze_model_id 		= $request->get_from_post("yze_model_id");
        $yze_modify_version = $request->get_from_post("yze_modify_version");
        $yze_model_name		= $request->get_from_post("yze_model_name");
        $yze_module_name	= $request->get_from_post("yze_module_name");

        $model = Model::find($yze_model_id, $yze_model_name);
        
        if(!$model) {
            throw new Resource_Not_Found_Exception(__("您要修改的内容不存在"));
        }
        include_once "{$yze_module_name}/models/".strtolower($yze_model_name).".class.php";
        
        if ($yze_modify_version != $model->get_version_value()) {
            throw new Model_Update_Conflict_Exception(vsprintf(__("数据已经在%s被更新了, 你编辑的数据是旧的，请刷新后重试"), array($model->get_version_value())));
        }

        return $this->_handle_post();
    }

    /**
     * 删除资源
     *
     * @access public
     * @author liizii, <libol007@gmail.com>
     * @return IResponse
     */
    public final function do_Delete()
    {
        do_action(YZE_BEFORE_DELETE, $this);
        $request = Request::get_instance();
        $session = Session::get_instance();
        //多人同时提交表单
        $yze_model_id       = $request->get_from_post("yze_model_id");
        $yze_modify_version = $request->get_from_post("yze_modify_version");
        $yze_model_name     = $request->get_from_post("yze_model_name");
        $yze_module_name    = $request->get_from_post("yze_module_name");

        if(empty($yze_model_id) || empty($yze_model_name)) {
            throw new Model_Update_Conflict_Exception(__("不知道要删除更新的模型名或者id"));
        }

        return $this->_handle_post();
    }

    /**
     * 处理post请求
     * @throws Action_Not_Found_Exception
     * @throws Form_Token_Validate_Exception
     */
    private function _Handle_Post()
    {
    	$session = Session::get_instance();
        $request = Request::get_instance();
        //保存post数据，以便在post不成功时重新显示出来
        $method = $request->method();
        if (!method_exists($this, $method)) {
            throw new Action_Not_Found_Exception($method);
        }
        //防止表单重复提交
        $this->_check_request_token($request->get_from_post('yze_request_token'));

        $response = $this->$method();
        //如果控制器中的方法没有return Redirect，默认通过get转到当前的uri
        if (!$response) {
            $response = new Redirect($request->the_uri(), $this);
        }
        //post后重定向，把post处理中设置的数据保存下来，重定向到新页面后再取出来显示
        //因为post不提供显示视图输出，所以这些数据需要在重定向后的get请求返回的视图中显示
        //这主要是post处理方法在向get方法中共享数据的方式
        //XXX ajax post的内容不需要重定向
        if ($this->get_view_data() && is_a($response, "Redirect")) {//有的post提交返回 的是Notpl_View
            Session::get_instance()->save_uri_datas($response->the_uri(), $this->get_view_data());
        }
        //成功处理，清除数据
        $session->clear_post_datas($request->the_uri());
		$session->clear_request_token_ext($request->the_uri());
        return $response;
    }
    
    private function _check_request_token($post_request_token)
    {
    	$session = Session::get_instance();
    	$request = Request::get_instance();
        $saved_token = $session->get_request_token($request->the_uri());

        //uri1中的表单提交到uri2中的情况
        $refer_saved_token = $session->get_request_token($request->the_referer_uri(true));
        $filtered_data  = do_filter("before_check_request_token", array("saved_token"=>$saved_token, "post_request_token"=>$post_request_token));
        $saved_token    = $filtered_data['saved_token'];
        $post_request_token = $filtered_data['post_request_token'];
        
        if (!$post_request_token) {
            throw new Form_Token_Validate_Exception(__("请求验证失败，出现该提示的原因可能是您点击过快，或者长时间没有操作，请重试(MISSING_POST_REQUEST_TOKEN)。"));
        }
        //$saved_token：j7ffqj40saoqerojp0pukrbar3_1300802801 $post_request_token：j7ffqj40saoqerojp0pukrbar3_1300802799
        //TODO 为什么会差1-2ms???
        if (strcasecmp($saved_token, $post_request_token)!=0 && strcasecmp($refer_saved_token, $post_request_token)!=0 ) {
        	YangzieObject::log("请求验证失败，出现该提示的原因可能是您点击过快，或者长时间没有操作，请重试。saveed_token: $saved_token post_request_token: $post_request_token");
            throw new Form_Token_Validate_Exception(__("请求验证失败，出现该提示的原因可能是您点击过快，或者长时间没有操作，请重试(REQUEST_TOKEN_NOT_MATCH)。"));
        }
    }
}
?>