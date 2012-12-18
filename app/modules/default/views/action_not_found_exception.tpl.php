<?php
$module 	= Request::get_instance()->module();
$method 	= Request::get_instance()->method();  
$controller = Request::get_instance()->request_controller();
$controller_class = Request::get_instance()->controller_class();
$dir = "app/modules/{$module}/controllers";
$file = $controller."_controller.class.php";
?>
<h1><?php echo vsprintf(__("未找到方法:%s"),array($method)) ?></h1>
<p>
<?php 
echo vsprintf(__("请先在控制器<i>%s</i>中创建方法 <i>%s</i>:"),array($dir."/".$file, $method));
?>
</p>
<code>
&lt;?php <br/>
class <?php echo $controller."_controller extends Resource_Controller{"?>
<br/>
&nbsp;&nbsp;&nbsp;&nbsp;public function <?php echo $method?>(){<br/>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;#这里写你的故事
<br/>
&nbsp;&nbsp;&nbsp;&nbsp;}
<br/>
}
<br/>?&gt;
</code>