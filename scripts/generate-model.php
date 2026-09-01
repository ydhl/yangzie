<?php
namespace yangzie;

use app\App_Module;

class Generate_Model_Script extends AbstractScript{
	protected $base;
	protected $module_name;
	protected $table_name;
	protected $class_name;
	protected $db_name;
	protected $uuid;
	private $enum_fields = [];
	static $chain_tables = [];

	public function generate(){
		$app_module = new \app\App_Module();
		$argv = $this->args;
		$this->base 				= $argv['base'];
		$this->module_name 	= $argv['module_name'];
		$this->table_name 		= $argv['table_name'];
		$this->class_name 		= $argv['class_name'];
		$this->db_name 		= $argv['db_name'] ?: $app_module->get_module_config("default_db");
		// ai@2026-05-27 替换 @ 抑制符，使用 ?? null 显式处理
		$this->uuid = $argv['uuid'] ?? null;

		if(empty($this->db_name) || empty($this->module_name) || empty($this->table_name)  || empty($this->class_name) ){
			die(YZE_SCRIPT_USAGE);
		}

		$generate_module = new Generate_Module_Script(array("module_name" => $this->module_name));
		$generate_module->generate();
		//Model
		$model_class = YZE_Object::format_class_name($this->class_name,"Model");
		$method_class = YZE_Object::format_class_name($this->class_name,"Method");

		$handleResult = $this->create_model_code($model_class, $method_class);
		echo __("create model {$model_class} :");
		$this->save_class($handleResult, $this->class_name, $this->module_name, 'model');

		// ai@2026-08-27 为每个 enum 字段生成 PHP enum 类型文件（类型名/文件名均为小写）
		foreach ($this->enum_fields as $enum_field => $enum_values) {
			$enum_type = YZE_Object::format_class_name($this->class_name . "_" . $enum_field, "");
			echo __("create enum {$enum_type}_Enum :");
			$this->save_class($this->create_enum_code("{$enum_type}_Enum", $enum_values), $enum_type, $this->module_name, 'enum');
		}

		$code = $this->create_method_code($method_class);
		echo __("create model method {$method_class} ");
		$this->save_class($code, $this->class_name, $this->module_name, 'method', false);

		echo __("create model {$model_class} phpt file :");
		$this->save_test($handleResult, $this->class_name, $this->module_name);

		echo __("Done!\n");
	}

    public function create_method_code($class){
        $package=$this->module_name;

        return "<?php
namespace app\\$package;
use \yangzie\YZE_Model;
use \yangzie\YZE_SQL;
use \yangzie\YZE_DBAException;
use \yangzie\YZE_DBAImpl;
use yangzie\GraphqlSearchNode;
/**
 *
 * 
 * @version \$Id\$
 * @package $package
 */
trait $class{
	public function $class(){
		
	}
	
    /**
	 * 返回表的描述
	 * @param \$column
	 * @return mixed
	 */
    public function get_description(){
		return '".$this->class_name." model';
	}

	/**
	 * 当前model是否允许在graphql中进行查询
	 * @return boolean
	 */
	public static function is_enable_graphql():bool{
		return false;
	}

	/**
	 * 自定义的Field，field的type nane是系统的表名, 该接口提供一个在通过graphql查询{$this->table_name}时可以联合查询的其他表的方式
	 * 
	 * 如果要返回的不是表名而是自定义的类型，那么该类型必须通过YZE_GRAPHQL_TYPES进行定义并返回
	 * 
	 * 具体如何查询，需要在query_graphql_fields中实现
	 * @return array<GraphqlField>
	 */
	public function custom_graphql_fields(): array{
		return [];
	}
	
	/**
	 * 根据传入的fieldName名返回对应的subFields值
	 * @param \$graphqlSearchNode 查询的内容结构体
	 * @return array<GraphqlField>
	 */
	public function query_graphql_fields(GraphqlSearchNode \$searchNode): array|null{
		return [];
	}
    // 这里实现model的业务方法 
}?>";
    }


	public function create_model_code($class, $method_class){
		$table = $this->table_name;
		$package=$this->module_name;
		$dbName = $this->db_name;
		$uuid = 'uuid';

		$app_module = new \app\App_Module();

		$db_connection = $app_module->get_module_config('db_connections')[$dbName];

		$db = mysqli_connect($db_connection["db_host"], $db_connection["db_user"], $db_connection["db_psw"], $dbName, $db_connection["db_port"]);

		$importClass = [];
		$relation_column = [];
		$assocFields = "";
		$assocFieldFuncs = "";
		// ai@2026-08-27 收集 enum 字段及其取值，用于生成 PHP enum 类型
		$enum_fields = array();
		mysqli_select_db($db, "INFORMATION_SCHEMA");
		$result = mysqli_query($db, "select COLUMN_NAME,CONSTRAINT_NAME,
		REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME from KEY_COLUMN_USAGE
		where TABLE_SCHEMA = '".$dbName."' and TABLE_NAME = '{$table}'
		and referenced_column_name is not NULL");
		while ($row=mysqli_fetch_assoc($result)) {
			$col = preg_replace('/_id$/','', $row['COLUMN_NAME']);
			$col_class = $this->get_class_of_table($row['REFERENCED_TABLE_NAME']);

			if ($row['REFERENCED_TABLE_NAME'] != $this->table_name){
				$importClass[] = "use $col_class;";
			}
			$sortClass = substr($col_class, strripos($col_class, "\\")+1);
			$relation_column[$row['COLUMN_NAME']] = [
				'graphql_field'=>$col,
				'target_class'=>$col_class,
				'target_column'=>$row['REFERENCED_COLUMN_NAME']
			];
			$assocFields .= "
	private {$sortClass} \${$col};";
			$assocFieldFuncs .= "
	public function get_{$col}(\$suffix=null): {$sortClass}{
		if( ! \$this->{$col}){
			\$this->{$col} = {$sortClass}::find_by_id(\$this->".$row['COLUMN_NAME'].", \$this->db, \$suffix);
		}
		return \$this->{$col};
	}

	/**
	 * @return $class
	 */
	public function set_{$col}({$sortClass} \$new){
		\$this->{$col} = \$new;
		return \$this;
	}
";
		}


		mysqli_select_db($db, $this->db_name);
		mysqli_query($db, "set names UTF8MB4");


		$unique_key = array();
		$result = mysqli_query($db, "SHOW INDEX FROM  `$table`");
		while ($row=mysqli_fetch_assoc($result)) {
		    $unique_key[$row['Column_name']] = $row['Key_name'];
		}

		$result = mysqli_query($db, "show full columns from `$table`");
		while ($row=mysqli_fetch_assoc($result)) {
			$row['Key']=="PRI" ? $key = $row['Field'] : null;
			$type_info = $this->get_type_info($row['Type']);
			$currEnums = (array)$this->getEnumConstant($row['Field'], $row['Type']);

			// ai@2026-08-27 不再生成 get_{field}() 方法，改由生成的 PHP enum 类型的 cases() 提供
			if ($currEnums){
				$enum_fields[$row['Field']] = array_values($currEnums);
			}

			// ai@2026-08-27 字段属性使用 Column 注解声明元数据
			// ai@2026-08-27 enum 类型使用生成的 PHP enum 类（类型名全小写）；原逻辑会得到 "enum" 关键字，作为类型声明是非法的
			if ($type_info['type'] == 'enum') {
				$php_type = YZE_Object::format_class_name($this->class_name . "_" . $row['Field'], "Enum");
			} else {
				$php_type = $type_info['type'] == 'date' ? 'string' : $type_info['type'];
			}
			$is_nullable = strcasecmp($row['Null'],"YES") ? false : true;
			$comment = str_replace(array('*/', "\r", "\n"), array('* /', '', ''), (string)($row['Comment'] ?? ''));
			$attr_args = "type: '".$type_info['type']."', nullable: ".($is_nullable ? 'true' : 'false');
			if (($type_info['length'] ?? '') !== '') {
				$attr_args .= ", length: ".intval($type_info['length']);
			}
			if (($row['Default'] ?? null) !== null && $row['Default'] !== '') {
				$attr_args .= ", default: '".addslashes($row['Default'])."'";
			}
			$attributeFields = ($attributeFields ?? '') . "
    /**
     * {$comment}
     * @var {$php_type}
     */
    #[Column({$attr_args})]
    private ".($is_nullable ? '?' : '')."{$php_type} \${$row['Field']};";
			$propertyDoc = ($propertyDoc ?? '') . " * @property ".($is_nullable ? '?' : '')."{$php_type} \${$row['Field']} ".trim($comment)."
";
			// ai@2026-08-27 不再生成 F_ 字段常量，get_column_mean 直接使用字段名
			$column_mean[] = "case \"".$row['Field']."\": return \"".($row['Comment']?:$row['Field'])."\";";
		}

		// ai@2026-08-27 供 generate() 生成 enum 类型文件使用
		$this->enum_fields = $enum_fields;

		return "<?php
namespace app\\$package;
use \yangzie\YZE_Model;
use \yangzie\YZE_SQL;
use \yangzie\YZE_DBAException;
use \yangzie\YZE_DBAImpl;
use \yangzie\Column;
".join("\r\n",array_unique($importClass))."
/** 
 * DO NOT EDIT THIS FILE
 * @package $package
".rtrim($propertyDoc)." */
class $class extends YZE_Model{
    use $method_class;

    const TABLE= \"$table\";
    const MODULE_NAME = \"$package\";
    const KEY_NAME = \"$key\";
	const UUID_NAME = \"$uuid\";

    $attributeFields
    /**
     * @see YZE_Model::\$unique_key
     */
    protected \$unique_key = ".var_export($unique_key, true).";

    /**
     * @see YZE_Model::\$relation_column
     */
    protected \$relation_column = ".var_export($relation_column, true).";
    		
    {$assocFields}
	{$assocFieldFuncs}
	
	/**
	 * 返回每个字段的描述文本
	 * @param \$column
	 * @return mixed
	 */
    public function get_column_mean(\$column){
    	switch (\$column){
    	".join("\r\n\t\t",$column_mean)."
    	default: return \$column;
    	}
		return \$column;
	}
}?>";
	}

	/**
	 * 生成 MySQL enum 字段对应的 PHP enum 类型代码
	 *
	 * @param string $enum_type 类型名（全小写）
	 * @param array  $values    MySQL enum 取值列表
	 * @return string
	 */
	public function create_enum_code($enum_type, $values){
		$cases = '';
		foreach ($values as $v) {
			// ai@2026-08-27 case 名与值均为 MySQL enum 值
			$cases .= "    case ".strtoupper($v)." = '{$v}';\n";
		}
		return "<?php
namespace app\\{$this->module_name};
/**
 * DO NOT EDIT THIS FILE
 * @package {$this->module_name}
 */
enum {$enum_type}: string{
{$cases}}";
	}

	protected function getEnumConstant($name, $type){
		if(preg_match("/^enum\((?<v>.+)\)/",$type,$matches)){
			foreach(explode(",",$matches['v']) as $c){
				$c = trim($c,"'");
				$constant[strtoupper($name)."_".strtr(strtoupper($c),array("-"=>"_"," "=>"_"))] = $c;
			}
			return $constant;
		}
	}
	/**
	 *
	 *
	 * @author leeboo
	 *
	 * @param string $type
	 * @return array('type','length')
	 */
	protected function get_type_info(string $type){
		$ret = array("type"=>"","length"=>"");

		if(preg_match("/^int/i",$type)||
		preg_match("/^tinyint/i",$type)||
		preg_match("/^smallint/i",$type)||
		preg_match("/^mediumint/i",$type)||
		preg_match("/^bigint/i",$type)){
			if(preg_match("/\((\d+)\)/", $type, $matchs)){
				$ret['length']=$matchs[1] ?? null;
			}
			$ret['type']="int";

			return $ret;
		}
		if(preg_match("/^decimal/i",$type)||
		preg_match("/^float/i",$type)||
		preg_match("/^double/i",$type)){
			if(preg_match("/\((\d+)\)/", $type, $matchs)){
				$ret['length']=$matchs[1] ?? null;
			}
			$ret['type']="float";

			return $ret;
		}
		if(preg_match("/^timestamp/",$type)||
		preg_match("/^date/",$type)||
		preg_match("/^datetime/",$type)||
		preg_match("/^time/",$type)||
		preg_match("/^year/",$type)){
			$ret['type']="date";
			return $ret;
		}
		if(preg_match("/^enum/",$type)){
			$ret['type']="enum";
			return $ret;
		}

		if(preg_match("/\((\d+)\)/", $type, $matchs)){
			$ret['length']=$matchs[1] ?? null;
		}
		$ret['type']="string";
		return $ret;
	}


	protected function save_test($handleResult,$class,$package){
		$class = strtolower($class);
		$path = dirname(dirname(__FILE__))."/tests/".$package;
		$this->check_dir($path);

		$class_file_path = dirname(dirname(__FILE__))
			."/tests/". $package."/" ."{$class}.model.phpt";

		$test_file_content = "--TEST--
	$class class Model Unit Test
--FILE--
<?php
	ini_set(\"display_errors\",0);
	chdir(dirname(dirname(dirname(__FILE__))).\"/app/public_html\");
	include \"init.php\";
	//write you test code here
?>
--EXPECT--
	";

		$this->create_file($class_file_path, $test_file_content);
	}

	protected function save_class($handleResult,$class,$package, $format="class", $overwrite=true){
		$class = strtolower($class);
		$path = dirname(dirname(__FILE__))."/app/modules/".$package."/models";
		$this->check_dir(dirname(dirname(__FILE__))."/app/modules/".$package);
		$this->check_dir($path);


		$class_file_path = dirname(dirname(__FILE__))
			."/app/modules/".$package."/models/{$class}.{$format}.php";
		if (!$overwrite && file_exists($class_file_path)){
			echo get_colored_text("file exists", "red", "white")."\r\n";return;
		}
		$this->create_file($class_file_path, $handleResult, true);
	}

	protected function get_class_of_table($table){
		global $db;
		$class_name = YZE_Object::format_class_name($table,"Model");
		if(class_exists($class_name)){
			return $class_name;
		}

		if ( @ self::$chain_tables[$table] ){//之前已经处理过了
			return "\\app\\".self::$chain_tables[$table]."\\{$class_name}";
		}

		clear_terminal();

		echo wrap_output(sprintf(__("\r\n
未能识别关联表%s的Model类，请输入该类所在的module名（默认当前模块）："), $table));
		$module = get_input();

		if ( ! $module){
			$module = $this->module_name;
		}

		self::$chain_tables[$table] = $module;

		if (class_exists("\\app\\{$module}\\{$class_name}")){
			return "\\app\\{$module}\\{$class_name}";
		}

		echo get_colored_text(wrap_output(sprintf(__("    开始生成 %s..."), "\\app\\{$module}\\{$class_name}")), "blue", "white")."\r\n";
		$object = new \yangzie\Generate_Model_Script(array("cmd" => "model",
			"base"       => "table",
			"module_name"=> $module,
			"class_name" => preg_replace('/_model$/i', "", $class_name),
			"table_name" => $table,
			"uuid" => $this->uuid,
			"db_name" => $this->db_name));
		$object->generate();
		echo "\r\n".get_colored_text(wrap_output(sprintf(__("    生成结束 %s ."), "\\app\\{$module}\\{$class_name}")), "blue", "white")."\r\n";


		return "\\app\\{$module}\\{$class_name}";
	}
}
?>
