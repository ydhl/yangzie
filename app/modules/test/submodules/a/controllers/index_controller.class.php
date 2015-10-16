<?php
namespace app\test;
use \yangzie\YZE_Resource_Controller;
use \yangzie\YZE_Request;
use \yangzie\YZE_Redirect;
use \yangzie\YZE_Session_Context;
use \yangzie\YZE_RuntimeException;

/**
*
* @version $Id$
* @package test
*/
class Index_Controller extends YZE_Resource_Controller {
    //通过ajax post表单时返回的数据
    protected $post_result_of_json = array();
    public function index(){
        $request = $this->request;
        //$this->layout = 'tpl name';
        $this->set_view_data('yze_page_title', 'this is sub controller index');
    }

    public function exception(YZE_RuntimeException $e){
        $request = $this->request;
        $this->layout = 'error';
        //get,post,put,delete处理中出现了异常，如何处理，没有任何处理将显示500页面
        //如果想显示get的返回内容可调用 :
        //return $this->wrapResponse($this->yourmethod())
    }
}
?>