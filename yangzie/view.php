<?php

namespace yangzie;

/**
 * 表示一个HTTP请求的响应结果。可能是可查看的内容，比如html，xml，json，yaml等，
 * 也可以只是一些http响应头，比如 301 redirect，304 not modified等
 *
 * @access public
 * @author liizii
 */
interface YZE_IResponse {
    /**
     * 输出响应,
     * return 为true表示返回不输出
     */
    public function output($return = false);

    /**
     * 取得控制器设置在响应中的值
     *
     * @package $key
     */
    public function get_data($key);
}
/**
 * 只输出http头，无message－body，表示请求的内容没有修改，客户端应该使用缓存的内容。
 *
 * @author liizii
 *
 */
class YZE_Response_304_NotModified extends YZE_Object implements YZE_IResponse {
    /**
     * 需要输出的 http 响应头集合，key 为头部名，value 为头部值
     *
     * @var array
     */
    private $headers;

    /**
     * @var YZE_Resource_Controller
     */
    private $controller;

    /**
     * 构造函数
     *
     * @param array                   $headers    要输出的 http 响应头
     * @param YZE_Resource_Controller $controller 发起响应的控制器
     */
    public function __construct($headers, YZE_Resource_Controller $controller) {
        $this->headers = $headers;
        $this->controller = $controller;
    }

    /**
     * 输出 304 状态码及配置的响应头
     *
     * @param bool $return 忽略，304 响应始终直接输出
     * @return void
     */
    public function output($return = false) {
        header ( "HTTP/1.1 304 Not Modified" );
        foreach ( ( array ) $this->headers as $name => $value ) {
            header ( "{$name}: {$value}" );
        }
    }

    /**
     * 增加一个响应头
     *
     * @param string $header_name  响应头名称
     * @param string $header_value 响应头值
     * @return void
     */
    public function add_header($header_name, $header_value) {
        $this->headers [$header_name] = $header_value;
    }

    /**
     * 获取指定响应头的值
     *
     * @param string $key 响应头名称
     * @return mixed 响应头值
     */
    public function get_data($key) {
        return $this->headers [$key];
    }
}
/**
 * HTTP Location:重定向，表示一次请求的处理输出是重定向到一个新地址
 *
 * destinationURI: 目标url
 * destinationController: 目标控制器
 *
 * @author liizii
 *
 */
class YZE_Redirect extends YZE_Object implements YZE_IResponse {
    /**
     * 重定向的目标 url
     *
     * @var string
     */
    private $destinationURI;

    /**
     * 发起重定向的控制器
     *
     * @var YZE_Resource_Controller
     */
	private $sourceController;

    /**
     * 输出重定向
     *
     * @param string $destination_uri
     * @param YZE_Resource_Controller $source_controller
     *
     */
    public function __construct($destination_uri,
            YZE_Resource_Controller $source_controller) {
    	$this->sourceController = $source_controller;
        $this->destinationURI = $destination_uri;
    }

    /**
     * 输出重定向响应
     *
     * @param bool $return 为 true 时返回目标 url 而不输出 header
     * @return string|null $return 为 true 时返回目标 url，否则输出 Location header 并返回 null
     */
    public function output($return=false){
		if ( ! $return ){
			header("Location: $this->destinationURI");
			return ;
		}
		return $this->destinationURI;
    }

    /**
     * 获取重定向的目标 url
     *
     * @return string 目标 url
     */
    public function destinationURI(){
        return $this->destinationURI;
    }

    /**
     * 重定向响应无视图数据，始终返回空字符串
     *
     * @param string $key 数据键名
     * @return string 空字符串
     */
    public function get_data($key){
        return '';
    }
}

/**
 * 视图响应，表示响应的HTTP中有message-body。message-body的内容可能是
 * html，xml，json，yaml等，
 * 由于包含的message-body，视图响应是可缓存的
 */
abstract class YZE_View_Adapter extends YZE_Object implements YZE_IResponse{
	/**
	 * 响应视图上要显示的数据，具体是什么内容由响应视图自己决定
	 * @var array
	 */
	protected $data;
	/**
	 * 指定该view输出的layout，如果指定了，则优先级最高于controller设置的layout
	 * @var string
	 */
	public $layout;

	/**
	 * 响应格式
	 * @var string
	 */
	public $format;

	/**
	 * 指定视图的容器视图，当前视图的内容将在master view的$this->content_of_view();中输出，<br/>
	 * master view的内容可以嵌套，最顶级的master view的内容将在layout的$this->content_of_view();输出<br/>
	 * master也支持content_of_section<br/>
	 * <br/>
	 * master view的默认查找路径是YZE_APP_VIEWS_INC、模块对应的views下面；你也可以指定决定路径<br/>
	 * <br/>
	 * 设置方式：$this->master_view = "master" 或者 $this->master_view = "mymaster/master"<br/>
	 * <br/>
	 * master view的格式使用请求环境的请求格式<br/>
	 * <br/>
	 * 如果指定的是master_view的路径，则是master模板文件，框架内部会通过yze_simple_view进行封装助理<br/>
	 * 如果指定的是YZE_View_Component对象，则直接调用其output进行输出
	 * @var string | YZE_View_Component
	 */
	public $master_view;

	/**
	 * 调用 check_master 后找到的 master view 的绝对路径
	 *
	 * @var string
	 */
	protected $master_view_path;

	/**
	 *
	 * @var YZE_Resource_Controller
	 */
	protected $controller;


	/**
	 * 获取指定 section 的内容
	 *
	 * @param string $section section 名
	 * @return mixed section 的内容
	 */
	public function content_of_section($section){
		return $this->data["content_of_section"][$section];
	}

	/**
	 * 获取当前视图的内容（content_of_view）
	 *
	 * @return mixed 当前视图的输出内容
	 */
	public function content_of_view(){
		return $this->data["content_of_view"];
	}

	/**
	 * 响应视图上要显示的数据，具体是什么内容由响应视图自己决定
	 *
	 * @param array $data 其中的view指当前请求处理时控制器设置的数据，cache指处理请求时之前缓存下来的数据
	 * @param YZE_Resource_Controller $controller
	 */
	public function __construct($data, YZE_Resource_Controller $controller){
		$this->data = (array)$data;
		$this->controller = $controller;
	}

	/**
	 * 获取视图所属的控制器
	 *
	 * @return YZE_Resource_Controller 控制器对象
	 */
	public function get_controller(){
		return $this->controller;
	}

	/**
	 * 检查 master view 是否存在
	 *
	 * 没有设置 master view，返回 false；设置了 master view 但不存在，抛异常；存在 master view 返回 true
	 * master view 可以放在模块的 views 下面或者 vendor 的 views 下面
	 *
	 * @return bool|void 存在返回 true，未设置返回 false，不存在时抛异常
	 */
	protected function check_master(){
		//stub
	}

	/**
	 * 输出 master view，将当前视图的内容与 section 传入 master view 渲染
	 *
	 * @param mixed $data   当前视图的输出内容
	 * @param bool  $return 为 true 时返回输出内容而不是直接输出
	 * @return string|null $return 为 true 时返回渲染内容，否则直接输出
	 */
	protected function output_master($data, $return=false){
		$datas = $this->get_datas();

		if (is_a($this->master_view, YZE_View_Component::class)){
			$master = $this->master_view;
		}else{
			$master = new YZE_Simple_View($this->master_view_path, array(), $this->controller);
		}

		$datas['content_of_section'] = $this->view_sections();
		$datas['content_of_view']    = $data;
		$master->set_datas($datas);
		$output = $master->get_output();

		if($return){
			return $output;
		}else{
			echo $output;
		}
	}

	/**
	 * 输出视图响应（含 master view 渲染）
	 *
	 * @param bool $return 为 true 时返回输出内容而不是直接输出
	 * @return string|null $return 为 true 时返回渲染内容，否则直接输出
	 */
	public final function output($return=false){
		ob_start();

		$this->display_self();
		$data = ob_get_clean();

		if($this->check_master()){
			return $this->output_master($data, $return);
		}

		if($return)return $data;
		echo $data;
	}

	/**
	 * 获取当前视图收集的所有 section 内容
	 *
	 * @return array|null section 集合，未收集时返回 null
	 */
	public function view_sections(){
	    // ai@2026-05-27 替换 @ 抑制符，使用 ?? null 显式处理
	    return $this->data['content_of_section'] ?? null;
	}

	/**
	 * 开始收集一个 section，配合 end_section 使用
	 *
	 * @return void
	 */
	public function begin_section(){
	    ob_start();
	}

	/**
	 * 结束 section 收集并保存到指定 section 名
	 *
	 * @param string $section section 名
	 * @return void
	 */
	public function end_section($section){
	    $this->data['content_of_section'][$section] = ob_get_clean();
	}

	/**
	 * 取得视图的输出内容
	 *
	 * @return string 视图渲染后的内容
	 */
	public function get_output(){
    	return $this->output(true);
	}

	/**
	 * 视图响应显示自己，其布局由视图模块定义，位于views/controller name/action下
	 * 子类根据自己的需要实现视图的加载方式
	 */
	protected abstract function display_self();

	/**
	 * 获取视图的单个数据
	 *
	 * @param string $key 数据键名
	 * @return mixed 数据值，不存在时返回 null
	 */
	public function get_data($key){
	   // ai@2026-05-27 替换 @ 抑制符，使用 ?? null 显式处理
	   return $this->data[$key] ?? null;
	}

	/**
	 * 获取视图的全部数据
	 *
	 * @return array 视图数据集合
	 */
	public function get_datas(){
	   return  $this->data;
	}

	/**
	 * 设置视图的单个数据
	 *
	 * @param string $key  数据键名
	 * @param mixed  $data 数据值
	 * @return void
	 */
	public function set_data($key, $data){
	    $this->data[$key] = $data;
	}

	/**
	 * 批量设置视图数据（覆盖全部现有数据）
	 *
	 * @param array $datas 数据集合
	 * @return YZE_View_Adapter 返回当前视图对象，支持链式调用
	 */
	public function set_datas(array $datas){
	    $this->data = $datas;
	    return $this;
	}

	/**
	 * 检查模板文件是否存在，不存在时由子类决定是否抛异常
	 *
	 * @return bool 模板存在返回 true
	 */
	public function check_view()
	{
		return true;
	}

	/**
	 * 根据请求的响应格式构建对应的响应视图
	 *
	 * json 返回 YZE_JSON_View，xml 返回 YZE_XML_View，其他格式返回 YZE_Notpl_View
	 *
	 * @param YZE_Resource_Controller $controller 控制器对象
	 * @param string                  $format     响应格式，json / xml / 其他
	 * @param array                   $data       视图数据
	 * @return YZE_IResponse 对应的响应视图对象
	 */
	public static function build_view(YZE_Resource_Controller $controller, $format, $data){
		if($format=="json") return new YZE_JSON_View($controller, $data);
		if($format=="xml") return new YZE_XML_View($controller, $data);
		return new YZE_Notpl_View($data, $controller);

	}
}
/**
 * 视图响应实现，负责加载视图响应模板，视图模板位于views/controller name/action name.tpl.php
 * Simple_View根据请求信息加载对于模块下面的视图模块，并include 它，由于是在对象中include，
 * 在该模板中就可以通过$this->the_date等API取到控制器设置给view的数据
 *
 * 模板可以是生成html的模板，也可以是生成其它数据的模板，比如json，xml等，只是不同的模块对应不同的layout
 * 在view这里它们是一样的。
 */
class YZE_Simple_View extends YZE_View_Adapter {

	/**
	 * 视图模板名（不含格式后缀），框架会按 {$tpl}.{$format}.php 查找模板文件
	 *
	 * @var string
	 */
	private $tpl;

	/**
	 * 通过模板、数据构建视图输出
	 *
	 * @param string                   $tpl_name  模板的路径全名（不含格式后缀）
	 * @param array                    $data      视图数据
	 * @param YZE_Resource_Controller  $controller 控制器对象
	 * @param string|null              $format    响应格式，为 null 时取请求的输出格式
	 */
	public function __construct($tpl_name, $data, YZE_Resource_Controller $controller, $format=null){
		parent::__construct($data,$controller);
		$this->tpl 		= $tpl_name;
		$this->format 	= $format ?: $controller->get_Request()->get_output_format();

	}
	protected function check_master(){
		if( ! $this->master_view ) return false;

		if (is_a($this->master_view, YZE_View_Component::class)){
			return;
		}

		$request = YZE_Request::get_instance();
		$module_view_path = $request->view_path();

		if( file_exists($module_view_path."/{$this->master_view}.{$this->format}.php")){
			$this->master_view_path = $module_view_path."/{$this->master_view}";
			return true;
		}

		if( file_exists(YZE_APP_VIEWS_INC."{$this->master_view}.{$this->format}.php")){
			$this->master_view_path = YZE_APP_VIEWS_INC."{$this->master_view}";
			return true;
		}

		if( file_exists("{$this->master_view}.{$this->format}.php")){
			$this->master_view_path = "{$this->master_view}";
			return true;
		}

		//如果不是默认的tpl格式，则换成tpl再找一遍，其他情况抛异常
        if($this->format == "tpl"){
            throw new YZE_Resource_Not_Found_Exception(" master view {$this->master_view}.{$this->format}.php not found from below path:
            <ul><li> {$module_view_path}/{$this->master_view}.{$this->format}.php</li>
            <li> ".YZE_APP_VIEWS_INC."{$this->master_view}.{$this->format}.php</li>
            <li> {$this->master_view}.{$this->format}.php</li></ul>");
        }else{
            $this->format = "tpl";
            $this->check_master();
        }
		return true;
	}

	public function check_view(){

		if( ! file_exists("{$this->tpl}.{$this->format}.php")){
            //if format not exist then use tpl
            if($this->format == "tpl"){
                throw new YZE_Resource_Not_Found_Exception(" 界面 {$this->tpl}.{$this->format}.php 不存在");
            }else{
                $this->format = "tpl";
                $this->check_view();
            }
		}
	}

	protected function display_self(){

		$this->check_view();
		require "{$this->tpl}.{$this->format}.php";
	}
}
/**
 * 以class的方式来实现view， 如果需要使用master view则重载check_master并返回master的YZE_View_Component对象
 * @author ydhlleeboo
 *
 */
abstract class YZE_View_Component extends YZE_View_Adapter{
    /**
     * 输出组件内容，由子类实现具体的组件渲染逻辑
     *
     * @return void
     */
    protected abstract function output_component();

    /**
     * 构造函数
     *
     * @param array                    $data       组件数据
     * @param YZE_Resource_Controller  $controller 控制器对象
     * @param string|null              $format     响应格式，为 null 时取请求的输出格式
     */
    public function __construct($data, $controller, $format=null){
        parent::__construct( $data, $controller);
		$this->format 	= $format ?: $controller->get_Request()->get_output_format();
    }
    protected function display_self(){
        $this->output_component();
    }
}
/**
 * 该response没有模板文件，只输出一些字符串，用于那些没有html模板只返回简单数据的地方如json，xml
 *
 */
class YZE_Notpl_View extends YZE_View_Adapter {
	/**
	 * 需要直接输出的字符串内容
	 *
	 * @var string
	 */
	private $html;

	/**
	 * 构造函数
	 *
	 * @param string                   $html       需要直接输出的字符串
	 * @param YZE_Resource_Controller  $controller 控制器对象
	 */
	public function __construct($html, YZE_Resource_Controller $controller){
		parent::__construct(array(),$controller);
		$this->html = $html;
	}

	/**
	 * 直接输出字符串内容
	 *
	 * @return void
	 */
	protected function display_self(){
		echo $this->html;
	}

	/**
	 * 返回要输出的字符串内容
	 *
	 * @return string 字符串内容
	 */
	public function return_html(){
		return $this->html;
	}
}

/**
 * 返回json，返回格式{errorcode, success,msg,data}
 *
 * @author apple
 *
 */
class YZE_JSON_View extends YZE_View_Adapter {
	/**
	 *
	 * @param YZE_Resource_Controller $controller
	 * @param array $data
	 */
	public function __construct(YZE_Resource_Controller $controller, $data){
		parent::__construct($data,$controller);
	}
	protected function display_self(){
		header("Content-Type: application/json; charset=utf-8");
		echo json_encode($this->data, JSON_UNESCAPED_UNICODE);
	}


	/**
	 * 构建一个失败响应的 json 视图，输出格式 {success:false, data, code, msg}
	 *
	 * @param YZE_Resource_Controller $controller 控制器对象
	 * @param string|null             $message    错误消息
	 * @param int|null                $code       错误码
	 * @param mixed                   $data       附带数据
	 * @return YZE_JSON_View 失败响应的 json 视图
	 */
	public static function error($controller, $message =null, $code =null, $data=null) {
	    return new YZE_JSON_View($controller,  array (
	            'success' => false,
	            "data" => $data,
	            "code" => $code,
	            "msg" => $message
	    ) );
	}

	/**
	 * 构建一个成功响应的 json 视图，输出格式 {success:true, data, msg:null}
	 *
	 * @param YZE_Resource_Controller $controller 控制器对象
	 * @param mixed                   $data       返回数据
	 * @return YZE_JSON_View 成功响应的 json 视图
	 */
	public static function success($controller, $data = null) {
	    return new YZE_JSON_View($controller,  array (
	            'success' => true,
	            "data" => $data,
	            "msg" => null
	    ) );
	}
}
/**
 * 把数据转换成xml输出，输出格式<?xml version="1.0"?>
 * <root><success>1</success><errorcode>0</errorcode><msg></msg><data>your data</data><data_type>data</data_type></root>
 *
 * @author apple
 *
 */
class YZE_XML_View extends YZE_View_Adapter {
	/**
	 *
	 * @param YZE_Resource_Controller $controller
	 * @param string $data
	 */
	public function __construct(YZE_Resource_Controller $controller, $data){
		parent::__construct($data, $controller);
	}
	protected function display_self(){
		$xml = new SimpleXMLElement("<?xml version=\"1.0\"?><root></root>");
		$this->array_to_xml($this->data,$xml);

		echo $xml->asXML();
	}

	/**
	 * 将数组数据递归转换为 xml 节点
	 *
	 * 数值键会转换成 item0、item1 等节点名，非数组值直接作为节点内容
	 *
	 * @param array              $data 需要转换的数据数组
	 * @param SimpleXMLElement   $xml  目标 xml 节点（引用传递）
	 * @return void
	 */
	private function array_to_xml($data, &$xml) {
		foreach($data as $key => $value) {
			if(is_array($value)) {
				if(!is_numeric($key)){
					$subnode = $xml->addChild("$key");
					$this->array_to_xml($value, $subnode);
				}
				else{
					$subnode = $xml->addChild("item$key");
					$this->array_to_xml($value, $subnode);
				}
			}
			else {
				$xml->addChild("$key","$value");
			}
		}
	}

	/**
	 * 构建一个失败响应的 xml 视图，输出 <success>0</success> 等节点
	 *
	 * @param YZE_Resource_Controller $controller 控制器对象
	 * @param string|null             $message    错误消息
	 * @param int|null                $code       错误码
	 * @return YZE_XML_View 失败响应的 xml 视图
	 */
	public static function error($controller, $message =null, $code =null) {
	    return new YZE_XML_View($controller, array (
	            'success' => false,
	            "data" => null,
	            "code" => $code,
	            "msg" => $message
	    ) );
	}

	/**
	 * 构建一个成功响应的 xml 视图，输出 <success>1</success> 等节点
	 *
	 * @param YZE_Resource_Controller $controller 控制器对象
	 * @param mixed                   $data       返回数据
	 * @return YZE_XML_View 成功响应的 xml 视图
	 */
	public static function success($controller, $data = null) {
	    return new YZE_XML_View($controller, array (
	            'success' => true,
	            "data" => $data,
	            "msg" => null
	    ) );
	}
}

/**
 * layout指定义视图响应的数据定义格式，比如输出html是&lt;html&gt;....&lt;/html&gt;，
 * 输出xml的格式是&lt;xml&gt;...&lt;/xml&gt;，json是{}等等，
 * <pre>
 * layout也是视图响应，也包含模板，它在定义的响应数据格式中加上请求的视图的内容，这其中有一些约定：
 * layout模板中的content_for_layout指的是请求的视图输出内容。
 * content_for_layout是固定的、表示视图内容的变量
 * 其它的需要在layout中显示的变量，可以在controller中通过set_view_data设置后，
 * 在layout模板中通过$this->view->get_data()取出来。
 * </pre>
 *
 * @author liizii
 *
 */
class YZE_Layout extends YZE_View_Adapter{
  	/**
  	 * 需要被布局包裹的视图对象
  	 *
  	 * @var YZE_View_Adapter
  	 */
	private $view;

	/**
	 * 构造函数
	 *
	 * @param string                   $layout     布局名，对应 app/vendor/layouts/{layout}.layout.php
	 * @param YZE_View_Adapter         $view       需要被布局包裹的视图对象
	 * @param YZE_Resource_Controller  $controller 控制器对象
	 */
	public function __construct($layout,YZE_View_Adapter $view,  YZE_Resource_Controller $controller){
		parent::__construct($view->get_datas(),$controller);
		$this->view 	= $view;
		$this->layout 	= $layout;
	}


	protected function display_self(){
		$this->data = $this->view->get_datas();
		$this->data['content_of_view'] = $this->view->get_output();
		$this->data['content_of_section'] = $this->view->view_sections();

		if(isset($this->view->layout)){
			$this->layout = $this->view->layout;
		}

		if(($_SERVER['HTTP_X_PJAX'] ?? null)){//pjax 请求，不返回layout
			echo "<title>".$this->get_data("yze_page_title")."</title>";//pjax 加载时设置页面标题
			$this->layout = "";
		}
		if ($this->layout){
		    if(YZE_Request::get_instance()->is_mobile_client()){
		        $moblayoutfile = YZE_APP_LAYOUTS_INC."{$this->layout}.moblayout.php";
		        if( file_exists($moblayoutfile) ){
		            include $moblayoutfile;
		            return;
		        }
		    }
		    $layoutfile = YZE_APP_LAYOUTS_INC."{$this->layout}.layout.php";
		    if( file_exists($layoutfile) ){
		        include $layoutfile;
		        return;
		    }
		    // ai@2026-05-27 修复 PHP 8 兼容：错误消息引用错误变量 $moblayoutfile，应为 $layoutfile
		    throw new YZE_Resource_Not_Found_Exception(" 布局 {$layoutfile} 不存在");
		}else{
			echo $this->data['content_of_view'];
		}
	}


}
?>
