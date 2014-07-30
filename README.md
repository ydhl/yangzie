## Yangzie -- 轻松构造模块化的应用

* 易点互联: <http://yidianhulian.com>
* Tags: php, web, module

## Yangzie的哲学
1. 一次请求一次响应
2. 一个uri表示一个资源，通过http的方法表示对资源进行何种操作，get表示获取，post表示增加，put表示修改，delete表示删除
3. 对资源的访问不需要通过http，在服务器端可以通过uri直接操作另外的uri
4. 模块代码要独立，模块之间的访问通过uri来实现代码模块之间的松耦合
5. 响应的内容要自由，同一个uri可以响应输出不同的格式，如html，pdf等
6. 请求要么成功，要么异常
7. 功能复用比代码重用更有价值

## Yangzie的处理流程
1. 初始化请求，解析请求信息，uri路由。出现异常进入yangzie的异常处理
2. 验证用户是否登录（如果需要）。出现异常进入yangzie的异常处理
3. 验证用户是否有权限（如果需要）。出现异常进入yangzie的异常处理
4. 验证器验证数据。出现异常进入yangzie的异常处理
5. 指派到控制器。出现异常进入控制器的exception方法
6. 控制器返回响应。出现异常进入exception的异常处理
7. 输出响应。出现异常进入exception的异常处理

## 特性

* 模块化
 * 模块之间保持松耦合，各自独立
 * 模块可以是一个目录，也可以打包成phar文件，可直接拷贝到其他使用yangzie开发到系统中直接使用
 * 每个模块便是一个MVC系统，定义有自己的url路由，验证器，控制器，视图，model
 * 模块之间调用通过url调用，只是这里的url不用在通过http，不用经过浏览器，比如在uri:example.com/order,处理的控制器是order，属于order模块，但是需要访问customer模块的userinfo控制器，以便得到客户的信息，只需要在代码中通过yze_go("/customer/userinfo?customer_id=24543"),便可得到客户的信息

* 灵活的视图
 * 任何请求都可响应输出为html，xml，json，pdf，excel，word等输出模版。只需两步：
 
    1.改变访问url后缀，无后缀默认返回html格式的内容，如example.com/order, 如果访问example.com/order.xml 将返回xml格式的内容，依此类推
    
    2.在模块的views目录中加上对应的模版文件, 如order.xml.php；在次文件中定义你要返回的xml内容；视图文件的命名格式是：[控制器名].[格式].php; 如order.json.php返回json格式内容；order.pdf.php 返回pdf格式内容；order.tpl.php返回html格式内容。注意，格式除了tpl外其他都是你自己定义的。yangzie会根据url的后缀去找调用对应的模版文件输出内容

* 表单数据回显
 * 只需在生成表单的地方加上一句代码调用：__yze_get_default_value($object, $name, $controller)__。比如
 &lt;input type="text" name="email" value="&lt;?php yze_get_default_value($user, "email", $this->controller)?&gt;"/&gt;
 * 在用户提交的数据验证器验证失败后，之前提交的数据会自动回显；避免用户重复再输入
 * 自定义错误提示。用户输入错误在所难免，但出错后应该友好的提醒用户哪里错了，（当然输入正确的数据应该还在那里），把__yze_controller_error()__放在合适的地方，出错后便会把错误消息显示在这里。该api是把错误消息集中显示在某个地方。如果想在错误的输入项附近显示错误消息，则只需要合适的地方调用__yze_field_error($field_name)__。如 &lt;input type="text" name="email" value="&lt;?php yze_get_default_value($user, "email", $this->controller)?&gt;"/&gt; &lt;?php yze_field_error("email") ?&gt;

* 灵活的URI路由。可定义表意明确的，可读性更好的uri
  * 默认的路由规则是/module/controller/variable1/variable2。可通过$request->get_Var(1)；得到variable1。
  * 可以在module文件夹下的__module__.php中自定义路由规则：
  <pre>
 <code>
 	'account/(?P<pa_id>\d+)'	=> array(//uri，前后不需要/，uri中的可便部分可写成正则表达式，该例中可通过$request->get_var("pa_id")得到实际uri中传入的值
		'controller'	=> 'account',//处理该uri的控制器
		'args'	=> array(
			'action'	=> 'add',//自定义传入参数，通过$request->get_var("action")便可得到add；通常用于同一个控制器处理不同uri时用于区分
		),
	),
 </code>
   </pre>
 该例子中的映射可处理example.com/account/123232。这比example.com/account.php?id=123232更直观。同时被和yangzie的视图处理，可以任意返回需要的数据格式
 
* 验证器，把数据验证代码独立出来
 * 数据验证是必不可少的，但在正式的逻辑处理之前，重复的写数据验证代码是很痛苦的。
 * 验证器便是通过代码重用的方式，把数据验证逻辑独立出来.
 * 验证器通过后才会进入控制器，这样保持控制器的逻辑处理代码简洁
 * 验证器失败后会返回错误信息，这些信息将会显示在视图上
 * 在验证器上通过如下的代码便可设置数据的验证
 <pre>
 <code>
 $this->assert('name', 'check_name', '', '');
 $this->assert('email', YZE_Vadilater::NOT_EMPTY, '', 'email不能为空');
 protected function check_name($method, $name, $rule){
	$datas = $this->get_datas($method);
	$willCheck = $datas[$name];//取当前编辑框内值
	if(sameCheck()){
		$this->set_error_message($name,"账户名重复");
		return false;
	}
	return true;
}
 </code>
  </pre>
 其中name是要验证的请求数据名，check_name是自定义验证方法。
 YZE_Vadilater::NOT_EMPTY是yangzie提供的验证方法

* 通过脚本生成基础代码
 * 通过脚本可生成mvc代码结构
 * 在yangzie根目录执行 php  scripts/generate.php 便可进入yangzie脚本窗口，根据窗口的提示便可生成相关的代码文件

* 输出缓存

## Controller
 * 控制器是具体访问uri的处理中心，控制器主要有下面几种方法，分别处理uri所代表的资源的增删改查操作
  * __get__：获取uri指向的信息，请求数据通过get请求传递，返回YZE_IResponse
  * __post__：创建信息，请求数据通过post请求传递，返回YZE_Redirect
  * __delete__：删除信息，请求数据通过post请求传递，返回YZE_Redirect
  * __put__：修改信息，请求数据通过post请求传递，返回YZE_Redirect
  * __exception(YZE_RuntimeException $e) __：在前4中方法中的代码出现任何未抓取的异常时，都将进入该方法进行异常处理。也是返回YZE_IResponse，如果没有任何返回，则显示vander/views/500.tpl.php界面；如果像重新显示该资源内容（如post失败后，想重新显示get的内容），可以通过 return $this->wrapGet($this->get());
  * 
  

## Model
## View, Response
## Validater

## 代码结构

## Hooks