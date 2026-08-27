<?php
namespace yangzie;

/**
 * 通过后缀名判断给定的文件是不是图片
 *
 * @param string $file 文件名或文件路径
 * @return bool 是图片返回 true，否则返回 false
 */
function yze_isimage($file){
	$type = array("png","gif","jpeg","jpg","bmp","ico","svg","webp");
	return in_array(strtolower(pathinfo($file,PATHINFO_EXTENSION) ?: $file), $type);
}

/**
 * 将路径格式化为绝对路径，去除 . 和 .. 部分
 *
 * this/is/../a/./test/.///is 格式化成 this/a/test/is，但要注意不能有 stream wrapper（如 http:// phar:// 等），// 会被处理掉
 *
 * @param string $path 需要格式化的路径
 * @param string $in   前置路径，拼接在 $path 之前
 * @return string 格式化后的绝对路径
 */
function yze_get_abs_path($path, $in=''){
    $path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $in."/".ltrim($path, "/"));
    $leftHasSperator = substr($path, 0, 1) == '/' || substr($path, 0) == '\\';
    $parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');
    $absolutes = array();
    foreach ($parts as $part) {
        if ('.' == $part) continue;
        if ('..' == $part) {
            array_pop($absolutes);
        } else {
            $absolutes[] = $part;
        }
    }
    return ($leftHasSperator ? DIRECTORY_SEPARATOR : '').implode(DIRECTORY_SEPARATOR, $absolutes);
}

/**
 * 从路径 path 中删除 need_remove 部分
 *
 * @param string $path        原始路径
 * @param string $need_remove 需要从路径中删除的子串
 * @return string 删除后的路径
 */
function yze_remove_path($path, $need_remove){
	$path = strtr($path, array(DS=>"/"));
    $need_remove =  strtr($need_remove, array(DS=>"/"));
	return strtr($path, array($need_remove=>''));
}

/**
 * 把文件移到指定目录中去，并返回移动成功后的目标文件路径，移动失败则返回 false
 *
 * @param string $src_file 源文件绝对路径
 * @param string $dist_dir 目标目录绝对路径
 * @return string|false 移动成功返回目标文件路径，失败返回 false
 */
function yze_move_file($src_file, $dist_dir){
	$dist_file = yze_copy_file($src_file, $dist_dir);
	if($dist_file){
		@unlink($src_file);
		return $dist_file;
	}else{
		return false;
	}
}

/**
 * 把 src_file 拷贝到 dist_dir 中去，并返回拷贝成功的文件路径，如果拷贝失败返回 false
 * dist_dir 不存在则自动创建
 *
 * @author leeboo
 *
 * @param string $src_file 源文件绝对路径
 * @param string $dist_dir 目标目录绝对路径
 * @return string|false 拷贝成功返回目标文件路径，失败返回 false
 */
function yze_copy_file($src_file, $dist_dir){
	if (!$dist_dir){
		return false;
	}

	yze_make_dirs($dist_dir);

	$dist_file = rtrim($dist_dir,DS).DS.basename($src_file);
	return copy($src_file,$dist_file) ? $dist_file : false ;
}

/**
 * 拷贝目录及其下所有子目录文件到指定目录
 *
 * @param string $srcDir  源目录绝对路径
 * @param string $destDir 目标目录绝对路径
 * @return bool 拷贝成功返回 true，失败返回 false
 */
function yze_copy_dir($srcDir, $destDir) {
    if ( ! file_exists($destDir) ) {
        if ( ! mkdir($destDir, 0777, true) ) {
            return false;
        }
    }
    $dir_handle = opendir($srcDir);
    while ( false !== ( $file = readdir($dir_handle)) ) {
        if (( $file != '.' ) && ( $file != '..' )) {

            if ( is_dir($srcDir . DS . $file) ) {
                yze_copy_dir($srcDir . DS . $file, $destDir . DS . $file);
            } else {
                if( ! copy($srcDir . DS . $file, $destDir . DS . $file)){
                    closedir($dir_handle);
                    return false;
                }
            }
        }
    }
    closedir($dir_handle);

    return true;
}

/**
 * 根据传入的目录路径递归创建目录，目录已存在则不做处理
 *
 * @param string $dirs 需要创建的目录绝对路径
 * @return void
 */
function yze_make_dirs($dirs){
	if (file_exists($dirs))return;
    $dir = '';
	foreach (explode(DS,strtr(rtrim($dirs,DS),array("/"=>DS))) as $d){
		// ai@2026-05-27 替换 @ 抑制符，使用 ?? '' 显式处理
		$dir = ($dir ?? '') . $d . DS;
		@mkdir($dir,0777);
	}
}
