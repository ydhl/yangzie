<?php
namespace yangzie;

use app\yze_get_ignore_acos;
/**
 * 访问控制列表（ACL）管理类
 *
 * 基于 ACO（Access Control Object，被访问的对象）与 ARO（Access Request Object，请求访问的角色/用户）
 * 两级权限模型实现权限检查，支持"拒绝优先、逐级向上匹配"的权限判断规则。
 *
 * @package yangzie
 */
class YZE_ACL extends YZE_Object{
	/**
	 * ACO 与 ARO 的权限映射表，结构：[aco=>["deny"=>[...], "allow"=>[...]]]
	 *
	 * @var array
	 */
	private $acos_aros;

	/**
	 * 权限检查结果缓存，结构：[aroname][aconame]=>bool
	 *
	 * @var array
	 */
	private $permission_cache = array();

	/**
	 * 单例实例
	 *
	 * @var YZE_ACL|null
	 */
	private static $instance;

	/**
	 * 私有构造函数，加载并排序权限映射表，禁止外部直接实例化
	 */
	private function __construct(){
		$this->acos_aros = \app\yze_get_acos_aros();
		krsort($this->acos_aros);
		$newarr = array();
		foreach ($this->acos_aros as $aco=>$aros){
		    if(is_array($aros['deny'])) {
		    	krsort($aros['deny']);
		    }
		    if(is_array($aros['allow'])){
		    	krsort($aros['allow']);
		    }
		    $newarr[$aco] = $aros;
		}
		$this->acos_aros = $newarr;
	}
	/**
	 * 检查当前登录用户对指定 ACO 的权限（基于 get_user_permissions 返回的权限配置）
	 *
	 * @param string $aconame ACO 名称
	 * @return bool|int 允许返回 true，拒绝返回 false，未明确配置返回 -1
	 */
	private function check_user_permission($aconame){
		$perm = get_user_permissions();

		if(!$perm)return -1;
		// ai@2026-05-27 替换 @ 抑制符，使用 ?? null 显式处理
		if (is_array($perm["deny"] ?? null)){//配置了拒绝项
			$denys = $this->in_array($aconame, $perm["deny"]);//拒绝当前ACO
			if ($denys){//拒绝当前ACO的所有action
				return false;
			}
		}

		if (is_array($perm["allow"] ?? null)){//允许当前ACO
			$allow = $this->in_array($aconame, $perm["allow"]);//允许当前ACO

			if ($allow){//允许当前ACO的所有action
				return true;
			}
		}

		if(($perm["deny"] ?? null)=="*")return false;//拒绝优先
		if(($perm["allow"] ?? null)=="*")return true;//允许所有
		return -1;
	}
	/**
	 * 检查指定角色（ARO）对指定 ACO 的权限，未匹配时逐级向父级 ACO/ARO 递归匹配
	 *
	 * @param string $aroname ARO 角色名
	 * @param string $aconame ACO 对象名
	 * @return bool 允许返回 true，否则返回 false
	 */
	private function check_role_permission($aroname, $aconame){
		if (!trim($aroname)) {
			return false;
		}

		if(function_exists("get_permissions")){
			$perm = get_permissions($aroname);

			// ai@2026-05-27 替换 @ 抑制符，使用 ?? null 显式处理
			if (is_array($perm["deny"] ?? null)){//配置了拒绝项
				$denys = $this->in_array($aconame, $perm["deny"]);//拒绝当前ARO
				if ($denys){//拒绝当前ACO的所有action
					return false;
				}
			}

			if (is_array($perm["allow"] ?? null)){//允许当前ACO
				$allow = $this->in_array($aconame, $perm["allow"]);//允许当前ARO
				if ($allow){//允许当前ACO的所有action
					return true;
				}
			}

			if(($perm["deny"] ?? null)=="*")return false;//拒绝优先

			if(($perm["allow"] ?? null)=="*")return true;//允许所有

			if($aconame=="/") return false;//都没找到

			$aconames = explode("/", $aconame);
			array_pop($aconames);
			$aconame= count($aconames)==1 ? "/" : join("/", $aconames);
			return $this->check_role_permission($aroname, $aconame);
		}


		// ai@2026-05-27 替换 @ 抑制符，使用 ?? null 显式处理
		$perm = $this->acos_aros[$aconame] ?? null;


		if (is_array($perm["deny"] ?? null)){//配置了拒绝项
			$denys = $this->in_array($aroname, $perm["deny"]);//拒绝当前ARO
			if ($denys){//拒绝当前ACO的所有action
				return false;
			}
		}

		if (is_array($perm["allow"] ?? null)){//允许当前ACO
			$allow = $this->in_array($aroname, $perm["allow"]);//允许当前ARO
			if ($allow){//允许当前ACO的所有action
				return true;
			}
		}

		if(($perm["deny"] ?? null)=="*")return false;//拒绝优先

		if(($perm["allow"] ?? null)=="*")return true;//允许所有

		if($aroname=="/") return false;//都没找到

		$aronames = explode("/", $aroname);
		array_pop($aronames);
		$aroname = count($aronames)==1 ? "/" : join("/", $aronames);
		return $this->check_role_permission($aroname, $aconame);
	}
	/**
	 * 判断指定 ACO 是否在需要权限控制的范围内
	 *
	 * 匹配到忽略列表（yze_get_ignore_acos）返回 null，匹配到权限映射表返回该 ACO 名
	 *
	 * @param string $aconame ACO 名称
	 * @return string|null 需要控制时返回命中的 ACO 名，忽略或未配置时返回 null
	 */
	private function need_controll($aconame){
		$array = \app\yze_get_ignore_acos();

		foreach ((array)$array as $aco) {
			$newaco = strtr($aco, array("*"=>".*"));
			if (preg_match("{^".$newaco."}i", $aconame)){
				return null;
			}
		}
		foreach ((array)$this->acos_aros as $aco=>$ignore) {
			$newaco = strtr($aco, array("*"=>".*"));
			if (preg_match("{^".$newaco."}i", $aconame)){
				return $aco;
			}
		}
		return null;
	}
	/**
	 * 判断 $check 是否匹配 $arrays 中的任意一项（支持通配符 *）
	 *
	 * @param string $check 待匹配的 ACO/ARO 名称
	 * @param array  $arrays 匹配规则列表，含 * 通配符
	 * @return bool 匹配任意一项返回 true，否则返回 false
	 */
	private function in_array($check, array $arrays){
		foreach ($arrays as $k) {
			if ($k==$check) return true;
			if(substr($k, -1) != "*"){
				$k .= "/*";
			}
			$k = strtr($k, array("*"=>".*"));
			// ai@2026-05-27 修复 PHP 8 兼容：preg_match subject 不能为 null
		if ($check && preg_match("{^".$k."$}i", $check)) {
				return true;
			}
		}
		return false;
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
	 * 开始检查权限，在begin_check_permission和end_check_permission之间的内容将在$aroname具有$aconame访问权限时输出
	 *
	 * @author leeboo
	 * @param string $aroname
	 * @param string $aconame
	 * @return void
	 */
	/**
	 * 开始权限检查：开启输出缓冲并缓存检查结果
	 *
	 * 与 end_check_permission 配合使用，两者之间的内容仅在 $aroname 具有 $aconame 访问权限时输出
	 *
	 * @param string $aroname 请求访问的角色名
	 * @param string $aconame 被访问的对象名
	 * @return void
	 */
	public function begin_check_permission($aroname, $aconame){
		ob_start();
		if(isset($this->permission_cache[$aroname][$aconame])) return;
		$this->permission_cache[$aroname][$aconame] = $this->check_byname($aroname, $aconame);
	}

	/**
	 * 结束权限检查：根据检查结果决定是否输出缓冲内容
	 *
	 * 与 begin_check_permission 配合使用，$aroname 具有 $aconame 访问权限时输出缓冲内容，否则丢弃
	 *
	 * @param string $aroname 请求访问的角色名
	 * @param string $aconame 被访问的对象名
	 * @return void
	 */
	public  function end_check_permission($aroname, $aconame){
		// ai@2026-05-27 替换 @ 抑制符，使用 ?? null 显式处理
		if(($this->permission_cache[$aroname][$aconame] ?? null)){
			ob_end_flush();
			return;
		}
		ob_end_clean();
	}

	/**
	 * 检查 $aroname 是否有对 $aconame 的访问权限
	 *
	 * $aroname 可以是单个角色名，也可以是角色名数组（多角色时任一角色有权限即通过）
	 *
	 * @param string|array $aroname 角色名或角色名数组
	 * @param string       $aconame 被访问的对象名
	 * @return bool 有访问权限返回 true，否则返回 false
	 */
	public function check_byname($aroname, $aconame){
	    $aconame = $this->need_controll($aconame);
		if ( ! $aconame) {
			return true;
		}

		if(function_exists("get_user_permissions")){
			$check_rst = $this->check_user_permission($aconame);
			if($check_rst!==-1)return $check_rst;
		}
		if(is_array($aroname)){//当前用户有多个角色
			foreach ($aroname as $value) {
				$check_rst = $this->check_role_permission($value, $aconame);
				if($check_rst)return true;
			}
			return false;
		}else{
			return $this->check_role_permission($aroname, $aconame);
		}
	}

}

?>
