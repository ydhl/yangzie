<?php  namespace yangzie;?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?php echo $this->get_data("yze_page_title")?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="">
<meta name="author" content="">

<!-- Le styles -->
<link href="/themes/default/css/bootstrap.min.css" rel="stylesheet">
<link href="/themes/default/css/bootstrap-responsive.css"
	rel="stylesheet">
<link href="/themes/default/css/user-defined.css" rel="stylesheet">
<!-- HTML5 shim, for IE6-8 support of HTML5 elements -->
<!--[if lt IE 9]>
      <script src="/js/html5shiv.js"></script>
    <![endif]-->

<!-- Fav and touch icons -->
<link rel="apple-touch-icon-precomposed" sizes="144x144"
	href="/themes/default/ico/apple-touch-icon-144-precomposed.png">
<link rel="apple-touch-icon-precomposed" sizes="114x114"
	href="/themes/default/ico/apple-touch-icon-114-precomposed.png">
<link rel="apple-touch-icon-precomposed" sizes="72x72"
	href="/themes/default/ico/apple-touch-icon-72-precomposed.png">
<link rel="apple-touch-icon-precomposed"
	href="/themes/default/ico/apple-touch-icon-57-precomposed.png">
<link rel="shortcut icon" href="/favicon.png">
</head>

<body>
	<?php echo $yze_content_of_layout;	?>
	<script src="/js/jquery.js"></script>
	<script src="/js/bootstrap.min.js"></script>
	<script src="/js/json.js"></script>
	<script src="/js/outerHTML-2.1.0-min.js"></script>
	<script src="/js/ydhlib.js/ydhlib.url.js"></script>
	<script src="/js/yze_ajax_front_controller.js"></script>
	<script src="/js/f.js"></script>
	<script src="/js/e.js"></script>
</body>
</html>
