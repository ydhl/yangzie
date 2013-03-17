<?php
class YZE_Form extends YZE_Object{
	private $form_name;
	private $model;
	private $method = "post";
	private $acl;
	private $view;


	public function __construct(YZE_View_Adapter $view,$form_name,YZE_Model $model=null){
		$this->form_name = $form_name;
		$this->model = $model;
		$this->view = $view;
		$this->acl = YZE_ACL::get_instance();
	}

	public function begin_form(array $attrs=array(),$is_delete=false){
		ob_start();
		$name = $this->form_name;
		$model = $this->model;
		$html = $modify = '';
		foreach ($attrs as $n=>$value){
			$html .= "$n = '$value' ";
		}
		$token = YZE_Session::get_instance()->get_request_token(YZE_Request::get_instance()->the_uri());
		if($model){
			$modify = "<input type='hidden' name='yze_modify_version' value='".$model->get_version_value()."'/>
					<input type='hidden' name='yze_model_id' value='".$model->get_key()."'/>
							<input type='hidden' name='yze_model_name' value='".get_class($model)."'/>
									<input type='hidden' name='yze_module_name' value='".$model->get_module_name()."'/>
											<input type='hidden' name='yze_method' value='".($is_delete ? "delete" : "put")."'/>";
		}
		echo "<form name='$name' method='{$this->method}' $html>
		<input type='hidden' name='yze_request_token' value='{$token}'/>
		$modify";
	}
	public function end_form(){
		echo '</form>';
		$form = ob_get_clean();
		$app_auth = new  App_Auth();
		$aroname = $app_auth->get_request_aro_name();
		if($this->acl->check_byname($aroname, $this->form_name)){
			echo $form;
		}
	}
}

function yze_render_link($action, array $args, $anchor=null){
	$path = http_build_query($args).($anchor ? "#{$anchor}" : "");
	$action = trim($action, "/");
	
	switch (YZE_REWRITE_MODE){
		case YZE_REWRITE_MODE_REWRITE: 	return "/".$action."?".trim($path, "/");
		case YZE_REWRITE_MODE_PATH_INFO: 	return "index.php/$action?".$path; 
		case YZE_REWRITE_MODE_NONE:	
		default: 	
			return $_SERVER["SERVER_NAME"]."?yze_action=/{$action}&".$path;
	}
}

/**
 *  取得一个对象的默认值，如果name有缓存（表单提交失败）取缓存的值；如果对象存在
 *  取对象的值，其它返回空。uri为空表示当前请求uri
 * 
 * @author leeboo
 * 
 * @param unknown $object
 * @param unknown $name
 * @param string $uri
 * @return string
 * 
 * @return
 */
function yze_get_default_value($object, $name, $uri=null)
{
	if (YZE_Session::post_cache_has($name, $uri)){
		return YZE_Session::get_cached_post($name, $uri);
	}
	if ($object){
		return $object->get($name);
	}
	return "";
}

/**
 * 返回当前uri的表单最后一次提交出错的出错信息
 * 
 * @author leeboo
 * 
 * @return string
 * 
 * @return
 */
function yze_get_post_error()
{
	$session = YZE_Session::get_instance();
	$uri = YZE_Request::get_instance()->the_uri();
	if ($session->has_exception($uri)) {
		return nl2br($session->get_uri_exception($uri)->getMessage());
	}
}

/**
 * 把传入的文件压缩成一个文件后返回该文件的uri，比如把所有的css文件压缩成一个；
 * js文件压缩成一个。该api会考虑缓存，如果所传入的文件没有变化，则直接返回之前压缩的文件
 * 压缩的文件存放在YZE_APP_CACHES_PATH / compressed 中， 缓存文件的命名及内容依赖于传入的文件
 * 顺序。
 * 
 * 该api参数是可变参数，传入每个文件的操作系统绝对路径。 
 * 调用方法 yze_output_compressed_file("/path/to/file/one.css", "/path/to/file/two.css");
 * 
 * 
 * @author leeboo
 * 
 * 
 * @return string 压缩文件的uri
 */
function yze_compress_file(){
	$num_args = func_num_args();
	if(!$num_args)return;
	
	$cache_name = ""; $version=""; $cache_content = "";
	yze_make_dirs(YZE_APP_CACHES_PATH."compressed");
	
	for ($i=0; $i<$num_args; $i++){
		$file_name 		= func_get_arg($i);
		if ( ! is_file($file_name)) continue;
		
		$cache_name 	.= $file_name;
		$version 		.= filemtime($file_name);
	}
	if(!$cache_name)return;
	
	$ext = pathinfo($file_name, PATHINFO_EXTENSION);
	$cache_name = YZE_APP_CACHES_PATH . "compressed/" . md5($cache_name) . "-" . md5($version) . "." . $ext;
	
	if(yze_isfile($cache_name)) return yze_remove_abs_path($cache_name);//not changed
	
	for ($i=0; $i<$num_args; $i++){
		$cache_content .= file_get_contents(func_get_arg($i))."\n";
	}
	
	//删除之前的缓存文件，如果有的话
	foreach (glob(YZE_APP_CACHES_PATH . "compressed/" . md5($cache_name) . "-*." . $ext) as $old){
		@unlink(YZE_APP_CACHES_PATH . "compressed/".$old);
	}
	
	file_put_contents($cache_name, $cache_content);
	return yze_remove_abs_path($cache_name);
}
?>