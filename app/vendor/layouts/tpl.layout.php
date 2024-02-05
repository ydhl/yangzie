<?php
namespace yangzie;
// 正常返回视图布局
?>
<html>
    <head>
        <meta charset="utf-8">
        <title><?php echo $this->get_data("yze_page_title")?> － <?php echo YZE_APP_NAME?></title>
        <?php
        yze_css_bundle("");
        yze_module_css_bundle();
        yze_js_bundle("yangzie");
        yze_module_js_bundle();
        ?>
    </head>
    <body>
        <?php echo $this->content_of_view();?>
    </body>
</html>
