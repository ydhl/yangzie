<?php
/**
 *
 * 返回后台进程中运行的函数，返回函数名，后台进程将每隔yze_get_sleep seconds后依次运行这些函数
 * 您可以在这些函数中编写后台运行的代码
 *
 */
function yze_get_jobs() {
	return "send_email";
}

function hello_yze(){
	yze_bd_log("hello yze ".date("Y-m-d H:i:s"));
}


function send_email()
{
	require_once APP_PATH.'modules/emails/models/edm_email_model.class.php';
	require_once APP_PATH.'modules/emails/models/edm_subject_model.class.php';
	require_once APP_PATH.'modules/emails/models/edm_list_model.class.php';

	require_once APP_PATH.'modules/emails/models/edm_email_list_model.class.php';
	require_once APP_PATH.'modules/emails/models/edm_subject_list_model.class.php';


	$app_config = new App_Module();
	$mailer       = new PHPMailer();
	$mailer->IsSMTP();
	$mailer->SMTPAuth   = $app_config->get_module_config("smtp_auth");
	$mailer->SMTPSecure = $app_config->get_module_config("smtp_secure");
	$mailer->Host       = $app_config->get_module_config("smtp_host");
	$mailer->Port       = $app_config->get_module_config("smtp_port");
	$mailer->Username   = $app_config->get_module_config("smtp_username");
	$mailer->Password   = $app_config->get_module_config("smtp_password");
	$mailer->SMTPDebug  = $app_config->get_module_config("smtp_debug");
	$mailer->SetFrom($app_config->get_module_config("smtp_from"), $app_config->get_module_config("smtp_fromname"));
	$mailer->CharSet    = "UTF-8";



	$sql = new SQL();
	$sql->from("Edm_Subject_Model","s")
	->where_group(array(
	new Where("s", "send_on", SQL::EQ, "0000-00-00 00:00:00","or"),
	new Where("s", "send_on", SQL::ISNULL,"", "or"))
	)->where_group(array(
	new Where("s", "is_draft", SQL::EQ, "0","or"),
	new Where("s", "is_draft", SQL::ISNULL,"", "or"))
	)->where_group(array(
	new Where("s", "status", SQL::EQ, "","or"),
	new Where("s", "status", SQL::ISNULL,"", "or"))
	);

	//yze_bd_log($sql."");
	$fail  = array();
	foreach ((array)DBAImpl::getDBA()->select($sql) as $mail_subject) {
		yze_bd_log("mail subject: ".$mail_subject->get("subject"));
		foreach ($mail_subject->get_lists() as $_) {
			$list = $_->get_list();
			yze_bd_log("mail list: ".$list->get("list_name"));
			foreach($list->get_emails() as $email){
				$email_addr = $email->get("email");
				yze_bd_log("mail: ".$email_addr);
				$mailer->Subject    = $mail_subject->get("subject");
				$mailer->AltBody    = "28ding.com";
				$mailer->MsgHTML($mail_subject->get("contents"));
				$mailer->AddAddress($email_addr, substr($email_addr, 0, strpos($email_addr, "@")));
				if(!$mailer->Send()) {
					$fail[] = $email;
				}
				yze_bd_log("sended to $email_addr");
				$mail_subject->save();
			}
			$mail_subject->set("send_on",date("Y-m-d H:i:s"));
			$mail_subject->set("status","已发送");
			$mail_subject->save();
		}
	}
}
?>