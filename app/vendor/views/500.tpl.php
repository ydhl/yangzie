<?php 
namespace yangzie;
    
    $this->layout = "tpl";
?>
<h4 class="page-header">服务器出现500错误</h4>
<?php 
$exception = $this->get_data("exception");
$request 	= YZE_Request::get_instance();
?>
<div class="alert alert-danger">
    <?php echo $exception->getMessage()?>
</div>
<?php if(YZE_DEVELOP_MODE){?>
<pre>
<?php echo $exception->getTraceAsString()?>
</pre>
<?php }?>
