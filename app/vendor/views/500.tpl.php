<?php 
//自定义自己的500实现
?>
<h1>请求出错了</h1>
<?php 
$exception = $this->get_data("exception");?>
<h2 class="page-header"><?php echo $exception->getMessage()?></h2>
<pre>
<?php 
echo $exception->getTraceAsString();
?>
</pre>