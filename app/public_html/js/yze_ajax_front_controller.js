function yze_ajax_front_controller(){
	this.getUrl 				= "";
	this.submitCallback 		= "";
	this.loadedCallback 		= "";
	this.loadType			= "ajax";//ajax | iframe
	
	/**
	 * ajax load表单到dom中，这种方式不支持文件上传
	 * 
	 * url 加载表单的url
	 * params 加载表单的参数
	 * loadedCallback 表单加载成功后的回调，在该回调中把form放在页面中，参数是form html
	 * submitCallback 表单提交成功后的回调，参数是提交成功后的数据
	 */
	this.get = function(url, params, loadedCallback, submitCallback) {
		this.getUrl 		= url;
		this.submitCallback 	= submitCallback;
		this.loadedCallback 	= loadedCallback;
		this.loadType = "ajax";
		var _self = this;
		params = params || {};
		params.yze_post_context = "json";
		
		$.ajax({
		        url: url,
		        type: "GET",
		        data: params,
		        error: function(jqXHR, textStatus, errorThrown) {alert(errorThrown);},
		        success: function(data, textStatus, jqXHR) {
		        	modifyForm.call(_self, data);
		        },
		        dataType: "html"
		});
	}
	
	/**
	 * 
	 * 构建一个iframe，并在其中加载表单
	 * 
	 * url 加载表单的url
	 *  loadedCallback 表单加载成功后的回调，在该回调中把form放在页面中，参数是iframe html
	 * submitCallback 表单提交成功后的回调，参数是提交成功后的数据
	 * cancelCallback 关闭的回调
	 */
	this.load = function(url, loadedCallback, cancelCallback, submitCallback) {
		this.loadType = "ifrmae";
		var _self = this;

		window.yze_iframe_form_cancelCallback = function(){
			cancelCallback();
		};
		
		window.yze_iframe_form_submitCallback = function(data){
			submitCallback(data);
		};
		
		loadedCallback("<iframe id='_yze_iframe_form' marginheight='0' frameborder='0'  width='100%'  height='200px'  src='"
				+ydhlib_AddParamsInUrl(url, { yze_post_context : 'iframe'} )+"'></iframe>");
		$("#_yze_iframe_form").load(function(){
			var newheight;
			var newwidth;
			newheight = this.contentWindow.document.body.scrollHeight;
			newwidth = this.contentWindow.document.body.scrollWidth;

			this.height = (newheight) + "px";
			this.width = (newwidth) + "px";
		});
	}
	
	
	// ---------------------------------
	//              private
	// ---------------------------------
	function modifyForm(data){
		var id = new Date();
		data = data.replace(/<form\s/ig, "<form data-yze-ajax-form-id='"+id+"' ");
		//alert(data);
		this.loadedCallback(data); //该回调调用后，表单就已经加在dom中了，现在修改它的submit事件
		
		var getUrl 					= this.getUrl;
		var submitCallback 	= this.submitCallback;
		var _self = this;
		$("form[data-yze-ajax-form-id='"+id+"']").unbind("submit");
		$("form[data-yze-ajax-form-id='"+id+"']").submit(function(){
			var postData = $(this).serialize();//这里的this指表单
			var action =  $(this).attr("action") || getUrl; //如果沒有指定action，那麼post仍然提交到getUrl中
			var method = $(this).attr("method") || "POST";
			
			postData += "&yze_post_context=json";
			
			$.ajax({
				url: 		action,
				type: 	method,
				data: 	postData,
			        error: function(jqXHR, textStatus, errorThrown) {
			        	alert(errorThrown);
			        },
			        success: function(data, textStatus, jqXHR) {
			        	//ajax POST的返回，可能是成功后的json，也可能是非json，如失败后的重定向, 表单数据回显
			        	try{
			        		var json = JSON.parse(data);
			        	}catch(e){}
		        		if(json){
		        			submitCallback(json);
		        		}else{
		        			modifyForm.call(_self, data);
		        		}
			        },
			        dataType: "html"
			    });
				return false; //阻止表单自己的提交
		});
	}
}

/**
 * 
 * add params in to url.
 * addParamsInUrl("helloworld.php", {foo1:bar1, foo2:bar2}) will return helloworld.php?foo1=bar1&foo2=bar2
 * 
 * if params has exist in url，new param will replace old
 * addParamsInUrl("helloworld.php?foo1=bar1", {foo1:bar2, foo2:bar2}) will return helloworld.php?foo1=bar2&foo2=bar2
 * 
 * if url is null, ""; will return querystring like:
 * addParamsInUrl("", {foo1:bar2, foo2:bar2}) will return foo1=bar2&foo2=bar2
 * 
 * if params is null, "", {}; will return the url:
 * addParamsInUrl("helloworld.php", "") will return helloworld.php
 * 
 * if params is not object, will append to url and return:
 * addParamsInUrl("hello", "world") will return hello?world
 * 
 * @param url
 * @param params json object like {foo1:bar1, foo2:bar2}
 */
function ydhlib_AddParamsInUrl(url, params){
    var queryString = [];
    if(typeof(params)=="object"){
        for(name in params){
            queryString.push( name+"="+params[name] );
        }
    }else{
        if(params){
            queryString.push(params);
        }
    }
    
    if( ! url){
        return queryString.join("&"); 
    }
    
    var urlComps = url.split("?");
    if(urlComps.length==1){
        return queryString.length>0 ? url+"?"+queryString.join("&") : url; 
    }
    
    var oldQueryString = urlComps[1].split("&");
    var oldParams = {};
    for(var i=0; i < oldQueryString.length; i++){
        var nameValue = oldQueryString[i].split("=");
        if( params[nameValue[0]]) continue;
        queryString.push(nameValue[0] + "=" + (nameValue.length < 1 ? "" : nameValue[1]));
    }
    
    return queryString.length>0 ? urlComps[0]+"?"+queryString.join("&") : urlComps[0]; 
}
