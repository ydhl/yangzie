$("#signup input[name='identity']").click(function(){
	  var obj=$("input:checked[name='identity']");
	  if ($(this).val() == "business"){
		  $("#ebusiness_comp").removeClass('hidden');
	  }else{
		  $("#ebusiness_comp").addClass('hidden');
	  }
});

$("._ckeditor").each(function(){
	var up_url = $(this).attr("up_url");
	CKEDITOR.replace($(this).attr("id"),{
	    toolbar : "Basic",
	    resize_enabled:false,
	    toolbarCanCollapse:false,
	    tabSpaces:4,
	    removePlugins:"elementspath,save,font",
	    height:500,
	    width:550,
	 	filebrowserImageUploadUrl : up_url,
	 	filebrowserFlashUploadUrl : up_url,
	 	filebrowserWindowWidth : "400",
	 	filebrowserWindowHeight : "300",
	 	//forcePasteAsPlainText : true,
	    skin:"v2",
	    toolbar:
		[
		    ["Bold","Italic","Underline","Strike"],
		    ["NumberedList","BulletedList","Blockquote","Table"],
		    ["Link","Unlink"],
		    [(up_url ? "Image" : ""),(up_url ? "Flash" : "")],
		    ["Smiley"],
		    ["TextColor","BGColor"],
		    ["Format"]
		]
	});
});
$(function(){
	var city = getCookie("28city");
	if (IPData[3]){
		$("#city").text(IPData[3]);
		var date = new Date();
		date.setDate(date.getDate()+7);
		setCookie("28city", IPData[3], "/", date);
	}else if(city){
		$("#city").text(unescape(city));
	}
});


$("#unquote,#quoted,#all_quote").click(function (){
	var filter = $(this).attr("id");
	var baseuri = $(this).attr("baseuri");
	document.location.href = baseuri+(filter=="all_quote" ? "" :"?filter="+filter);
});

$(".delete_lineitem,.delete").click(function (){
	if (confirm('删除不可恢复，您确定吗?')){
		return true;
	}else{
		return false;
	}
});

$("input[name=select_consignee]").click(function(){
	if ($(this).val() == 'new'){
		$("#consignee_modify").show();
	}else{
		$("#consignee_modify").hide();
	}
})

$("#consignee_modify_btn").click(function(){
	if ($(this).text()=="修改") {
		$(this).text("关闭");
	}else{
		$(this).text("修改");
	}
	$("#exist_consignee").toggle();
	$("#default_consignee").toggle();
});


function select_region(select, level)
{
	var selected = $("#"+select).val();
	var selected_html = '';
	var del = [];
	var options = '';
	
	if (selected) {
		$("#region_id").val(selected);
		selected_region_names[level] = $("#"+select+" option:selected").text();
		selected_region_ids[level] = selected;
		for (var i in selected_region_names) {
			if (i && i>level) {
				$("#region_"+i).replaceWith("");
				del.push(i);
			}
		}
		for(var i=0; i< del.length; i++) {
			delete selected_region_names[del[i]];
		}
		for (var i in selected_region_names) {
			selected_html += selected_region_names[i]+", ";
		}
		$("#address_prefix").text(selected_html);
		$.get("/crm/getregion/"+selected,{},function(d){
			for (var p in d) {
				options += "<option value='"+p+"'>"+d[p]+"</option>";
			}
			options = options ? "<select onchange=\"select_region('region_"+level+"', "+(level+1)+")\" id='region_"+level+"'><option value=''>请选择</option>"+options+"</select>" : "";
			if ($("#region_"+level).length ){
				$("#region_"+level).replaceWith(options);
			}else{
				$("#consignee_addr").append(options);
			}
		},
		"json");
		
		var date = new Date();
	    date.setDate(date.getDate()+7);
	    setCookie("sl", selected, "/", date);
		
		return;
	}
	
	if (selected_region_names) {
		for (var i in selected_region_names) {
			if (i>=level) {
				$("#region_"+i).replaceWith("");
				del.push(i);
			}
		}
		for(var i=0; i< del.length; i++) {
			delete selected_region_names[del[i]];
		}
		$("#region_id").val(0);
		for (var i in selected_region_names) {
			$("#region_id").val(selected_region_ids[i]);
			selected_html += selected_region_names[i]+", ";
		}
		$("#address_prefix").text(selected_html);
	}
}

function setdefault_address(key, rtoken)
{
	if (!key) return;
	$.post("/users/my/set_default/"+key,{
		yze_method:		"put",
		yze_model_id:	key,
		yze_model_name:	"Address_Model",
		yze_request_token:	rtoken,
		yze_module_name:	"crm",
		yze_modify_version:	""
	}, 
	function (d){
		if (d && d.result){
			document.location.reload();
		}
	},"json");
}
function delete_address(key, rtoken)
{
	if (!key) return;
	$.post("/users/my/address/"+key,{
		yze_method:		"delete",
		yze_model_id:	key,
		yze_model_name:	"Address_Model",
		yze_request_token:	rtoken,
		yze_module_name:	"crm",
		yze_modify_version:	""
	},function (d){
		if (d && d.result){
			$("#address"+key).hide();
		}
	},"json");
}

$("#post_suggestion").click(function (){
	if(!$.trim($("#suggestion_content").val())) {
		$("#suggestion_resp").text("请写点内容，谢谢");
		return false;
	}
	$.post("/message/post-suggestion/",{
		suggestion:	$("#suggestion_content").val(),
		author:	$("#suggestion_author").val(),
		yze_request_token:$("#yze_request_token").val()
	},function (d){
		$("#suggestion_resp").text("提交成功，非常感谢");
		$("#suggestion_content").val("")
	},"json");
});

/////////////////////////报价功能//////////////////////////////////
//$(".quote_price_item").sum("keyup", "#quote_total_price_item");
$(".quote_price_item,#quote_discount").click(function(){
	$(this).select();
});

$(".quote_price_item,#quote_discount").keyup(function(){
	$("#quote_total_price_item").text($(".quote_price_item").sum());
	var total = parseFloat($("#quote_total_price_item").text());
	var qty = parseFloat($("#quote_qty").text());
	var disc = parseFloat($("#quote_discount").val());
	total = isNaN(total) ? 0 : total;
	qty = isNaN(qty) ? 0 : qty;
	disc = isNaN(disc) ? 0 : disc;
	$("#after_discount_price").text(total*qty-disc);
});

///////////////////////时间控件绑定//////////////////////////////
$.tools.dateinput.localize("zh-cn",  {
	   months:        '一月,二月,三月,四月,五月,六月,七月,八月,九月,十月,十一月,十二月',
	   shortMonths:   '一月,二月,三月,四月,五月,六月,七月,八月,九月,十月,十一月,十二月',
	   days:          '星期日,星期一,星期二,星期三,星期四,星期五,星期六,星期七',
	   shortDays:     '日,一,二,三,四,五,六,七'
	});
$(".datectl").dateinput({	lang: 'zh-cn', 
	format: 'yyyy/mm/dd',
	offset: [30, 0]});

///////////////////////弹出对话框/////////////////////////////////
$("#facebox").overlay({
	// custom top position
	top: 260,
	// some mask tweaks suitable for facebox-looking dialogs
	mask: {
 
		// you might also consider a "transparent" color for the mask
		color: '#fff',
 
		// load mask a little faster
		loadSpeed: 200,
 
		// very transparent
		opacity: 0.5
	},
 
	// disable this for modal dialog-type of overlays
	closeOnClick: false,
 
	// load it immediately after the construction
	load: true
 
});

///////////////////首页滚动广告/////////////////////////////
$(function(){
	$("#chained").scrollable({circular: true, mousewheel: true}).navigator().autoscroll({
		interval: 10000		
	});
});

//////////////////提示框/////////////////////////////////
$(function() {
	$(".tooltip_trigger").each(function(){
		$(this).tooltip({ 
			effect: 'slide',
			offset: [10, 2],
			tipClass :$(this).attr('tipcls') 
		}).dynamic({ bottom: { direction: 'down', bounce: true } });
	});
});
/**
 * 下订单时使用参考配置
 * @param ref_id
 * @param args
 */
function use_reference(ref_id, args)
{
	if (!ref_id || !args) {
		return;
	}

	var args = args[ref_id];
	for (var n in args) {
		if((typeof args[n] )=="object") {
			//init
			$("input[type=checkbox][name="+n+"[]]").attr({"checked":false});
			$("select[name="+n+"[]] option").attr({"selected":false});
			
			for(var i=0; i<args[n].length; i++){
				$("input[type=checkbox][name="+n+"[]][value="+args[n][i]+"]").attr({"checked":true});
				$("select[name="+n+"[]] option[value="+args[n][i]+"]").attr({"selected":true});
			}
		}
		$("input[type=text][name="+n+"]").val(args[n]);
		$("input[type=radio][name="+n+"][value="+args[n]+"]").attr({"checked":true});
	}
}
/**
 * 展开折叠_id
 * @param _id
 */
function oc(_id)
{
	$("#"+_id).toggle();
}

/////定制商品模块在之间选择了商品的情况下默认选择一样商品
function select_product(curr_id)
{
	$("#selected_product_id").val(curr_id);
	$("#selected_product_name").html(names[curr_id]);

	var date = new Date();
    date.setDate(date.getDate()+7);
    setCookie("sp", curr_id, "/", date);
    
	$(".category_lnk2").removeClass("current");
	$("#product_"+curr_id).addClass("current");
}


//////////////获取焦点/////////////
$(function(){
	$(".focus").focus();
});

//////////////tab/////////////
//perform JavaScript after the document is scriptable.
$(function() {
	$("ul.tabs").tabs("> .tabpane");
});

//////////////////////////function////////////////////////////////

function setCookie(sName, sValue, path, date)
{
	document.cookie = sName + "=" + escape(sValue) + "; path="+path+"; expires=" + date.toGMTString();
}

//Retrieve the value of the cookie with the specified name.
function getCookie(sName)
{
	// cookies are separated by semicolons
	var aCookie = document.cookie.split("; ");
	for (var i=0; i < aCookie.length; i++)
	{
		// a name/value pair (a crumb) is separated by an equal sign
		var aCrumb = aCookie[i].split("=");
		if (sName == aCrumb[0])
			return unescape(aCrumb[1]);
	}
	// 	a cookie with the requested name does not exist
	return null;
}

//Delete the cookie with the specified name.
function delCookie(sName,path)
{
	document.cookie = sName + "=" + escape(sValue) + "; path="+path+"; expires=Fri, 31 Dec 1999 23:59:59 GMT;";
}
//answer buyer
function answer_for(user,qid)
{
	$("#answer_who").text(user);
	$("#answer_for").val(qid);
	$("#post_answer").show();
}
function copyCode(obj)
{ 
    var testCode=obj; 
    if(copy2Clipboard(testCode)!=false){ 
        alert(obj+"复制下来了，请Ctrl+V 贴到qq或msn分享！ "); 
    } 
} 


function copy2Clipboard(txt)
{ 
    if(window.clipboardData){ 
        window.clipboardData.clearData(); 
        window.clipboardData.setData("Text",txt); 
    } 
    else if(navigator.userAgent.indexOf("Opera")!=-1){ 
        window.location=txt; 
    } 
    else if(window.netscape){ 
        try{ 
            netscape.security.PrivilegeManager.enablePrivilege("UniversalXPConnect"); 
        } 
        catch(e){ 
            alert("您的firefox限制您进行剪贴板操作,请手动复制下面的内容进行分享:\n\n"+txt); 
            return false; 
        } 
        var clip=Components.classes['@mozilla.org/widget/clipboard;1'].createInstance(Components.interfaces.nsIClipboard); 
        if(!clip)return; 
        var trans=Components.classes['@mozilla.org/widget/transferable;1'].createInstance(Components.interfaces.nsITransferable); 
        if(!trans)return; 
        trans.addDataFlavor('text/unicode'); 
        var str=new Object(); 
        var len=new Object(); 
        var str=Components.classes["@mozilla.org/supports-string;1"].createInstance(Components.interfaces.nsISupportsString); 
        var copytext=txt;str.data=copytext; 
        trans.setTransferData("text/unicode",str,copytext.length*2); 
        var clipid=Components.interfaces.nsIClipboard; 
        if(!clip)return false; 
        clip.setData(trans,null,clipid.kGlobalClipboard); 
    } 
}
//////////配置参数界面增加值
function add_product_arg_row()
{
	$("#tbl_rows").append("<tr>"+$("#tbl_rows #tpl").html()+"</tr>");
}

/////////for ie6
if($.browser.msie && $.browser.version=='6.0'){
	$("input, textarea").each(function(){
		var t = $(this).attr("type");
		if (t=="text" || t=="password" || t=="textarea"){
			$(this).addClass('input_for_ie6');
		}
		$(this).focus(function(){
			$(this).addClass('input_for_ie6_hover');
		}).blur(function(){
			$(this).removeClass('input_for_ie6_hover');
		})
	});
}

/////////根据cookie的值自动触发
$(function() {
	try{
		var sp = getCookie("sp");
		if (sp && typeof (names) != 'undefined') {
			select_product(sp);
		}
	}catch(e){}
});