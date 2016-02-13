<?php 
namespace yangzie;
?>
<h1><i class="glyphicon glyphicon-ok"></i> 
Yangzie by <a target="_blank" href="http://yidianhulian.com">YDHL</a><sup>&copy;</sup> at GuiYang</h1>
0. 配置
<ol>
<li>windows 需要设置php的路径到path环境变量中去</li>
<li>apache 开启url_rewrite<code>http.conf: LoadModule rewrite_module modules/mod_rewrite.so</code></li>
<li>apache 开启 htaccess支持<code>http.conf: LoadModule rewrite_module modules/mod_rewrite.so</code></li>
</ol>
1. 创建代码
<ol>
	<li>打开cmd，进入到<?php echo realpath(YZE_INSTALL_PATH)?></li>
	<li><code>php scripts/yze.php</code></li>
	<li>enjoy</li>
</ol>