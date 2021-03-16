<?php

namespace yangzie;

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
    private $operationType = 'query';
    private $operationName;
    private $fetchActRegx = "/\{|\}|\(.+\)|\w+|\.{1,3}|\\$|\#[^\\n]*/miu";
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
        preg_match_all($this->fetchActRegx, $query, $matches);
        //处理query 或者 mutation name
        $acts = $matches[0];
        if (!strcasecmp('query', $acts[0]) || !strcasecmp('mutation', $acts[0])){
            $this->operationType = $acts[0];
            if ($acts[1]!="{"){
                $this->operationName = $acts[1];
                return $this->fetchNode(array_slice($acts, 3));
            }
            return $this->fetchNode(array_slice($acts, 2));
        }

        return $this->fetchNode(array_slice($acts, 1));
    }
    private function fetchNode ($acts, &$fetchedLength=0) {
        $nodes = [];
        $currNode = [];
        $index = 0;
        while (true){
            if ($index==count($acts)-1) {
                $fetchedLength = $index;
                if ($currNode) $nodes[] = $currNode;
                return $nodes;
            }
            $act = $acts[$index++];

            if ($act=="}") {
                $fetchedLength = $index;
                if ($currNode) $nodes[] = $currNode;
                return $nodes;
            }

            if ($act == "{"){
                $subLength = 0;
                @$currNode['sub'] = $this->fetchNode(array_slice($acts, $index), $subLength);
                $index += $subLength;
                $nodes[] = $currNode;
                $currNode = [];
                continue;
            }
            if ($act[0] == "("){ //参数处理
                @$currNode['args'] = $this->fetchArgs($act);
                continue;
            }

            if (@$currNode['name']){
                $nodes[] = $currNode;
                $currNode = [];
            }
            @$currNode['name'] = $act;
        }
        return $nodes;
    }

    /**
     * @param $argString
     * @return array
     */
    private function fetchArgs ($argString) {
        $ignoredBracket = mb_substr($argString, 1, mb_strlen($argString)-1);
        preg_match_all("/\w+|,|\"|'|\\\\|:|\(|\)|\{|\}/miu", $ignoredBracket, $quoteMatches);
        $acts = [];
        $isQuoting = false;
        $quoteString = [];
        $args = [];

        // 上面的正则解析出来的数据比较细，把解析出来的参数字符串在重新按照name:v的格式梳理一遍
        // 测试字符串：(id: "\"{(1000),", a:"(2')", c:1)
        foreach ($quoteMatches[0] as $index => $act){
            // 单词 : ,
           if (!$isQuoting && (preg_match("/\w+/miu",$act) || $act == ":" || $act == ",")) {
               $acts[] = $act;
               continue;
           }
            // 引号处理
            if ($act=='"' && $quoteMatches[0][$index-1]!='\\'){
                if (!$isQuoting){
                    $isQuoting = true;
                    $quoteString[] = $act;
                    continue;
                }
                $isQuoting = false;
                $quoteString[] = $act;
                $acts[] = join('', $quoteString);
                $quoteString = [];
                continue;
            }
            $quoteString[] = $act;
        }
        $currArg = [];
        foreach ($acts as $act) {
            if (!@$currArg['name']){
                $currArg['name'] = $act;
                continue;
            }
            if ($act == ":")continue;
            if ($act == ","){
                $args[] = $currArg;
                $currArg = [];
            }
            @$currArg['default'] = $act;
        }
        if ($currArg) {
            $args[] = $currArg;
        }
        return $args;
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
