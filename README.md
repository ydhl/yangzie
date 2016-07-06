文档在完善中。。。

V1.5.4
1. 增加master view ，让view 重用做到极致
2. 生成的Model字段加上F前缀, 加上CLASS_NAME常量
3. $column 修改为静态变量

V1.5.3

1. 增加mongodb，redis支持
2. 修复/module/controller/123,无法解析vars参数bug
3. 增加init.php，yangzie所有文件由他加载
3. sql 可以查询多个sum,count,max,min可以查询多个

V1.5.2

# Yangzie -- 轻松构造模块化的应用

* 易点互联: <http://yidianhulian.com>
* Tags: php, web, module

## Yangzie的哲学
1. 小到一个函数，大到一个系统都奉行输入－处理－输出的原则，输入、输出都要明确定义。
2. 模块化，任何模块不在代码层面依赖其他模块，即使他们之间有功能逻辑上的依赖性
3. 功能复用比代码重用更有价值

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

# URI

## 解析规则

* 如果在 __module.php__ 中定义了映射，则以该定义进行映射。url定义中可以自由设置正则表达式，
   比如(?P<pa_id>\d+)，这时便可通过$request->get_var("pa_id")得到实际的值
* 如果没有定义映射，则按照如下规则进行解析：/module/controller/var1/var2....
   第一个为模块，第二个为控制器，后面解析成变量，可通过$request->get_Var(1); $request->get_Var(2); 取到对应的值

* 灵活的URI路由。可定义表意明确的，可读性更好的uri
  * uri是资源的请求地址，同时也是API，因为一个uri定义了明确的输入，和明确的输出
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
 
 # 数据提交
 yangzie会防止数据重复提交，比如在网络比较慢的情况下用户重复点击了提交按钮
 
 * 同一个uri可以直接使用在ajax环境中
 
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

## 模块
一个模块是一组功能逻辑的集合。在yangzie中一个模块可以是一个文件夹，也可以是phar包（yangzie-cli可把一个模块目录打包成phar）。
模块的目录结构如下：
<pre>
+module name
 | + controllers 
 | + models
 | + views
 | + validates
 | + hooks
 | __module__.php
 | __hooks__.php
</pre>
 __module__.php中定义了该模块的接口URI。用户可通过浏览器直接访问这些URI，其他模块或者系统也可以通过编程访问这些URI。他们的区别是该URI响应不同（不同的响应格式），用户得到的是可读性好，美观的响应，如html，pdf。程序得到的是结构良好便于程序处理的响应，如xml，json，但含义一样。

模块只是复杂系统的一部分，肯定需要与同一个系统或者不同系统的模块之间进行功能调用。yangzie不建议直接在一个模块中直接应用另一个模块的代码（虽然完全可以这样做，php的require或者include另一个模块的php文件），yangzie提供两种方法进行模块之间的功能调用

### 通过URI API

通过编程访问另一个模块中定义的URI：__yze_go($uri, $method, $return)__。method表示对资源的操作，return为true表示以变量返回，false表示直接输出。这里的$uri需要指定返回的数据格式，如/order/3328473894.xml。

* 通过uri调用，yangzie会解析uri，指派到controller，处理响应
* 出现异常会抛出到调用环境中

### 通过HOOK

被调用的模块可以在__hooks__.php中定义hook name，然后注册hook处理函数：
<pre>
<code>
define("HOOK_NAME","4");
YZE_Hook::the_hook()->add_hook(YZE_FILTER_BEFORE_CHECK_REQUEST_TOKEN, function($data){
	
});
</code>
</pre>

调用的模块可以通过yangzie hook api来触发hook，从而完成调用
<pre>
<code>
do_hook(HOOK_NAME, $data);
</code>
</pre>

他们的区别：
* uri调用返回的是文本内容，如json，xml等；通过hook调用可以得到的是php的数据格式如数组，对象等
* 如果调用不成功，都将抛出异常到调用环境中

## 请求的生命周期

## 多种输出格式的自动支持

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
## 全局唯一的YZE_Request

## 代码结构

## Hooks