## Yangzie -- 轻松构造模块化的应用

* 易点互联: <http://yidianhulian.com>
* Tags: php, web, module

## 特性

* 模块化
 * 模块之间保持松耦合，各自独立
 * 模块可以是一个目录，也可以打包成phar文件，可直接拷贝到其他使用yangzie开发到系统中直接使用
 * 每个模块便是一个MVC系统，定义有自己的url路由，验证器，控制器，视图，model
 * 模块之间调用通过url调用，只是这里的url不用在通过http，不用经过浏览器，比如在uri:http://example.com/order,处理的控制器是order，属于order模块，但是需要访问customer模块的userinfo控制器，以便得到客户的信息，只需要在代码中通过yze_go("/customer/userinfo?customer_id=24543"),便可得到客户的信息

* 灵活的视图
 * 任何请求都可响应输出为html，xml，json，pdf，excel，word等输出模版。只需两步：
 
    1.改变访问url后缀，无后缀默认返回html格式的内容，如http://example.com/order, 如果访问http://example.com/order.xml 将返回xml格式的内容，依此类推
    
    2.在模块的views目录中加上对应的模版文件, 如order.xml.php；在次文件中定义你要返回的xml内容；视图文件的命名格式是：[控制器名].[格式].php; 如order.json.php返回json格式内容；order.pdf.php 返回pdf格式内容；order.tpl.php返回html格式内容。注意，格式除了tpl外其他都是你自己定义的。yangzie会根据url的后缀去找调用对应的模版文件输出内容

* 表单数据回显
 * 只需在生成表单的地方加上一句代码调用：__yze_get_default_value($object, $name, $controller)__。比如
 &lt;input type="text" name="email" value="&lt;?php yze_get_default_value($user, "email", $this->controller)?&gt;"/&gt;
 * 在用户提交的数据验证器验证失败后，之前提交的数据会自动回显；避免用户重复再输入
 * 自定义错误提示。用户输入错误在所难免，但出错后应该友好的提醒用户哪里错了，（当然输入正确的数据应该还在那里），把__yze_controller_error()__放在合适的地方，出错后便会把错误消息显示在这里。该api是把错误消息集中显示在某个地方。如果想在错误的输入项附近显示错误消息，则只需要合适的地方调用__yze_field_error($field_name)__。如 &lt;input type="text" name="email" value="&lt;?php yze_get_default_value($user, "email", $this->controller)?&gt;"/&gt; &lt;?php yze_field_error("email") ?&gt;

* 验证器，把数据验证代码独立出来

* 通过脚本生成基础代码