<?php
abstract class El extends YangzieObject{
	protected $view;
	protected $name;
	private $acl;
	public $aco_name;
	
	public function __construct(View_Adapter $view,$name){
		$this->view = $view;
		$this->name = $name;
		$this->acl = new ACL();
	}
	
	/**
	 * 
	 * 元素的开始标签，如：<input type="text" $attrs>
	 * @param $attrs string|array 属性字符串或者数组 
	 * @param $value string value=$value
	 * @param $inner_content string 标签中显示的文本
	 */
	public abstract function el($attrs,$value='',$inner_contents='');
	
	public function display($attrs='',$value='',$inner_contents=''){
		$app_auth = new App_Auth();
		$aroname = $app_auth->get_request_aro_name();
		if(!$this->acl->check_byname($aroname,$this->aco_name)){
			echo '';return;
		}
		if(is_array($attrs)){
			$attrs['name'] = $this->name;
			$html='';
			foreach ($attrs as $n=>$value){
				$html .= "$n = '$value' ";
			}	
		}else{
			$html = $attrs." name='{$this->name}'";
		}
		
		echo $this->el($html,$value,$inner_contents);
	}
}

//class El_Factory{
//	const TEXT = "text";
//	public static function create_el($type){
//		
//	}
//}
?>