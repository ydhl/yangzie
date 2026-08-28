<?php
namespace yangzie;

/**
 * 模块配置基类，整个yangzie app也是一个module，
 * 每个module可以通过check方法检查该模块运行必须要满足的条件，
 * 通过config返回模块的配置，比如routers
 */
abstract class YZE_Base_Module extends YZE_Object {
	/**
	 * 模块的名字
	 * @var string
	 */
	public string $name = "";


	/**
	 * 该模块中需要认证访问的资源,格式：
	 *     [
	 *     '控制器名'=>"action，支持正则"
	 *     ]
	 *
	 * 表示某个资源控制器的某个请求求认证。如
	 *     [
	 *     'resouce_controller_name2'=>"(post_?)add",
	 *     ]
	 *
	 * 需要认证的资源在访问时，框架会调用hook YZE_HOOK_GET_LOGIN_USER，该hook返回非假则表示已经登录，假值则抛出YZE_Need_Signin_Exception异常，并进入YZE_HOOK_YZE_EXCEPTION处理
	 * @var array
	 */
	public array $auths = array();

	/**
	 * 同 auths，定义不需要认证的 action，优先级比 auths 高
	 *
	 * @var array
	 */
	public array $no_auths = array();

	/**
	 * 获取指定的模块配置，模块的配置包含了对象属性及通过 config 方法返回的内容
	 *
	 * @param string|null $name 配置名，为 null 时返回全部配置
	 * @return array|string 指定配置名的值或全部配置数组
	 */
	public function get_module_config($name=null): array|string{
		$config = get_class_vars(get_class($this));
		$config = array_merge($config,$this->config());
		// ai@2026-05-27 替换 @ 抑制符，使用 ?? null 显式处理
		return $name ? ($config[strtolower($name)] ?? []) : $config;
	}

	/**
	 * 返回指定控制器上映射的 url 列表
	 *
	 * @param string $controller 控制器名（可带 _controller 后缀）
	 * @param string $action     action 名，默认 index
	 * @return array 匹配的 url 列表
	 */
	public function get_uris_of_controller($controller, $action='index'){
		$controller = rtrim(strtolower($controller), "_controller");
		$config = $this->config();
		$_ = array();
		foreach ($config['routers'] as $uri => $mapping){
			if(strtolower($mapping['controller']) == $controller && $action==strtolower(($mapping['action'] ?? null))){
				$_[] = $uri;
			}
		}
		return $_;
	}
	/**
	 * 加载该模块之间做检查, 出错则抛出异常
	 *
	 * @author leeboo
	 * @throws YZE_RuntimeException
	 */
	public function check(){
	}
	/**
	 * 初始化一些配置项的值，返回数组，键为配置名
	 *
	 * module 通过 config 返回路由映射，格式：
	 * <pre>
	 * [
	 *   'routers' => [
	 * 	    'uri地址'=>["controller"=>'控制器名', 'action'=>'执行的方法', "args"=>["固定参数名"=>"参数值"]]
	 *   ]
	 * ]
	 * 如 ['/something/(?P<id>\d+)'=>["controller"=>'quote',"args"=>[]]]
	 * uri 地址支持正则，并且可命名正则匹配值，比如上面的 id，则可以通过 $request->get_var('id') 获取地址上的值
	 * 控制器名是不包含 controller 的，比如 quote_controller 中的 quote
	 * args 是固定传入 action 的参数，也是通过 $request->get_var('参数名') 获取
	 * </pre>
	 *
	 * @return array 模块配置数组
	 */
	protected abstract function config(): array;
    /**
     * js 资源分组，在加载时方便直接通过分组名一次性加载所有文件，并支持 http 缓存机制
     *
     * 如果是项目级的资源：路径以 web 绝对路径 / 开始，/ 指的是 public_html 目录，
     * 在 layouts 中通过接口 yze_js_bundle("foo,bar") 一次打包加载这里指定的资源
     *
     * 如果是模块的资源：路径以 web 绝对路径 / 开始，/ 指的是模块下的 public_html 目录，
     * 在 layouts 中通过接口 yze_module_js_bundle("foo,bar") 一次打包加载这里指定的资源
     *
     * 实现该函数决定如何返回要打包下载的资源
     *
     * @param string $bundle 资源分组名
     * @return array 资源路径列表，如 array(资源路径1，资源路径2)
     */
    public abstract function js_bundle(string $bundle): array;
    /**
     * css 资源分组，在加载时方便直接通过分组名一次性加载所有文件，并支持 http 缓存机制
     *
     * 如果是项目级的资源：资源路径以 web 绝对路径 / 开始，/ 指的是 public_html 目录，
     * 在 layouts 中通过接口 yze_css_bundle("yangzie,foo,bar") 一次打包加载这里指定的资源
     *
     * 如果是模块的资源：路径以 web 绝对路径 / 开始，/ 指的是模块下的 public_html 目录，
     * 在 layouts 中通过接口 yze_module_css_bundle("yangzie,foo,bar") 一次打包加载这里指定的资源
     *
     * 实现该函数决定如何返回要打包下载的资源
     *
     * @param string $bundle 资源分组名
     * @return array 资源路径列表，如 array(资源路径1，资源路径2)
     */
	public abstract function css_bundle(string $bundle): array;
}
?>
