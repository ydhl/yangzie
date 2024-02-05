<?php
// 公用404 视图
$exception = $this->Get_data("exception");
echo $exception ? $exception->getMessage() : "Page not found";
?>
