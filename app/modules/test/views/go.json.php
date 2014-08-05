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
 echo YZE_Request::get_instance()->get_var(1);
 //var_dump(YZE_Request::get_instance());
//  echo count($yze_request_stack);
 //throw new YZE_RuntimeException("fdfd1");
 echo $this->get_data("data");
 //print_r($_SESSION);
?>
this is go json