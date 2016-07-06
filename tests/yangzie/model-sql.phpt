--TEST--
测试Model的各种查询方法
--FILE--
<?php
namespace  yangzie;
ini_set("display_errors",1);
chdir(dirname(dirname(dirname(__FILE__)))."/app/public_html");
include "init.php";

//测试用的Model
class UserModel extends YZE_Model{
	const TABLE= "users";
	const VERSION = 'modified_on';
	const MODULE_NAME = "test";
	const KEY_NAME = "id";
	const ID = "id";
	const CLASS_NAME = 'yangzie\UserModel';

	const TITLE = "title";
	const CREATED_ON = "created_on";
	const MODIFIED_ON = "modified_on";

	public static $columns = array(
			'id'         => array('type' => 'integer', 'null' => false,'length' => '11','default'	=> '',),
			'title'      => array('type' => 'string', 'null' => false,'length' => '201','default'	=> '',),
			'created_on' => array('type' => 'date', 'null' => false,'length' => '','default'	=> '',),
			'modified_on' => array('type' => 'date', 'null' => false,'length' => '','default'	=> 'CURRENT_TIMESTAMP',),
	);
}
class TestModel extends YZE_Model{
	const TABLE= "tests";
	const VERSION = 'modified_on';
	const MODULE_NAME = "test";
	const KEY_NAME = "id";
	const ID = "id";
	const CLASS_NAME = 'yangzie\TestModel';
	
	const TITLE = "title";
	const USER_ID = "user_id";
	const CREATED_ON = "created_on";
	const MODIFIED_ON = "modified_on";
	
	public static $columns = array(
			'id'         => array('type' => 'integer', 'null' => false,'length' => '11','default'	=> '',),
			'title'      => array('type' => 'string', 'null' => false,'length' => '201','default'	=> '',),
			'user_id'      => array('type' => 'integer', 'null' => false,'length' => '11','default'	=> '',),
			'created_on' => array('type' => 'date', 'null' => false,'length' => '','default'	=> '',),
			'modified_on' => array('type' => 'date', 'null' => false,'length' => '','default'	=> 'CURRENT_TIMESTAMP',),
	);
}
class OrderModel extends YZE_Model{
	const TABLE= "orders";
	const VERSION = 'modified_on';
	const MODULE_NAME = "order";
	const KEY_NAME = "id";
	const ID = "id";
	const CLASS_NAME = 'yangzie\OrderModel';

	const ORDER_ID = "order_id";
	const USER_ID = "user_id";
	const CREATED_ON = "created_on";
	const MODIFIED_ON = "modified_on";

	public static $columns = array(
			'id'         => array('type' => 'integer', 'null' => false,'length' => '11','default'	=> '',),
			'order_id'      => array('type' => 'string', 'null' => false,'length' => '201','default'	=> '',),
			'user_id'      => array('type' => 'integer', 'null' => false,'length' => '11','default'	=> '',),
			'created_on' => array('type' => 'date', 'null' => false,'length' => '','default'	=> '',),
			'modified_on' => array('type' => 'date', 'null' => false,'length' => '','default'	=> 'CURRENT_TIMESTAMP',),
	);
}

echo "单表查询:\r\n";

#where调用
echo TestModel::where("CHAR_LENGTH(title)=:title and (id>10 or id<20)")->getSingle();
#调用方法4
echo "\r\n\r\n";
echo TestModel::where("CHAR_LENGTH(title)=:title and (id>10 or id<20)")
	->left_Join("t", UserModel::CLASS_NAME, "u", "t.user_id=u.id")
	->getSingle(array(),"t");
echo "\r\n\r\n";
echo TestModel::where("CHAR_LENGTH(title)=:title and (id>10 or id<20)")
	->left_Join("t", UserModel::CLASS_NAME, "u", "t.user_id=u.id")
	->getSingle(array(),"u");
echo "\r\n\r\n";
echo TestModel::where("CHAR_LENGTH(title)=:title and (id>10 or id<20)")
	->left_Join("t", UserModel::CLASS_NAME, "u", "t.user_id=u.id")
	->limit(5)
	->select(array(),"u");
echo "\r\n\r\n";
echo TestModel::where("CHAR_LENGTH(title)=:title and (id>10 or id<20)")
	->left_Join("t", UserModel::CLASS_NAME, "u", "t.user_id=u.id")
	->limit(5)->group_by(TestModel::TITLE,"t")
	->select(array(),"u");
echo "\r\n\r\n";
echo TestModel::where("CHAR_LENGTH(title)=:title and (id>10 or id<20)")
	->left_Join("t", UserModel::CLASS_NAME, "u", "t.user_id=u.id")
	->limit(5)->order_by(TestModel::TITLE,"asc","t")
	->select(array(),"u");
echo "\r\n\r\n";
echo TestModel::where("CHAR_LENGTH(title)=:title and (id>10 or id<20)")
	->left_Join("t", UserModel::CLASS_NAME, "u", "t.user_id=u.id")
	->limit(5)->order_by(TestModel::TITLE,"asc","u")->group_by(TestModel::TITLE,"u")
	->selectSQL(array(),"u");
echo "\r\n\r\n";
echo TestModel::where("CHAR_LENGTH(title)=:title and (id>10 or id<20)")
	->left_Join("t", UserModel::CLASS_NAME, "u", "t.user_id=u.id")
	->Join("t", OrderModel::CLASS_NAME, "o", "o.user_id=u.id")
	->limit(5)->order_by(TestModel::TITLE,"asc","u")->group_by(TestModel::TITLE,"u")
	->select(array(),"o");

#调用方法5
// TestModel::where(TestModel::TITLE,"title")->leftJoin("t", UserModel::CLASS_NAME, "u", "t.user_id=u.id")->limit(5)->select();
// TestModel::where(TestModel::TITLE,"title")->leftJoin("t", UserModel::CLASS_NAME, "u", "t.user_id=u.id")->limit(5)->select();

?>
--EXPECT--
单表查询:
SELECT m.id AS m_id,m.title AS m_title,m.user_id AS m_user_id,m.created_on AS m_created_on,m.modified_on AS m_modified_on 
FROM tests AS m 
WHERE CHAR_LENGTH(title)=:title and (id>10 or id<20) LIMIT 1

SELECT t.id AS t_id,t.title AS t_title,t.user_id AS t_user_id,t.created_on AS t_created_on,t.modified_on AS t_modified_on 
FROM tests AS t  LEFT JOIN users AS u ON t.user_id=u.id 
WHERE CHAR_LENGTH(title)=:title and (id>10 or id<20) LIMIT 1

SELECT u.id AS u_id,u.title AS u_title,u.created_on AS u_created_on,u.modified_on AS u_modified_on 
FROM tests AS t  LEFT JOIN users AS u ON t.user_id=u.id 
WHERE CHAR_LENGTH(title)=:title and (id>10 or id<20) LIMIT 1

SELECT u.id AS u_id,u.title AS u_title,u.created_on AS u_created_on,u.modified_on AS u_modified_on 
FROM tests AS t  LEFT JOIN users AS u ON t.user_id=u.id 
WHERE CHAR_LENGTH(title)=:title and (id>10 or id<20) LIMIT 5

SELECT u.id AS u_id,u.title AS u_title,u.created_on AS u_created_on,u.modified_on AS u_modified_on 
FROM tests AS t  LEFT JOIN users AS u ON t.user_id=u.id 
WHERE CHAR_LENGTH(title)=:title and (id>10 or id<20) GROUP BY t.title LIMIT 5

SELECT u.id AS u_id,u.title AS u_title,u.created_on AS u_created_on,u.modified_on AS u_modified_on 
FROM tests AS t  LEFT JOIN users AS u ON t.user_id=u.id 
WHERE CHAR_LENGTH(title)=:title and (id>10 or id<20) ORDER BY t.title ASC LIMIT 5

SELECT u.id AS u_id,u.title AS u_title,u.created_on AS u_created_on,u.modified_on AS u_modified_on 
FROM tests AS t  LEFT JOIN users AS u ON t.user_id=u.id 
WHERE CHAR_LENGTH(title)=:title and (id>10 or id<20) GROUP BY u.title ORDER BY u.title ASC LIMIT 5

SELECT o.id AS o_id,o.order_id AS o_order_id,o.user_id AS o_user_id,o.created_on AS o_created_on,o.modified_on AS o_modified_on 
FROM tests AS t  LEFT JOIN users AS u ON t.user_id=u.id  INNER JOIN orders AS o ON o.user_id=u.id 
WHERE CHAR_LENGTH(title)=:title and (id>10 or id<20) GROUP BY u.title ORDER BY u.title ASC LIMIT 5