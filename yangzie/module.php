<?php
abstract class Base_Module{
	public $validate_required = array();
	/**
	 * 模块的名字
	 * @var string
	 */
	public $name = "";
	
	/**
	 * 该模块加载时自动设置的包含路径，如array('path1','path2')
	 * @var array
	 */
	public $include_path = array();
	
	/**
	 * 该模块加载时自动加载的文件，如array('path1/file1','file2')
	 * @var array
	 */
	public $include_files = array();

	
	/**
	 * 该模块自定义的uri路由，如array('/something/\d+'=>array("controller"=>'quote',"args"=>array()))
	 * controller string 资源控制器名
	 * args 带到action中去的参数:<br/>
	 * 例如：array('/something/(?P<order_id>\d+)'=>array("controller"=>'quote',"args"=>array('r:order_id')))
	 * <br/>控制器便可以通过get_var("order_id")得到地址上的order id, 这里的r:order_id表示正则匹配地址中的order_id<br/>
	 * 也可以写固定的值，比如array('/something/(?P<order_id>\d+)'=>array("controller"=>'quote',"args"=>array('foo'=>'bar')))
	 * <br/>控制器便可以通过get_var("foo")得到bar
	 * 
	 * @var array
	 */
	public $routers = array();
	
	/**
	 * 该模块中需要认证访问的资源及其访问方法,格式：
	 *     array(
	 *         'resouce_controller_name'=>"get|post|put|delete|*",
	 *         'resouce_controller_name2'=>"get|post|put|delete|*",
	 *     )
	 * 表示某个资源控制器的某个请求求认证。如
	 *     array(
	 *         "deals" => "*"
	 *         "deals" => "post|put|delete"
	 *     )
	 * 表示deals控制器中的所有请求都要认证
	 * deals控制器中只有（post,put,delete）在调用前都要认证,get请求不需要
	 * 
	 * @var array
	 */
	public $auths = array();
	
	/**
	 * 同auths，只是其它的定义不做验证，优先级比auths高
	 * 
	 * @var unknown_type
	 */
	public $no_auths = array();
	
	/**
	 * 定义模块中哪些action允许或者拒绝哪些ARO访问
	 * 格式为：
	 * array(
	 *     controller_name1/action1:get|post|put|delete|* => ARO名或者回调函数
	 * )
	 * @var array
	 * @deprecated 未考虑好怎么实现
	 */
//	public $acls = array();
	
	
	public function get_module_config($name=null){
		$config = get_class_vars(get_class($this));
		$config = array_merge($config,$this->_config());
		return $name ? @$config[strtolower($name)] : $config;
	}

	/**
	 * 初始化一些配置项的值，返回数组，键为配置名
	 * @return array
	 */
	protected abstract function _config();
}
?>