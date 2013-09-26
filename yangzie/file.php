<?php
namespace yangzie;
/**
 * 判断一个相同文件是否真的存在
 * 
 * @author leeboo
 * 
 * @param unknown $relative_file
 * @return boolean
 * 
 * @return
 */
function yze_isfile($relative_file){
	return is_file(yze_get_abs_path($relative_file));
}

function yze_isimage($file){
	$type = array("png","gif","jpeg","jpg","bmp",".ico");
	return in_array(strtolower(pathinfo($file,PATHINFO_EXTENSION)), $type);
}

function yze_get_abs_path($path, $in){
	return $in.strtr(ltrim($path, "/"), array("/"=>DS));
}

function yze_remove_abs_path($path, $in){
	$path = strtr($path, array(DS=>"/"));
	$in =  strtr($in, array(DS=>"/"));
	return "/".ltrim(strtr($path, array($in=>'')),"/");
}

/**
 *
 * 把文件移到指定目录中去, 并返回移动成功后的目标文件路径，移动失败则返回原文件
 *
 * @param unknown_type $src_file 绝对路径
 * @param unknown_type $dist_dir 绝对路径
 */
function yze_move_file($src_file, $dist_dir){
	$dist_file = yze_copy_file($src_file, $dist_dir);
	if($dist_file != $src_file){
		@unlink($src_file);
		return $dist_file;
	}else{
		return $src_file;
	}
}

/**
 * 把src_file 拷贝到 dist_dir 中去, 并返回拷贝成功的一文件路径，如果拷贝失败返回src_file
 * dist_dir不存在则创建
 * 
 * @author leeboo
 * 
 * @param unknown $src_file
 * @param unknown $dist_dir
 * @return unknown|string
 * 
 * @return
 */
function yze_copy_file($src_file, $dist_dir){
	if (!$dist_dir){
		return $src_file;
	}
	
	yze_make_dirs($dist_dir);

	$dist_file = rtrim($dir,DS).DS.basename($src_file);
	return copy($src_file,$dist_file) ? $dist_file : $src_file ;
}

/**
 *  根据传入的目录路径创建它们, 目录存在不做处理
 * 
 * @param unknown_type $dirs 绝对地址
 */
function yze_make_dirs($dirs){
	if (file_exists($dirs))return;
	
	foreach (explode(DS,strtr(rtrim($dirs,DS),array("/"=>DS))) as $d){
		$dir = @$dir.$d.DS;
		@mkdir($dir,0777);
	}
}