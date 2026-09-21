<?php
namespace app\test;
use \yangzie\YZE_Model;
use \yangzie\YZE_SQL;
use \yangzie\YZE_DBAException;
use \yangzie\YZE_DBAImpl;
use yangzie\GraphqlSearchNode;

/**
 * 单元测试表 yze_encrypt_e2e 的业务方法
 *
 * @package test
 */
trait Yze_Encrypt_E2e_Method{
	/**
	 * 返回表的描述
	 * @return string
	 */
	public function get_description(){
		return 'yze_encrypt_e2e model';
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

	// 这里实现model的业务方法
}
?>