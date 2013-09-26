<?php 
/**
 * 取得保存文件的访问url
 * 
 * @author leeboo
 * 
 * @param unknown $path
 * @return string
 * 
 * @return
 */
function get_file_url($path){
	if(defined("YZE_DEVELOP_MODE") && YZE_DEVELOP_MODE){
		return SITE_URI."/upload/".\yangzie\yze_remove_abs_path($path, YZE_UPLOAD_PATH);
	}
	$path = ltrim($path, "saestor://");
	if( !$path)return "#";
	$stor = new SaeStorage();
	$first_slash = stripos($path, "/");
	$domain = substr($path, 0, $first_slash);
	$file_path = substr($path, $first_slash+1);
	return $stor->getUrl ($domain, $file_path);
}
?>