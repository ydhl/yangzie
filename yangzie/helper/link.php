<?php
class Link{
	private $args 	= array();
	private $css 	= array();

	public $uri   = "";
	public $label 	= "";
	public $type 	= "link";
	public $name 	= "";
	public $img_uri= "";
	public $img_width;
	public $img_height;
	public $acl_name;
	public $target = "";
	public $title = "";
	
	const _BLANK 	= "_blank";
	const _PARENT 	= "_parent";
	const _SELF 	= "_self";
	const _TOP 		= "_top";
	
	const T_LINK	= "link";
	const T_IMG_LINK   = "img";
	const T_IMG_TEXT_LINK   = "img_text";
	const T_ANCHOR	= "anchor";
	
	public function Link($type=self::T_LINK){
		$this->type = $type;
	}
	/**
	 * 
	 * 链接中的地址部分，如http://www.example.com/test.php
	 * @param unknown_type $uri
	 */
	public function uri($uri){
		$uris = parse_url($uri);
		$paths = explode("/", $uris['path']);
		foreach ($paths as $path){
			$newpaths[] = urlencode($path);
		}
		
		$this->uri = (@$uris['scheme'] ? $uris['scheme']."://":"").
		(@$uris['user'] ? $uris['user'].":":"").
		(@$uris['pass'] ? $uris['pass']."@":"").
		(@$uris['host'] ? $uris['host']:"").
		(@$uris['port']&&$uris['port']==80 ? ":".$uris['port']."/":"").
		(@$uris['path'] ? join("/", $newpaths):"").
		(@$uris['fragment'] ? "#".$uris['fragment'] : "");
		return $this;
	}
	public function query_arg($name,$value){
		$this->args[$name] = $value;
		return $this;
	}
	public function query_args(array $datas){
		$this->args = array_merge($this->args,$datas);
		return $this;
	}
	public function css($css){
		$this->css[] = $css;
		return $this;
	}
	public function target($target){
		$this->target = $target;
		return $this;
	}
	public function label($label){
		$this->label = $label;
		return $this;
	}
    public function img_uri($img_uri){
        $this->img_uri = $img_uri;
        return $this;
    }
    public function img_width($width){
        $this->img_width = $width;
        return $this;
    }
    public function img_height($height){
        $this->img_height = $height;
        return $this;
    }
	public function name($name){
		$this->name = $name;
		return $this;
	}
	public function set($att, $value)
	{
		$this->$att = $value;
		return $this;
	}
	public function display($return=false){
        $acl = YZE_ACL::get_instance();
        $app_auth = new App_Auth();
        if ( !$acl->check_byname($app_auth->get_request_aro_name(), $this->acl_name)){
        	return;
        }
		switch ($this->type) {
			case self::T_LINK:
				$args = Http::build_query($this->args);
	            $args = $args ? "?".$args : $args;
	            $html = "<a href='{$this->uri}{$args}' "
	            .($this->target ? "target='{$this->target}'" : "")." "
	            .($this->css ? "class='".join(" ",$this->css)."'" : "")." title='".($this->title ? $this->title : $this->label)."'>".$this->label."</a>";
	            break;
			case self::T_IMG_LINK:
			case self::T_IMG_TEXT_LINK:
                $args = Http::build_query($this->args);
                $args = $args ? "?".$args : $args;
                $html = "<a href='{$this->uri}{$args}' "
                .($this->target ? "target='{$this->target}'" : "")." "
                .($this->css ? "class='".join(" ",$this->css)."'" : "")." title='".($this->title ? $this->title : $this->label)."'>
                <img src='".$this->img_uri."' ".($this->img_width ? "width='{$this->img_width}'" : "")
                ." ".($this->img_height ? "height='{$this->img_height}'" : "")." alt='".$this->label."'/>
                ".($this->type==self::T_IMG_TEXT_LINK ? "<br/>".$this->label : "")."
                </a>";
                break;
			default:
				$html = "<a name='{$this->name}'>".$this->label."</a>";
				break;
		}
	    if ($return) {
            return $html;;
        }
        echo $html;
	}
	
	public static function build($acl_name, $uri, $label){
		$link = new Link();
		$link->uri($uri);
		$link->label($label);
		$link->acl_name = $acl_name;
		$link->display();
	}
    public static function build_imglink($acl_name, $uri, $img_uri,$label){
        $link = new Link(Link::T_IMG_LINK);
        $link->uri($uri);
        $link->label($label);
        $link->img_uri($img_uri);
        $link->acl_name = $acl_name;
        $link->display();
    }
}
?>