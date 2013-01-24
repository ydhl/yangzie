<?php

	//TODO 定义视图显示
?>
<h1>Yangzie - 简易PHP开发框架。</h1>
<br/>
<?php 
if($this->the_cache("name")){
	echo '你提交的post数据是：'.$this->the_cache("name");
}else{
	echo '这是对资源（主页 index）的get请求，模板文件中 /home/views/index.tpl.php 中';
}
?>
<br/><br/>

<h2>测试对主页资源的post请求</h2>
<?php 
$form = new YZE_Form($this, "post");
$form->begin_form();
echo get_post_error();
?>
<table>
  <tr>
    <td>提交内容</td>
    <td><input name="name" value="<?php get_default_value(null, "name")?>"/></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td><input type="submit" value="提交"/></td>
  </tr>
</table>
<?php 
$form->end_form();
?>
