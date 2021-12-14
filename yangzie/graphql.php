<?php

namespace yangzie;

class GraphqlResult extends YZE_JSON_View{
    public static function error($controller, $message =null, $code =null, $data=null) {
        return new GraphqlResult($controller,  array (
            'errors' => [$message],
            "data" => $data
        ) );
    }
    public static function success($controller, $data = null) {
        return new GraphqlResult($controller,  array (
            "data" => $data
        ) );
    }
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
    use Graphql_Schema;
    private $operationType = 'query';
    private $operationName;
    private $fetchActRegx = "/:|\{|\}|\(.+\)|\w+|\.{1,3}|\\$|\#[^\\n]*/miu";
    public function response_headers(){
        return [
            "Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization, token, Redirect",
            "Access-Control-Allow-Methods: GET, POST, PUT,DELETE,OPTIONS,PATCH",
            "Access-Control-Allow-Origin: *"
            ];
    }
    public function post_index() {
        return $this->index();
    }
    public function index() {
        $this->layout = '';
        try{
            // 1. 线解析graphql成语法结构体
            $nodes = $this->parse();
            $result = [];
            // 2. 对每个结构进行数据查询
            foreach ($nodes as $node) {
                //2.1 特殊端查询：向服务端询问有哪些可查询端内容 https://graphql.cn/learn/introspection/
                if (strtoupper($node['name']) == '__SCHEMA'){
                    $schemeResult = [];
                    foreach ($node['sub'] as $schemaNode){
                        $schemaName = strtoupper($schemaNode['name']);
                        switch ($schemaName){
                            case 'QUERYTYPE': $schemeResult[$schemaNode['name']] = $this->schemaQueryType($schemaNode);break;
                            case 'SUBSCRIPTIONTYPE': $schemeResult[$schemaNode['name']] = $this->schemaSubscriptionType($schemaNode);break;
                            case 'MUTATIONTYPE': $schemeResult[$schemaNode['name']] = $this->schemaMutationType($schemaNode);break;
                            case 'TYPES': $schemeResult[$schemaNode['name']] = $this->schemaTypes($schemaNode);break;
                            case 'DIRECTIVES': $schemeResult[$schemaNode['name']] = $this->schemaDirectives($schemaNode);break;
                        }
                    }
                    $result['__schema'] = $schemeResult;
                }else{
                    // 2.2 具体数据查询
                    $result[$node['name']] = $this->query($node);
                }
            }
            // 3. 返回结构
            return GraphqlResult::success($this, $result);
        }catch (\Exception $e){
            return GraphqlResult::error($this, $e->getMessage());
        }
    }

    /**
     * 根据请求端方法（post/get）已经传参端方式，获取请求中端数据
     * @return array [0=>查询字符串, 1=>变量字符串, 2=>操作名称字符串]
     */
    private function fetchRequest() {
        $request = $this->request;

        if (strcmp(@$_SERVER['CONTENT_TYPE'], 'application/json') === 0 ){
            $content = json_decode(trim(file_get_contents("php://input")), true);
            return [@$content['query'],@$content['variables'],@$content['operationName']];
        }

        return[
            trim($request->get_from_request('query')),
            trim($request->get_from_request('variables')),
            trim($request->get_from_request('operationName'))
        ];
    }

    /**
     * 解析请求并对field做验证，如果有错误抛出异常。
     * query IntrospectionQuery { __schema { queryType { name } } }返回的结构体格式如下：
     * <pre>
     * [
     *  0=>[
     *   'name'=>'__schema',
     *   'sub'=>[
     *      ... 下面的结构体
     *   ]
     *  ]
     * ]
     * </pre>
     * 每个结构体的格式如下：[name=>名称, sub=>[下面的结构体], args=>[参数结构体]]；
     * 参数结构体的格式如下：[name=>参数名, default=>默认值]
     * @throws YZE_FatalException
     */
    private function parse(){
        $request = $this->request;
        list($query, $vars, $operationName) = $this->fetchRequest();
        //用正则来分离query里面的结构
        preg_match_all($this->fetchActRegx, $query, $matches);
        //处理query 或者 mutation name
        $acts = $matches[0];
        if (!$acts){
            throw new YZE_FatalException('query is missing');
        }
        // query {, query operationName {, mutation {, mutation operationName {的情况
        if (!strcasecmp('query', $acts[0]) || !strcasecmp('mutation', $acts[0])){
            $this->operationType = $acts[0];
            if ($acts[1]!="{"){
                $this->operationName = $acts[1];
                return $this->fetchNode(array_slice($acts, 3));
            }
            return $this->fetchNode(array_slice($acts, 2));
        }

        // 直接{开头的情况
        return $this->fetchNode(array_slice($acts, 1));
    }

    /**
     * 提取指定的fragment
     * @param $acts
     */
    private function fetchFragment($acts, $fragmentName) {
        $fragmentIndex = -1;
        foreach ($acts as $index => $act) {
            if (!strcasecmp('fragment', $acts[$index]) && !strcasecmp($acts[$index+1], $fragmentName)){
                $fragmentIndex = $index;
                break;
            }
        }
        if ($fragmentIndex==-1) return [];

        while (true) {
            if (!strcasecmp('{', $acts[$fragmentIndex])){
                break;
            }
            $fragmentIndex++;
        }
        return $this->fetchNode(array_slice($acts, $fragmentIndex + 1));
    }

    /**
     * 遍历提取的关键字，然后解析出节点，传入的数据中不需要头的{
     * @param $acts
     * @param int $fetchedLength
     * @return array
     */
    private function fetchNode ($acts, &$fetchedLength=0) {
        $nodes = [];
        $currNode = [];
        $index = 0;
        if (!$acts) return $nodes;
        while (true){
            // 解析完了
            if ($index==count($acts)-1) {
                $fetchedLength = $index;
                if ($currNode) $nodes[] = $currNode;
                return $nodes;
            }
            $act = $acts[$index++];

            // 遇到}表示当前节点节点解析完了
            if ($act=="}") {
                $fetchedLength = $index;
                if ($currNode) $nodes[] = $currNode;
                return $nodes;
            }

            // 开始解析新节点
            if ($act == "{"){
                $subLength = 0;
                @$currNode['sub'] = $this->fetchNode(array_slice($acts, $index), $subLength);
                $index += $subLength;
                $nodes[] = $currNode;
                $currNode = [];
                continue;
            }
            //参数处理
            if ($act[0] == "("){
                @$currNode['args'] = $this->fetchArgs($act);
                continue;
            }
            // ：别名处理,:后面是别名，index往后移动一位
            if ($act == ":"){
                @$currNode['alias'] = $acts[$index++];
                continue;
            }
            // fragment 处理，后面是fragment，index移动一位
            if ($act == "..."){
                $nodes = array_merge($nodes, $this->fetchFragment($acts, $acts[$index++]));
                $currNode = [];
                continue;
            }
            // 正常节点名称
            if (@$currNode['name']){
                $nodes[] = $currNode;
                $currNode = [];
            }
            @$currNode['name'] = $act;
        }
        return $nodes;
    }

    /**
     * 提取查询字符串中的参数部分
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
     * 查询具体的node值
     * @param $node [name=>'', sub=>[]]
     */
    private function queryField($node) {

        return $node['name'];
    }
    /**
     * 解析并返回查询结果，对field做验证，如果有错误抛出异常
     * @param $nodes [[name=>'', sub=>[]], [name=>'', sub=>[]]]
     * @throws YZE_FatalException
     */
    private function query($node = []) {

        if (@$node['sub']){
            $result = [];
            foreach ($node['sub'] as $sub) {
                $result[$sub['name']] = $this->query($sub);
            }
            return $result;
        }else{
            return $this->queryField($node);
        }
    }
}

?>
