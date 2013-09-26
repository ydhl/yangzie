<?php
namespace app;
use app\front\File_Model;

use \app\front\Command_Item_Model;

$is_tpl 		= $this->get_data("is_tpl");
$response 	= $this->get_data("response");
$commands = $this->get_data("commands");

$response_content_active 		= !$response || ! $response->get("action") ? "active" : "";
$response_command_active 	= $response && strpos($response->get("action"), "/autoresponse/command/")!==false ? "active" : "";
$response_advance_active 		= $response && strpos($response->get("action"), "/autoresponse/api/")!==false ? "active" : "";
$response_content_show 		= !$response || ! $response->get("action") ? "show" : "hide";
$response_command_show 		=  $response && strpos($response->get("action"), "/autoresponse/command/")!==false ? "show" : "hide";
$response_advance_show 		=  $response && strpos($response->get("action"), "/autoresponse/api/")!==false ? "show" : "hide";

$response_content_text_active 		= !$response || $response->get("response_type")==Command_Item_Model::TEXT ? "active" : "";
$response_content_voice_active 	= $response && $response->get("response_type")==Command_Item_Model::VOICE ? "active" : "";
$response_content_pic_active 		= $response && $response->get("response_type")==Command_Item_Model::PIC ? "active" : "";
$response_content_video_active 	= $response && $response->get("response_type")==Command_Item_Model::VIDEO ? "active" : "";
$response_content_text_pic_active 	=  $response && $response->get("response_type")==Command_Item_Model::TEXTPIC ? "active" : "";

$response_content_text_show 		= !$response || $response->get("response_type")==Command_Item_Model::TEXT ? "show" : "hide";
$response_content_voice_show 	= $response && $response->get("response_type")==Command_Item_Model::VOICE ? "show" : "hide";
$response_content_pic_show 		= $response && $response->get("response_type")==Command_Item_Model::PIC ? "show" : "hide";
$response_content_video_show 	= $response && $response->get("response_type")==Command_Item_Model::VIDEO ? "show" : "hide";
$response_content_text_pic_show 	=  $response && $response->get("response_type")==Command_Item_Model::TEXTPIC ? "show" : "hide";

$command_content_name 			= $response ? "command_content[".$response->get_key()."]" : "new_command_content[]";
$action_name 						= $response ? "_action[".$response->get_key()."]" : "new__action[]";
$response_text_name 				= $response ? "response[".$response->get_key()."]" : "new_response[]";
$action_type_name					= $response ? "action_type[".$response->get_key()."]" : "new_action_type[]";
$response_content_type			= $response ? "response_content_type[".$response->get_key()."]" : "new_response_content_type[]";


$response_content_type_value 		= Command_Item_Model::TEXT;
$response_type						= "response";


if($response){
	$response_content_voice_html		= "<input type='hidden' value='' class='response-content-voice_id' name='response-content-voice_id[".$response->get_key()."]'/>";
	$response_content_video_html		= "<input type='hidden' value='' class='response-content-video_id' name='response-content-video_id[".$response->get_key()."]'/>";
	$response_content_pic_html		= "<input type='hidden' value='' class='response-content-pic_id' name='response-content-pic_id[".$response->get_key()."]'/>";
	$response_content_textpic_html	= "<input type='hidden' value='' class='response-content-text-pic_id' name='response-content-text-pic_id[".$response->get_key()."]'/>";
	
	$response_type = $response->get("action") ? "action" : "response";
	if($response_type=="response" && $response->get("response_type") != \app\front\Command_Item_Model::TEXT){

		$file = File_Model::find_by_id(trim($response->get("response")));
		$html_tpl = "<strong>".$file->get("file_name")."</strong>
			<input type='hidden' value='".$file->get_key()."' class='%s_id' name='%s_id[".$response->get_key()."]'/>
			<i> ".$file->get("file_desc")."</i>
			<br/><br/><a class='%s_reselect content_type_reselect btn btn-small' data-tag-class='%s' data-content-type='%s' href='javascript:void(0)'>重新选择</a>";
		
		switch($response->get("response_type")){
			case Command_Item_Model::PIC:  
				$response_content_pic_html = sprintf($html_tpl, "response-content-pic", "response-content-pic", "response-content-pic","response-content-pic","pic");
				$response_content_type_value 		= Command_Item_Model::PIC;
				break;
			case Command_Item_Model::VIDEO:  
				$response_content_video_html = sprintf($html_tpl, "response-content-video", "response-content-video", "response-content-video","response-content-video","video");
				$response_content_type_value 		= Command_Item_Model::VIDEO;
				break;
			case Command_Item_Model::VOICE:  
				$response_content_voice_html = sprintf($html_tpl, "response-content-voice", "response-content-voice", "response-content-voice","response-content-voice","voice");
				$response_content_type_value 		= Command_Item_Model::VOICE;
				break;
			case Command_Item_Model::TEXTPIC:  
				$response_content_textpic_html = sprintf($html_tpl, "response-content-text-pic", "response-content-text-pic", "response-content-text-pic","response-content-text-pic","textpic");
			$response_content_type_value 		= Command_Item_Model::TEXTPIC;
				break;
		}
	}
}else{
	$response_content_voice_html		= "<input type='hidden' value='' class='response-content-voice_id' name='new_response-content-voice_id[]'/>";
	$response_content_video_html		= "<input type='hidden' value='' class='response-content-video_id' name='new_response-content-video_id[]'/>";
	$response_content_pic_html		= "<input type='hidden' value='' class='response-content-pic_id' name='new_response-content-pic_id[]'/>";
	$response_content_textpic_html	= "<input type='hidden' value='' class='response-content-text-pic_id' name='new_response-content-text-pic_id[]'/>";
}

$cache_index = $response ? $response->get_key() : "";
?>
<div class="well whitebg response <?php echo $is_tpl ? "response-tpl" : ""?>">
	<a class="close delete-response" href="javascript:void(0)" title="删除">&times;</a>
	
	<div>
		<div class="input-prepend input-append">
  			<span class="add-on">当用户回复</span>
 			<input class="span3" type="text" value="<?php echo \yangzie\yze_get_default_value($response, "command_content", $this->controller, $cache_index)?>"
 				name="<?php echo $command_content_name?>" 
 				placeholder="用户回复内容，为空表示任何内容">
 			<span class="add-on">时，响应：</span>
 			<div class="btn-group " data-toggle="buttons-radio" >
				  <button type="button" data-target="response-content" data-action-type="response" class="response-btn btn <?php echo $response_content_active?>">内容</button>
				  <button type="button" data-target="response-command" data-action-type="action" class="response-btn btn <?php echo $response_command_active?>">显示另一个问题</button>
				  <button type="button" data-target="response-advance" disabled class="response-btn btn <?php echo $response_advance_active?>">高级定制</button>
				  <input type="hidden" name="<?php echo $action_type_name?>" class="action_type" value="<?php echo $response_type?>"/>
			</div>
		</div>
	</div>
	<div class="response-type response-advance <?php echo $response_advance_show?>">
		<p class="disabled">可根据用户的回复从您的业务系统中动态查询内容，如需要该类型响应，请联系96696628进行定制</p>
	</div>
	<div class="response-type response-command <?php echo $response_command_show?>">
		<select class="input-block-level" name="<?php echo $action_name?>"><option>选择问题</option>
		<?php 
		if($response){
			$curr_command = $response->get_command();
		}
		foreach((array)$commands as $command){
			$action = "/autoresponse/command/".$command->get_key();
			
			if($curr_command && $curr_command->get_key()==$command->get_key())continue;
		?>
			<option value="<?php echo $action?>" <?php echo $response && $action==$response->get("action") ? "selected" : ""?>><?php echo $command->get_sort_conent()?></option>
		<?php 
		}
		?>
		</select>
	</div>
	<div class="response-type response-content <?php echo $response_content_show?>">
		<input type="hidden" class="data-response-id" name="just_use_in_js" value="<?php echo $response ? $response->get_key() : ""?>"/>
		<div class="btn-group " data-toggle="buttons-radio" style="margin:10px 0px 10px 0px;display:block">
			  <button type="button" data-target="response-content-text" class="response-content-btn btn <?php echo $response_content_text_active?>">文字</button>
			  <button type="button" data-target="response-content-voice"  class="response-content-btn btn <?php echo $response_content_voice_active?>">语音</button>
			  <button type="button" data-target="response-content-pic"   class=" response-content-btn btn <?php echo $response_content_pic_active?>">图片</button>
			  <button type="button" data-target="response-content-video"  class=" response-content-btn btn <?php echo $response_content_video_active?>">视频</button>
			  <button type="button" data-target="response-content-text-pic" disabled class="disabled response-content-btn btn <?php echo $response_content_text_pic_active?>">图文</button>
			  <input type="hidden" name="<?php echo $response_content_type?>" class="response_content_type" value="<?php echo $response_content_type_value?>"/>
		</div>
		<div class="response-content-type response-content-text <?php echo $response_content_text_show?>">
			输入响应文字内容<textarea class="input-block-level" rows="6" name="<?php echo $response_text_name?>"><?php echo \yangzie\yze_get_default_value($response, "response", $this->controller, $cache_index)?></textarea>
		</div>
		<div class="response-content-type response-content-voice <?php echo $response_content_voice_show?>">
			<?php echo $response_content_voice_html;?>
		</div>
		<div class="response-content-type response-content-pic <?php echo $response_content_pic_show?>">
			<?php echo $response_content_pic_html;?>
		</div>
		<div class="response-content-type response-content-video <?php echo $response_content_video_show?>">
			<?php echo $response_content_video_html;?>
		</div>
		<div class="response-content-type response-content-text-pic <?php echo $response_content_text_pic_show?>">
			<?php echo $response_content_textpic_html;?>
		</div>
	</div>
</div>