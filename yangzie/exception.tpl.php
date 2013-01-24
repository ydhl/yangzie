<?php 
$exception 	= $this->the_data("exception");
$request 	= Request::get_instance();
$request_module 	= $request->request_module();

?>
<p>您正处于开发者模式, 如果你访问的是正式环境，请把__config__.php中的DEVELOP_MODE修改为false</p>
<p>异常：<?php echo get_class($exception)?></p>
<p>URI：<?php echo $request->orgi_uri()?></p>
<p>Module：<?php echo $request->request_module()?></p>
<p>Controller：<?php echo $request->request_controller()?></p>
<p>Controller Path：<?php echo $request->controller_class()?></p>
<p>Exception Message：<?php echo $exception ? $exception->getMessage() : ""?></p>
<p>异常栈:</p>
<pre>
<?php 
echo $exception ? $exception->getTraceAsString() : "";
?>
</pre>