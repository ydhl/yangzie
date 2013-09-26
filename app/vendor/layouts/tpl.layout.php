<?php  namespace yangzie;use app\front\User_Model;
$selected_menu = $this->get_data("selected_menu");
?>
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
	<div class="page-header-line">.</div>

	<div class="container">
		<a class="brand pull-left" href="/"></a>
			<?php 
			if(($user = YZE_Session_Context::get_instance()->get("login_user"))){
			echo "<div class='pull-right userinfo'><img src='",$user->get("avatar"),"'/> ", $user->get("nick_name");
			if( $user->get("user_type") == User_Model::VIP ){
				echo " （VIP用户）";
			}else if( $user->get("user_type") == User_Model::BASE ){
				echo "（高级用户）";
			}else{
				echo "（普通用户）";
			}
			echo  "&nbsp;<a href='/logout'>登出</a></div>";
		}
		?>
		
	</div>

	<div class="main-container">
		<div class="container">
			<br/>
			<div class="navbar">
				<div class="navbar-inner">
					<ul class="nav">
						<li <?php echo $selected_menu=="menu" ? 'class="active"' : ""?>><a href="/admin/menus"><img width="48px" alt="" src="/themes/default/img/menu.png"><br/>自定义菜单</a></li>
						<li <?php echo $selected_menu=="command" ? 'class="active"' : ""?>><a href="/admin/commands"><img  width="48px"  alt="" src="/themes/default/img/autoresponse.png"><br/>自动应答</a></li>
						<li <?php echo $selected_menu=="setting" ? 'class="active"' : ""?>><a href="/admin"><img  width="48px"  alt="" src="/themes/default/img/advanced.png"><br/>基本配置</a></li>
						<li <?php echo $selected_menu=="appstore" ? 'class="active"' : ""?>><a href="/apps"><img  width="48px"  alt="" src="/themes/default/img/appstore.png"><br/>应用中心</a></li>
					</ul>
				</div>
			</div>

			<div class="main-container-body well">
			<?php echo $yze_content_of_layout;	?>
			</div>
		</div>
		
	</div>
	<div class="container">
		<!-- FOOTER -->
		<footer>
			<p class="pull-right">
				<a href="#">回到顶部</a>
			</p>
			<p>
				&copy;
				<?php echo date("Y")?>
				<a href="http://yidianhulian.com" target="_blank">易点互联</a>. &middot;<a
					href="#">服务协议</a>&middot; <img alt="" height="12"
					src="http://static.sae.sina.com.cn/image/poweredby/117X12px.gif"
					width="117">
			</p>
		</footer>

	</div>
	<script src="/js/jquery.js"></script>
	<script src="/js/bootstrap.min.js"></script>
	<script src="/js/json.js"></script>
	<script src="/js/outerHTML-2.1.0-min.js"></script>
	<script src="/js/ydhlib.js/ydhlib.url.js"></script>
	<script src="/js/yze_ajax_front_controller.js"></script>
	<script src="/js/f.js"></script>
	<script src="/js/e.js"></script>
	<script>
      !function ($) {
        $(function(){
          $('#myCarousel').carousel()
        })
      }(window.jQuery)
    </script>
</body>
</html>
