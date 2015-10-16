<?php
namespace app\vendor;
use app\test\User_Model;
use yangzie\YZE_Simple_View;
/**
 * 查询助手，配合YDForm,YDTable使用可简化构建查询及数据显示
 * @author leeboo
 *
 */
class YDSearch{
    public function table($tableClass, $alias){
        return $this;
    }
    public function column($alias, $columns){
        return $this;
    }
    public function on($on){
        return $this;
    }
    public function doSearch(){
        
    }
}

class YDForm{
    
}

//查询助手
$search = new YDSearch();
$search->table('\app\test\User_Model',"u")
       ->table('\app\test\Order_Model',"o")
       ->column("u", "id,name:姓名,nickname:昵称")
       ->column("o","order_id,product_name:产品名")
       ->on("u.id=o.user_id")
       ->getResults($page);//查询当页记录
$search->getTotalResults();//满足条件的总记录数
$search->getTotalPages($countResultOfPage);//总的页数
$search->getPaginationPages($pages);//分页记录，返回数组{first,prev,x,x,x,x,x,next,end}

//ydform 用法1：随意制定
$ydform = new YDForm();
$ydform->setItem("name:姓名,nickname:昵称,product_name:产品,order_id")
    ->setView("name", YZE_APP_VIEWS_INC."custom-select", $data)
    ->setView("product_name","file")
    ->setView("nickname","textarea")
    ->setView("order_id","checkbox", array("value"=>"显示想名字"))
    ->setItemLayout('<div class="form-group"><label for="{itemid}">{name}</label>{html}</div>');
$ydform->output();

//ydform 用法2：与YZE_Model绑定
$ydform = new YDForm();
$ydform->setModel('\app\test\User_Model', /*排除在外,不生成表单的*/"created_on,id")
    ->setView("name", YZE_APP_VIEWS_INC."custom-select", $data)
    /*根据model 自动生成view*/
    ->setItemLayout('<tr><td>{name}</td><td>{html}</td></tr>');
$ydform->output();

// ydtable 用法1
$ydtable = new YDTable();
$ydtable->search($search)/*显示查询对象中的column*/
    ->output();

$ydtable->output();

// ydtable 用法2
$ydtable = new YDTable();
$ydtable->search($search)/*显示查询对象中的column*/
        ->setCol("u.id,o.order_id,name,nickname")
        ->setColLayout("<td><input id='{itemid}' type='checkbox' name='{colname}' value='{colvalue}'/>{html}</td>","id")
        ->setHeaderLayout("<th>{html}<a href='./orderby={colname}&sort={sort}'>排序</a></th>","id");

$ydtable->output();