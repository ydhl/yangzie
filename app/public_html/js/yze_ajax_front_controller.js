function yze_ajax_front_controller(){
	this.getUrl 				= "";
	this.submitCallback 		= "";
	this.loadedCallback 		= "";
	this.allowCache				= false;
	this.loadType			= "ajax";//ajax | iframe
	
	/**
	 * ajax load表单到dom中, 提交时通过ajax提交，这种方式不支持文件上传
	 * 
	 * url 加载表单的url
	 * params 加载表单的参数
	 * loadedCallback 表单加载成功后的回调，在该回调中把form放在页面中，参数是form html;如果参数是null，表示需要重新显示
	 * submitCallback 表单提交成功后的回调，参数是提交成功后的数据
	 * allowCache 是否可以缓存；默认为true，当没有提交form时，重新打开之前的网址，之前的数据总是在那里;这只是重新显示之前的表单，并没有重新加载
	 */
	this.get = function(url, params, loadedCallback, submitCallback, allowCache) {
		this.getUrl 		= url;
		this.submitCallback 	= submitCallback;
		this.loadedCallback 	= loadedCallback;
		this.allowCache = allowCache;
		this.loadType = "ajax";
		var _self = this;
		params = params || {};
		params.yze_post_context = "json";
		
		if(allowCache && $("#"+url).length>0){
			loadedCallback(null);
			return;
		}
		
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
	 * allowCache 是否可以缓存；默认为true，当没有提交form时，重新打开之前的网址，之前的数据总是在那里;这只是重新显示之前的表单，并没有重新加载
	 */
	this.load = function(url, loadedCallback, submitCallback, allowCache) {
		this.loadType = "ifrmae";
		this.allowCache = allowCache;
		var _self = this;

		window.yze_iframe_form_submitCallback = function(data){
			submitCallback(data);
		};
		
		if(allowCache && $("#"+url).length>0){
			loadedCallback(null);
			return;
		}
		
		loadedCallback("<iframe data-submited=0 id='"+url+"' marginheight='0' frameborder='0'  width='100%'  height='200px'  src='"
				+ydhlib_AddParamsInUrl(url, { yze_post_context : 'iframe'} )+"'></iframe>");
		$("#"+url).load(function(){
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

		data = data.replace(/<form\s/ig, "<form data-submited=0 data-yze-ajax-form-id='"+this.getUrl+"' ");
		//alert(data);
		this.loadedCallback(data); //该回调调用后，表单就已经加在dom中了，现在修改它的submit事件
		
		var getUrl 					= this.getUrl;
		var submitCallback 	= this.submitCallback;
		var _self = this;
		$("form[data-yze-ajax-form-id='"+this.getUrl+"']").unbind("submit");
		$("form[data-yze-ajax-form-id='"+this.getUrl+"']").submit(function(){
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
