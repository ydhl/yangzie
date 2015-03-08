<?php
namespace app\user;
use \yangzie\YZE_Resource_Controller;
use \yangzie\YZE_Request;
use \yangzie\YZE_Redirect;
use \yangzie\YZE_Session_Context;
use \yangzie\YZE_RuntimeException;

/**
*
* @version $Id$
* @package user
*/
class Add_Controller extends YZE_Resource_Controller {
    public function index(){
        $request = $this->request;
        //$this->layout = 'tpl name';
        $this->set_view_data('yze_page_title', 'Add user');
    }
    
    public function post_index(){
        $this->post_result_of_json = array("result"=>true);
    }

    public function add2(){
        
    }
    
    public function post_add2(){
        $this->post_result_of_json = array("result"=>true);
    }
    
    public function exception(YZE_RuntimeException $e){
        $request = $this->request;
        $this->layout = '';
        //get,post,put,delete处理中出现了异常，如何处理，没有任何处理将显示500页面
        //如果想显示get的返回内容可调用 :
        return $this->wrapResponse($this->index());
    }
    
    public function get_response_guid(){
        //如果该控制器的响应输出需要缓存，这里返回生成缓存文件的唯一id
        return null;
    }
    
    /*
     * @see YZE_Resouse_Controller::cleanup()
     */
    public function cleanup(){
        //pass
        parent::cleanup();
    }

}
?>