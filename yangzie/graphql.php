<?php

namespace yangzie;

class GraphqlField {
    public $name;
    public $vars;
    public $sub;
}

/**
 * Graphql处理控制器
 *
 * @category Framework
 * @package Yangzie
 * @author liizii, <libol007@gmail.com>
 * @license http://www.php.net/license/3_01.txt PHP License 3.01
 * @link yangzie.yidianhulian.com
 */
class Graphql_Controller extends YZE_Resource_Controller {
    public function post_index() {
        return $this->index();
    }
    public function index() {
        $this->layout = '';
        try{
            $nodes = $this->parse();
            $datas = $this->query($nodes);
            return YZE_JSON_View::success($this, $datas);
        }catch (\Exception $e){
            return YZE_JSON_View::error($this, $e->getMessage());
        }
    }

    private function fetchRequest() {
        $request = $this->request;

        if (strcmp(@$_SERVER['CONTENT_TYPE'], 'application/json') === 0 ){
            $content = json_decode(trim(file_get_contents("php://input")), true);
            return [@$content['query'],@$content['variables'],@$content['operationName']];
        }

        return[
            trim($request->get_from_get('query')),
            trim($request->get_from_get('variables')),
            trim($request->get_from_get('operationName'))
        ];
        return [$query, $vars, $operationName];
    }

    /**
     * 解析请求并对field做验证，如果有错误抛出异常
     * @throws YZE_FatalException
     */
    private function parse(){
        $request = $this->request;
        list($query, $vars, $operationName) = $this->fetchRequest();

        preg_match_all("/:|\{|\}|\(|\)|\w+|\.|\\$|\"|\#[^\\n]*/miu", $query, $matches);
        print_r($matches);
        // 所有节点树
        $nodes = [];
        return $nodes;
    }

    /**
     * 解析并返回查询结果，对field做验证，如果有错误抛出异常
     *
     * @throws YZE_FatalException
     */
    private function query($nodes = []) {
        return $nodes;
    }
}

?>
