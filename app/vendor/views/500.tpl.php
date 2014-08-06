<?php 
namespace yangzie;

?>
<h1>服务器出现500错误</h1>
<?php 
$exception = $this->get_data("exception");
$request 	= YZE_Request::get_instance();
?>
<h2 class="page-header"><?php echo $exception->getMessage()?></h2>

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
<p style="color:#ff0000;">请根据实际情况修改该页面，该页面位于vendor/views/500</p>
<p>Controller：<?php echo $request->controller_name()?>::exception()中写代码来处理异常</p>