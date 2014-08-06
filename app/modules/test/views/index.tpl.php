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
$yze_form = new \yangzie\YZE_Form($this, "test",null);
$yze_form->begin_form(array(/*"action"=>"/test/go"*/));

$session 	= YZE_Session_Context::get_instance();
$controller = YZE_Request::get_instance()->controller();

//var_dump( get_class($controller));
//var_dump( $_SESSION);
        
echo \yangzie\yze_controller_error();
?>
<input name="name" value=""/><input type="submit" value="Go"/>
<?php echo \yangzie\yze_form_field_error($this->controller, "name")?>
<?php 
$yze_form->end_form();
?>