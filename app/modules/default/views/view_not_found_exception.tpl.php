<?php
$module 	= Request::get_instance()->module();
$method 	= Request::get_instance()->method();  
$controller = Request::get_instance()->request_controller();
$controller_class = Request::get_instance()->controller_class();
$dir = "app/modules/{$module}/controllers";
$file = $controller."_controller.class.php";
?>
<h1><?php echo vsprintf(__("未找到view文件: %s/views/%s.tpl.php"),array($module, $controller)) ?></h1>
请创建该文件