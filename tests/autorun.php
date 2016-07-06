<?php
include 'config.php';

if(@$argv[1]){
	system("php run-tests.php {$argv[1]}");
}else{
	system("php run-tests.php ./");
}
//while(1){
//    //自动运行测试文件
//    echo ($i++)."\r\n";
//}
?>