<?php 
namespace yangzie;

$exception 	= $this->get_data("exception");
$request 	= YZE_Request::get_instance();

?>
<p>您正处于开发者模式, 如果你访问的是正式环境，请把__config__.php中的YZE_DEVELOP_MODE修改为false</p>
<p>异常：<?php echo get_class($exception)?></p>
<p>URI：<?php echo $request->the_uri()?></p>
<p>Module：<?php echo $request->module()?></p>
<p>CWD：<?php echo getcwd()?></p>
<p>Controller：<?php echo $request->controller_name()?></p>
<p>Exception Message：<?php echo $exception ? $exception->getMessage() : ""?></p>
<p>异常栈:</p>
<pre>
<?php 
echo $exception ? $exception->getTraceAsString() : "";
?>
</pre>