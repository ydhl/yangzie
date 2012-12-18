<?php
?>
<div class="corner margin10 padding10" style="width:800px;margin:0px auto;">
<div class="sorry">
    <br/>
    <br/>
    <h3>
        未经许可的访问
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