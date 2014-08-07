<?php
namespace app\test;
use \yangzie\YZE_Resource_Controller;
use \yangzie\YZE_Request;
use \yangzie\YZE_Redirect;
use \yangzie\YZE_Session_Context;
use \yangzie\YZE_RuntimeException;

/**
*
* @version $Id$
* @package test
*/
class Index_Controller extends YZE_Resource_Controller {
	/**
	 * get 请求返回视图
	* $this->layout = ''; 设置该视图的布局模板，默认为tpl.layout.php,位于app/vendor/layouts
	* $this->set_view_data('arg_name', 'arg_value'); 给视图设置数据, arg_value可以是任何php变量
	* 视图中通过 $this->get_data('arg_name')来取得控制器设置的数据
	* 
	 */
	public function get(){
		$request = $this->request;
		$this->set_view_data('yze_page_title', 'this is controller index');
                $this->set_view_data("hook", \yangzie\YZE_Hook::do_hook("order_test"));
		//return new YZE_Redirect("/test/go.json?foo=bar", $this, array("foo1"=>"bar1"), true);
	}
	
	/**
	 * post请求用于对请求资源的创建
	 *
	 */
	public function post(){
		$request = $this->request;
		//\yangzie\yze_go("/test/go.json", "post", true);
// 		$r = new YZE_Redirect("/test/go.json", $this, array(), true);
// 		$r->output();
	}
	
	/**
	 * delete请求用于对请求资源的删除
	 *
	 */
	public function delete(){
		$request = $this->request;
	}
	
	/**
	 * put请求用于对请求资源的更新
	 *
	 */
	public function put(){
		$request = $this->request;
	}

	public function exception(YZE_RuntimeException $e){
		$request = $this->request;
		//出现了异常，如何处理，没有任何处理将显示500页面
		//如果想显示get的返回内容可调用 $this->wrapResponse($this->get())
		
		return $this->wrapResponse($this->get());
	}
	
	public function get_response_guid(){
		//如果该控制器的响应输出需要缓存，这里返回生成缓存文件的唯一id
		return null;
	}
	
	/*
	 * @see YZE_Resouse_Controller::cleanup()
	 */
	public function cleanup(){
		//pass
		parent::cleanup();
	}

}
?>