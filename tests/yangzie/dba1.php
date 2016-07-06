<?php
namespace  yangzie;
ini_set("display_errors",0);
chdir(dirname(dirname(dirname(__FILE__)))."/app/public_html");
include "init.php";

//this test only work for me 

$db = YZE_DBAImpl::getDBA();

echo $db->lookup("id","users","id >=:id", array(":id"=>"1")),"\r\n";
print_r( $db->lookup_record("id,created_on","users","id =:id", array(":id"=>"1")) );
print_r( $db->lookup_record("id,created_on","users") );
print_r( $db->lookup_records("id,created_on","users","id in (1,2)") );
print_r( $db->update("users","name=:name", "id=:id", array(":id"=>"1",":name"=>"test")) );
echo "\r\n";
?>
