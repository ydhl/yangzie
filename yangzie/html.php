<?php

function get_default_value($object, $name, $uri=null)
{
	if (Session::post_cache_has($name, $uri)){
		return Session::get_cached_post($name, $uri);
	}
	if ($object){
		return $object->get($name);
	}
	return "";
}
function get_post_error()
{
	$session = Session::get_instance();
	$uri = Request::get_instance()->the_uri();
	if ($session->has_exception($uri)) {
		return nl2br($session->get_uri_exception($uri)->getMessage());
	}
}
?>