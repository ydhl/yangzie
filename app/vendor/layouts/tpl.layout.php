<?php  
namespace yangzie;

?>
<!DOCTYPE html>
<head>
<meta charset="utf-8">
<title><?php echo $this->get_data("yze_page_title")?> － Yangzie Demo</title>

<?php //load js?>
<link rel="stylesheet" type="text/css" href="/bootstrap3/css/bootstrap-theme.min.css" />
<link rel="stylesheet" type="text/css" href="/bootstrap3/css/bootstrap.min.css" />

<?php //load css?>
<script type="text/javascript" src="/js/jquery-1.11.2.min.js"></script>
<script type="text/javascript" src="/bootstrap3/js/bootstrap.min.js"></script>
<script type="text/javascript" src="/js/yze_ajax_front_controller.js"></script>

</head>
<body>
    <div class="container">
        <?php echo $this->content_of_view();?>
    </div>
</body>
</html>