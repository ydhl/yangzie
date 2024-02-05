<?php
namespace app\vendor;

use app\asset\Asset_Model;
use app\cms\Notify_Model;
use app\order\Order_Model;
use yangzie\YZE_FatalException;
use yangzie\YZE_Hook;
use yangzie\YZE_Model;
use yangzie\YZE_Request;

/**
 * Model查询基类，通过继承该类实现多表复杂的查询; 只支持Model Search语法
 *
 * @package app\vendor
 */
abstract class Search_Model_Helper
{
    /**
     * 当前第几页
     * @var int
     */
    protected $page;
    /**
     * 每页的条数
     * @var int
     */
    protected $pageCount;

    public function set_page($page){
        $this->page = intval($page);
        return $this;
    }

    public function set_page_count($pageCount){
        $this->pageCount = intval($pageCount);
        return $this;
    }
    public function get_page(){
        return $this->page <=0 ? 1 : $this->page;
    }

    public function get_page_count(){
        return $this->pageCount <=0 ? 20 : $this->pageCount;
    }

    /**
     * 返回要查询的model对象，比如：
     * <ul>
     * <li>return Foo_Model::from("o")</li>
     * <li>return Foo_Model::from("o")->left_join(Bar_Model::CLASS_NAME, 'bar', 'bar.oid=o.id')</li>
     * </ul>
     * @return YZE_Model
     */
    protected abstract function build_search_model(): YZE_Model;

    /**
     * 构建where对象，查询参数和count的的字段
     *
     * <strong style="color:red">分页条件不要在返回的where中构建，Search_Model_Helper会进行处理</strong>
     *
     * @param $params
     * @param $countColumn
     * @return string
     */
    protected abstract function build_where_params(&$params=[], &$countColumn="id"): string;

    /**
     * 做查询并返回结果数组和总数
     * @param $total 返回满足条件的结果总数
     * @return array
     */
    public function search(&$total){
        $params = [];
        $where = $this->build_where_params($params, $countColumn);
        $searchModel = $this->build_search_model();
        $searchModel = $searchModel->where($where);
        $total = $searchModel ->count($countColumn, $params);
        $where .= " limit " . ($this->get_page()-1) * $this->get_page_count() . ", " . $this->get_page_count();
        return $searchModel->clean_where()->where($where)->select($params);
    }
}
