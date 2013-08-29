<?php
/**
 * 视图的描述
 * @param type name optional
 *
 */
 
$view = new YZE_XML_View($this->controller, array("msg"=>"hello world"));
$view->output();
?>