<?php
echo '<h2>找不到类定义</h2>';

$exception = $this->the_data("exception");
if($exception){
	echo '<p>'.$exception->getMessage().'</p><br/>';
	echo (nl2br($exception->getTraceAsString()));
}
?>