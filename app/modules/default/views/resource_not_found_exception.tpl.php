<?php
//TODO 定义视图显示
?>
<div class="corner margin10 padding10" style="width:800px;margin:0px auto;">
<div class="notfound">
    <br/>
    <br/>
    <h3>
        您请求的资源不存在，请确认您的访问地址是否正确
    </h3>
    <br/>
    <p>
    <?php 
    $exception = $this->the_data("exception");
    if ($exception) {
    	echo $exception->getMessage();
    }
    ?>
    </p>
    <br/>
    <br/>
</div>
</div>