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
 
try {
    echo file_get_contents('phar:///Users/apple/workspace/yangzie/app/modules/welcome.phar/__module__.php');
} catch (\PharException $e) {
    echo $e;
}
?>

this is index view