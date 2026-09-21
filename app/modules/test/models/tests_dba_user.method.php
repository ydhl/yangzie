<?php
namespace app\test;
use \yangzie\YZE_Model;
use \yangzie\YZE_SQL;
use \yangzie\YZE_DBAException;
use \yangzie\YZE_DBAImpl;
use yangzie\GraphqlSearchNode;

/**
 * 单元测试表 tests_dba_user 的业务方法
 *
 * @package test
 */
trait Tests_Dba_User_Method{
	/**
	 * 返回表的描述
	 * @return string
	 */
	public function get_description(){
		return 'tests_dba_user model';
	}

	/**
	 * 当前model是否允许在graphql中进行查询
	 * @return boolean
	 */
	public static function is_enable_graphql():bool{
		return false;
	}

	/**
	 * 自定义的Field
	 * @return array<GraphqlField>
	 */
	public function custom_graphql_fields(): array{
		return [];
	}

	/**
	 * 根据传入的fieldName名返回对应的subFields值
	 * @param GraphqlSearchNode $searchNode
	 * @return array<GraphqlField>
	 */
	public function query_graphql_fields(GraphqlSearchNode $searchNode): array|null{
		return [];
	}

	/**
	 * role 字段的枚举可选值
	 *
	 * dba.php 的 valid_entity() 通过 get_{字段名}() 获取枚举可选值，
	 * 取值由本表生成的 PHP enum 类型提供，保持单一数据源
	 *
	 * @return array
	 */
	public function get_role(){
		return array_map(function(Tests_Dba_User_Role_Enum $case){
			return $case->value;
		}, Tests_Dba_User_Role_Enum::cases());
	}

	// 这里实现model的业务方法
}
?>
