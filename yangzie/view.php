<?php
/**
 * 表示一个请求的响应结果。可能是可查看的内容，比如html，xml，json，yaml等，
 * 也可以只是一些http响应头，比如 301 redirect，304 not modified等
 *
 * @access public
 * @author liizii, <libol007@gmail.com>
 */
interface IResponse{
	/**
	 * 输出响应
	 */
    public function output();
    
    /**
     * 取得控制器设置在响应中的值
     * @package $key
     */
    public function the_data($key);
    /**
     * 
     * 取得一次请求中缓存下来的值
     * @param $key
     */
    public function the_cache($key);
    

}
/**
 * 只输出http头，无message－body，表示请求的内容没有修改，客户端应该使用缓存的内容。
 * @author liizii
 *
 */
class Response_304_NotModified implements IResponse{
	private $headers;
	public function __construct($headers,YZE_Resource_Controller $controller){
		$this->headers = $headers;
		$this->controller = $controller;
	}
	public function output(){
		header("HTTP/1.1 304 Not Modified");
		foreach ((array)$this->headers as $name => $value){
			header("{$name}: {$value}");
		}
	}
	public function add_header($header_name,$header_value){
		//TODO 头中需要进行什么编码？
		$this->headers[$header_name] = $header_value;
	}

    public function the_data($key){
    	return $this->headers[$key];
    }
	public function the_cache($key){
    	return $this->headers[$key];
    }
}
/**
 * HTTP Location:重定向，表示一次请求的处理输出是重定向
 * @author liizii
 *
 */
class Redirect implements IResponse{
	private $uri;
	private $data;
	public function __construct($uri, YZE_Resource_Controller $controller){
		$this->uri = $uri;
		$this->controller = $controller;
	}
	public function output(){
		//如果一次请求是由 .json发起的，那么后续的站内url跳转都自动带上.json后缀
		$format = Request::get_instance()->get_output_format();
		if($format){
			$url_components = parse_url($this->uri);
			
			//不是站内跳转
			if(@$url_components['host'] && $url_components['host']!=$_SERVER['HTTP_HOST']){
				header("Location: $this->uri");
				return;
			}
			
			header("Location: ".$url_components['path'].".{$format}?".$url_components['query'].
					(@$url_components['fragment'] ? "#".$url_components['fragment'] : ""));
			return;	
		}
		header("Location: $this->uri");
	}
	/**
	 * 返回重定向的地址中的uri， 只包含路径，不包含其它部分
	 * @return string
	 */
	public function the_uri(){
		return parse_url($this->uri,PHP_URL_PATH);
	}
	/**
	 * 返回重定向的整个路径
	 * @return string
	 */
	public function the_full_uri(){
		return $this->uri;
	}
    public function the_data($key){
    	return $this->data[$key];
    }
	public function the_cache($key){
    	return $this->data[$key];
    }
}

/**
 * 视图响应，表示响应的HTTP中有message-body。message-body的内容可能是 
 * html，xml，json，yaml等，
 * 由于包含的message-body，视图响应是可缓存的
 */
abstract class View_Adapter extends YZE_Object implements IResponse,Cacheable{
	/**
	 * 响应视图上要显示的数据，具体是什么内容由响应视图自己决定
	 * @var array
	 */
	protected $data;
	/**
	 * 一次请求中缓存下来的内容
	 * @var array
	 */
	protected $cache_data;
	/**
	 * 处理当前请求时出现的异常
	 * @var Exception
	 */
	private $exception;
	/**
	 * 视图响应的缓存控制
	 * @var YZE_HttpCache
	 */
	private $cache_ctl;
	/**
	 * 
	 * @var YZE_Resource_Controller
	 */
	protected $controller;#生成Response的Controller
	/**
	 * 响应视图上要显示的数据，具体是什么内容由响应视图自己决定
	 *
	 * @param array $data 其中的view指当前请求处理时控制器设置的数据，cache指处理请求时之前缓存下来的数据
	 */
	public function __construct($data, YZE_Resource_Controller $controller){
		$this->data = is_array(@$data['view']) ? $data['view'] : array(@$data['view']);
		$this->cache_data = is_array(@$data['cache']) ? $data['cache'] : array(@$data['cache']);
		$this->controller = $controller;
		$this->exception = Session::get_instance()->get_uri_exception(Request::get_instance()->the_uri());
	}
	public function get_controller(){
		return $this->controller;
	}
    public final function output(){
    	if($this->cache_ctl){
    		$this->cache_ctl->output();
    	}
    	$this->display_self();
    }
    /**
     * 取得视图的输出内容
     */
    public function get_output(){
    	ob_start();
    	$this->output();
    	return ob_get_clean();
    }
    
    /**
     * 视图响应显示自己，其布局由视图模块定义，位于views/controller name/action下
     * 子类根据自己的需要实现视图的加载方式
     */
    protected abstract function display_self();

    public function the_data($key){
    	return @$this->data[$key];
    }
	public function the_cache($key){
    	return @$this->cache_data[$key];
    }
    public function get_datas(){
    	return array('view'=>$this->data,"cache"=>$this->cache_data);
    }
    public function has_exception(){
    	return is_a($this->exception,"Exception");
    }
    public function get_exception_message(){
    	if(!$this->has_exception()){
    		return "";
    	}
    	return $this->exception->getMessage();
    }
    public function get_exception(){
    	return $this->exception;
    }
	public function set_cache_config(YZE_HttpCache $cache=null){
		$this->cache_ctl = $cache;
	}
	
	/**
	 * 检查模板文件是否存在 
	 */
	public function check_view()
	{
		return true;
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
class Simple_View extends View_Adapter {
	private $tpl;
	/**
	 * 通过模板、数据构建视图输出
	 * @param string $tpl 模板的路径全名。
	 * @param array $data
	 * @param YZE_Resource_Controller $controller
	 */
	public function __construct($tpl, $data, YZE_Resource_Controller $controller){
		parent::__construct($data,$controller);
		$this->tpl = $tpl;
	}
	
	public function check_view()
	{
		if(!file_exists("{$this->tpl}.tpl.php")){
			throw new YZE_View_Not_Found_Exception("{$this->tpl}.tpl.php");
		}
	}
	
	protected function display_self(){
		require "{$this->tpl}.tpl.php";
	}
}
/**
 * 该response没有模板文件，只输出一些字符串，用于那些没有html模板只返回简单数据的地方如json，xml
 * 
 */
class Notpl_View extends View_Adapter {
	private $html;
	public function __construct($html, YZE_Resource_Controller $controller){
		parent::__construct(array(),$controller);
		$this->html = $html;
	}
	protected function display_self(){
		echo $this->html;
	}
	public function return_html(){
		return $this->html;
	}
}

/**
 * layout指定义视图响应的数据定义格式，比如输出html是<html>....</html>，
 * 输出xml的格式是<xml>...</xml>，json是{}等等，
 * 
 * layout也是视图响应，也包含模板，它在定义的响应数据格式中加上请求的视图的内容，这其中有一些约定：
 * layout模板中的content_for_layout指的是请求的视图输出内容。
 * content_for_layout是固定的、表示视图内容的变量
 * 其它的需要在layout中显示的变量，可以在controller中通过set_view_data设置后，
 * 在layout模板中通过$this->view->the_data()取出来。
 * 
 * @author liizii
 *
 */
class Layout extends View_Adapter{
	private $view;
	private $layout;
	public function __construct($layout,View_Adapter $view,  YZE_Resource_Controller $controller){
		parent::__construct($view->get_datas(),$controller);
		$this->view 	= $view;
		$this->layout 	= $layout;
	}
	
	protected function display_self(){
		ob_start();
		$this->view->output();
		$yze_content_of_layout = ob_get_clean();
		include_once APP_LAYOUTS_INC."/{$this->layout}.tpl.php";
	}
}
?>