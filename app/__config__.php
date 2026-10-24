<?php
namespace  app;

use yangzie\YZE_Exception;
use yangzie\YZE_FatalException;
use function yangzie\yze_js_bundle;

/**
 * 指定上传目录
 */
define("YZE_UPLOAD_PATH", YZE_APP_PATH. "public_html".DS."upload".DS);

/**
 * 网站地址
 */
define("SITE_URI", "http://YOUR-DOMAIN/");
/**
 * 上传内容的访问地址，如果有cdn，填写cdn地址
 */
define("UPLOAD_SITE_URI", "http://YOR-DOMIAN/upload/");

/**
 * 开发环境true还是生产环境（false）
 */
define("YZE_DEVELOP_MODE",  true );
/**
 * 错误报告级别
 */
ini_set('error_reporting', E_ALL & ~E_STRICT & ~E_DEPRECATED);
/**
 * 时区
 */
date_default_timezone_set('Asia/Chongqing');
/**
 * 应用名
 */
define("YZE_APP_NAME", "Yangzie");
/**
 * 是否是session less应用，session less将不开启session功能
 */
define("SESSIONLESS", false);


/**
 * 返回应用级的配置
 * @author leeboo
 *
 */
class App_Module extends \yangzie\YZE_Base_Module{

	/**
	 * App 访问时做一些检查，比如php的版本；如果有不满足的条件则抛出异常
	 * @return void
	 * @throws YZE_FatalException
	 */
	public function check(){
		if( version_compare(PHP_VERSION,'8.0.0','lt')){
			throw new YZE_FatalException("要求8.0以上PHP版本");
		}
		// ai@2026-09-13 补充 PHP 扩展检查：mbstring（dba/model/graphql/pomo 使用 mb_* 函数）、
		// pdo_mysql（YZE_DBAImpl 的 DSN 写死 mysql）、Phar/zlib/openssl（模块 phar 的打包与 phar:// 加载）；
		// SESSIONLESS 为 false 时 init.php 会调用 session_start()，故还需要 session。
		// json 自 PHP 8.0 起为内置扩展不可禁用，无需检查；mysqli 只有 CLI 生成 model 时用到，不属于应用运行必需。
		$required_extensions = array(
			"mbstring"  => "框架的字符处理（dba/model/graphql/pomo 的 mb_* 函数）",
			"pdo_mysql" => "数据库访问（YZE_DBAImpl 使用 PDO 的 mysql DSN）",
			"Phar"      => "模块 phar 的打包与 phar:// 模块加载",
			"zlib"      => "phar 打包时使用 Phar::GZ 压缩",
			"openssl"   => "phar 的 OpenSSL 签名与校验",
		);
		if ( ! SESSIONLESS ) {
			$required_extensions["session"] = "非 SESSIONLESS 应用会调用 session_start()";
		}
		$missing = array();
		foreach ($required_extensions as $extension => $reason){
			if ( ! extension_loaded($extension) ) {
				$missing[] = $extension."（".$reason."）";
			}
		}
		if ($missing) {
			throw new YZE_FatalException("缺少必需的 PHP 扩展：".join("、", $missing));
		}
	}

	/**
	 * 动态返回配置
	 * @return array
	 */
	protected function config(): array{
		return [
			'default_db' => 'test1', // 默认链接的数据库名，请填写项目实际的数据库名
			'db_connections' => [
				'test1' => [
					'db_type' => $this->env('test1.db_type', 'mysql'),
					'db_host' => $this->env('test1.db_host', ''),
					'db_user' => $this->env('test1.db_user', ''),
					'db_psw'  => $this->env('test1.db_psw', ''),
					'db_port' => $this->env('test1.db_port', '3306'),
					'db_charset'=> $this->env('test1.db_charset', 'utf8'),
					'crypt_key'=> $this->env('test1.crypt_key', ''),
					'db_params' => [\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY=>true],
				],
				'test2' => [
					'db_type' => $this->env('test2.db_type', 'mysql'),
					'db_host' => $this->env('test2.db_host', '127.0.0.1'),
					'db_user' => $this->env('test2.db_user', ''),
					'db_psw'  => $this->env('test2.db_psw', ''),
					'db_port' => $this->env('test2.db_port', ''),
					'db_charset'=> $this->env('test2.db_charset', ''),
					'crypt_key'=> $this->env('test2.crypt_key', ''),
					'db_params' => [\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY=>true],
				],
			],
			/**
			 * 应用启动时需要加载的文件，如果指定目录，则自动包含里面的所有文件, 但要注意是按文件名排序顺序包含的，如果被包含的文件之间有依赖关系，这会导致代码错误，这种情况请手动添加包含的文件
			 */
			'include_files'=>[
				"vendor/autoload.php"
			]
		];
	}

	/**
	 * js资源分组及其包含的文件，在加载时方便直接通过分组名加载;
	 * 资源路径以web 绝对路径/开始，/指的上public_html目录
	 * 在layouts中通过接口yze_js_bundle("yangzie,foo,bar")一次打包加载这里指定的资源
	 * @return array(资源路径1，资源路径2)
	 */
	public function js_bundle(string $bundle): array{
		$config = [
//			"foo" => ['/js/foo.js']
		];
		// ai@2026-05-27 替换 @ 抑制符，使用 ?? null 显式处理
		return $config[$bundle] ?? [];
	}
	/**

	 * css资源分组及其包含的文件，在加载时方便直接通过分组名加载;
	 * 资源路径以web 绝对路径/开始，/指的上public_html目录
	 * 在layouts中通过接口yze_css_bundle("yangzie,foo,bar")一次打包加载这里指定的资源
	 * @return array(资源路径1，资源路径2)
	 */
	public function css_bundle(string $bundle): array{
		$config = [
//			"foo" => ['/css/foo.css']
		];
		// ai@2026-05-27 替换 @ 抑制符，使用 ?? null 显式处理
		return $config[$bundle] ?? [];
	}
}
?>
