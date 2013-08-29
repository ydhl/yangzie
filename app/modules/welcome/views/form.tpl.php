<?php
/**
 * 视图的描述
 * @param type name optional
 *
 */
 
$form = new YZE_Form($this, "form");
$form->begin_form();

?>
<h3>测试登录</h3>
<strong style="color:red"><?php echo yze_controller_error()?></strong>
<p>正确的登录名是yangzie@yangzie.com, yangzie</p>
<p>登录邮箱:<input type="text" name="email" value="<?php echo yze_get_default_value(null, "email", $this->controller)?>">
<?php echo yze_form_field_error($this->controller, "post", "email")?></p>
<p>登录密码:<input type="password" name="psw" value=""><?php echo yze_form_field_error($this->controller, "post", "psw")?></p>
<p><input type="submit" value="登录"/></p>
<?php 
$form->end_form(); 
?>