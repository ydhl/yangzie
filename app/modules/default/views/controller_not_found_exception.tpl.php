<?php 
$controller = Request::get_instance()->request_controller();
$request_module = Request::get_instance()->request_module();

$controller_class = Request::get_instance()->request_controller_class();
$dir = "app/modules/$request_module/controllers";
$file = $controller."_controller.class.php";
?>
<h1><?php echo vsprintf(__("未找到控制器%s"),array($file)) ?></h1>
<p>
<?php 
echo vsprintf(__("请先在目录<i>%s</i>中创建文件<i>%s</i>:"),array($dir,$file));
?>
</p>
<code>
&lt;?php <br/>
class <?php echo $controller_class." extends Resource_Controller{"?>
<br/>
}
<br/>?&gt;
</code>