<?php
class Generate_Module_Script extends AbstractScript{
	private $module_name;
	const USAGE = "generate module,usage:

php generate.php -cmd module  -mod module_name
    -cmd controller：命令名
    -mod module_name：模块名
";

	public function generate(){
		$argv = $this->args;
		$this->module_name = $argv['module_name'];

		if(empty($this->module_name)){
			die(__(Generate_Module_Script::USAGE));
		}

		echo "module at 'app/modules/".$this->module_name."';\r\n";
		
		$this->save_module();
	}

	
	private function save_module(){
		$module = $this->module_name;
		//创建modules dir
		$path = dirname(dirname(__FILE__))."/app/modules/".$module;
		$this->check_dir($path);
		$this->check_dir($path."/controllers");
		$this->check_dir($path."/models");
		$this->check_dir($path."/validates");
		$this->check_dir($path."/views");
		$this->check_dir(dirname(dirname(__FILE__))."/tests/".$module);

		//生成module 配置文件
		$module = ucfirst($module);
		$config_file ="<?php
/**
 *
 * @version \$Id\$
 * @package $module
 */
class {$module}_Module extends YZE_Base_Module{
    public \$auths = array();
    public \$no_auths = array();
    protected function _config(){
        return array(
        'name'=>'{$module}',
        'include_path'=>array(),
        'include_files'=>array(),
        'routers' => array(
        )
        );
    }
}
?>";
		echo "create __module__.php:\t\t";
		$this->create_file("$path/__module__.php",$config_file);
		
		//如果module没有在app config中，则加入
	}

	
}
?>
