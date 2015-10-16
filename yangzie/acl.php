<?php
namespace yangzie;

class YZE_ACL extends YZE_Object{
	private $acos_aros;
	private $permission_cache = array();
	private static $instance;
	
	private function __construct(){
		$this->acos_aros = \app\yze_get_acos_aros();
		krsort($this->acos_aros);
		$newarr = array();
		foreach ($this->acos_aros as $aco=>$aros){
		    krsort($aros['deny']);
		    krsort($aros['allow']);
		    $newarr[$aco] = $aros;
		}
		$this->acos_aros = $newarr;
	}
	
	/**
	 * 
	 * @return YZE_ACL
	 */
	public static function get_instance()
	{
		if (!isset(self::$instance)) {
			$c = __CLASS__;
			self::$instance = new $c;
		}
		return self::$instance;
	}
	
	/**
	 * 
	 * 开始检查权限
	 * 
	 * @author leeboo
	 * 
	 * @param unknown $id 唯一标识所检查对象的id
	 * @param unknown $aroname
	 * @param unknown $aconame
	 * 
	 * @return
	 */
	public  function begin_check_permission($id, $aroname, $aconame){
		ob_start();
		if(array_key_exists($id, $this->permission_cache) && array_key_exists($aconame, @$this->permission_cache[$id]))return;
		$this->permission_cache[$id][$aconame] = $this->check_byname($aroname, $aconame);
		
	}
	
	public  function end_check_permission($id, $aroname, $aconame){
		if($this->permission_cache[$id][$aconame]){
			return ob_get_clean();
		}
		ob_end_clean();
	}
	
	public function check_byname($aroname, $aconame){
	    $aconame = $this->_need_controll($aconame);
		if ( ! $aconame) {//不要求控制
			return true;
		}

		if(function_exists("get_user_permissions")){
			$check_rst = $this->_check_user_permission($aconame);
			if($check_rst!==-1)return $check_rst;
		}

		if(is_array($aroname)){//当前用户有多个角色
			foreach ($aroname as $value) {
				$check_rst = $this->_check_role_permission($value, $aconame);
				if($check_rst)return true;
			}
			return false;
		}else{
			return $this->_check_role_permission($aroname, $aconame);
		}
	}

	private function _check_user_permission($aconame)
	{
		$perm = get_user_permissions();

		if(!$perm)return -1;
		if (is_array(@$perm["deny"])){//配置了拒绝项
			$denys = $this->_in_array($aconame, $perm["deny"]);//拒绝当前ACO
			if ($denys){//拒绝当前ACO的所有action
				return false;
			}
		}

		if (is_array(@$perm["allow"])){//允许当前ACO
			$allow = $this->_in_array($aconame, $perm["allow"]);//允许当前ACO
			
			if ($allow){//允许当前ACO的所有action
				return true;
			}
		}
		if (@$perm["allow"]=="*"){//允许所有
			return true;
		}
		return -1;
	}

	private function _check_role_permission($aroname, $aconame){
		if (!trim($aroname)) {
			return false;
		}

		if(function_exists("get_permissions")){
			$perm = get_permissions($aroname);
		}else{
			$perm = @$this->acos_aros[$aconame];
		}

		if($perm["deny"]=="*")return false;//拒绝优先
		
		if (is_array(@$perm["deny"])){//配置了拒绝项
			$denys = $this->_in_array($aroname, $perm["deny"]);//拒绝当前ARO
			if ($denys){//拒绝当前ACO的所有action
				return false;
			}
		}

		if (is_array(@$perm["allow"])){//允许当前ACO
			$allow = $this->_in_array($aroname, $perm["allow"]);//允许当前ARO
			if ($allow){//允许当前ACO的所有action
				return true;
			}
		}
		if (@$perm["allow"]=="*"){//允许所有
			return true;
		}

		if($aroname=="/"){
			return false;
		}

		$aronames = explode("/", $aroname);
		array_pop($aronames);
		$aroname = count($aronames)==1 ? "/" : join("/", $aronames);
		return $this->_check_role_permission($aroname, $aconame, $matched_aco);
	}

	private function _need_controll($aconame){
		foreach ((array)$this->acos_aros as $aco=>$ignore) {
			$newaco = strtr($aco, array("*"=>".*"));
			if (preg_match("{".$newaco."}", $aconame)){
				return $aco;
			}
		}
		return null;
	}
	

	private function _in_array($check, array $arrays){
		foreach ($arrays as $k) {
			if ($k==$check) return true;
		    if($k{-1} != "*"){
		        $k .= "/*";
		    }
			$k = strtr($k, array("*"=>".*"));
			if (preg_match("{^".$k."$}", $check)) {
				return true;
			}
		}
		return false;
	}
}

?>