<?php
namespace app\test;
use \yangzie\YZE_Resource_Controller;
use \yangzie\YZE_Request;
use \yangzie\YZE_Redirect;
use \yangzie\YZE_Session_Context;
use \yangzie\YZE_RuntimeException;

/**
 * 视图的描述
 * @param type name optional
 *
 */
global $yze_request_stack;

$data = $this->get_data('arg_name');
echo "<pre>";
echo YZE_Request::get_instance()->get_from_get("foo");
//echo count($yze_request_stack);
echo "<hr/>";
var_dump(\yangzie\yze_go("/test/go/test.json?foo=bar1&go=abc","get",true));

?>