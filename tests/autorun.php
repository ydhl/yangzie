<?php
putenv("TEST_PHP_EXECUTABLE=D:/php-5.3.5-Win32-VC9-x86/php-win.exe");
putenv("TEST_PHP_DETAILED=0");//1或者0，设置日志的级别
//putenv("TEST_PHP_USER=./logs");//: 设置用户目录
putenv("TEST_PHP_LOG_FORMAT=LD");//: 日志的格式，LEOD。(.log .exp .out .diff)

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