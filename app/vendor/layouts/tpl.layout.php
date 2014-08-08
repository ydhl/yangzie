<?php  namespace yangzie;
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
<?php echo $this->content_of_section("test section");?>
<hr/>
<?php echo $this->content_of_view();?>
</body>
</html>
