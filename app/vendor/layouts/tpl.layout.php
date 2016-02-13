<?php  
namespace yangzie;
?>
<html>
<head>
<meta charset="utf-8">
<title><?php echo $this->get_data("yze_page_title")?> － <?php echo APPLICATION_NAME?></title>
<?php 
yze_css_bundle("bootstrap");
yze_module_css_bundle();
yze_js_bundle("jquery,bootstrap,yangzie,pjax");
?>
</head>
<body>
    <div class="container">
        <?php echo $this->content_of_view();?>
    </div>
<?php yze_module_js_bundle();?>
</body>
</html>