<?php
namespace yangzie;
/**
 * HTTP的缓存接口
 * @author liizii
 *
 */
interface YZE_Cacheable{
	public function set_cache_config(YZE_HttpCache $cache);
}

class YZE_HttpCache extends YZE_Object{
	private $last_modified;
	private $etag;
	private $expires;
	private $max_age;
	
	public function last_modified(){
		if(func_num_args()>=1){
			$this->last_modified = YZE_Request::format_gmdate(func_get_arg(0));
			return $this;
		}
		return $this->last_modified;
	}
	/**
	 * HTTP/1.1 支持的内容协商方法
	 * @param unknown_type $etag
	 */	
	public function etag(){
		if(func_num_args()>=1){
			$this->etag = func_get_arg(0);
			return $this;
		}
		return $this->etag;
	}
	
	public function expires(){
		if(func_num_args()>=1){
			$this->expires = YZE_Request::format_gmdate(func_get_arg(0));
			return $this;
		}
		return $this->expires;
	}
	/**
	 * Cache-Control: max-age=$maxage 优先级大于expires
	 * @param unknown_type $maxage
	 */
	public function max_age(){
		if(func_num_args()>=1){
			$this->max_age = func_get_arg(0);
			return $this;
		}
		return $this->max_age;
	}
	
	public function output(){
//		if($this->last_modified){
			header("Last-Modified: ".$this->last_modified);
//		}
		if($this->etag){
			header("ETag: ".$this->etag);
		}
//		if($this->expires){
			header("Expires: ".$this->expires);
//		}
		header("Cache-Control: ");
		header("Pragma: ");
		if($this->max_age){
			header("Cache-Control: max-age=".$this->max_age);
		}
	}
}
?>