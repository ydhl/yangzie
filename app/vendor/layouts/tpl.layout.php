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
</head>

<body>
	
			<?php echo $yze_content_of_layout;	?>
</body>
</html>
