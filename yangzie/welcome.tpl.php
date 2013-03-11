<?php

?>
<h1>Yangzie - 简易PHP开发框架。</h1>
<br/>
这个界面是开发者第一次安装yangzie后运行看到的界面，这里做些什么？
<?php 
if($this->the_cache("name")){
	echo '你提交的post数据是：'.$this->the_cache("name");
}
?>
<br/><br/>

<h2>测试对主页资源的post请求</h2>
<?php 
$form = new YZE_Form($this, "post");
$form->begin_form();
echo yze_get_post_error();
?>
<table>
  <tr>
    <td>提交内容</td>
    <td><input name="name" value="<?php yze_get_default_value(null, "name")?>"/></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td><input type="submit" value="提交"/></td>
  </tr>
</table>
<?php 
$form->end_form();
?>
