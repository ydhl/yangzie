function yze_ajax_front_controller(){
	this.getUrl 					= "";
	this.submitCallback = "";
	this.httpCallback 		= "";
	
	//api 
	this.get = function(url, params, httpCallback, submitCallback) {
		this.getUrl 		= url;
		this.submitCallback 	= submitCallback;
		this.httpCallback 	= httpCallback;
		var _self = this;
		
		$.ajax({
		        url: url,
		        type: "GET",
		        headers: {
		            "X-YZE-No-Content-Layout" : "yes"
		        },
		        data: params,
		        error: function(jqXHR, textStatus, errorThrown) {alert(errorThrown);},
		        success: function(data, textStatus, jqXHR) {
		        	modifyForm.call(_self, data);
		        },
		        dataType: "html"
		});
	}
	
	
	// ---------------------------------
	//              private
	// ---------------------------------
	function modifyForm(data){
		var id = new Date();
		data = data.replace(/<form\s/ig, "<form data-yze-ajax-form-id='"+id+"' ");
		//alert(data);
		this.httpCallback(data); //该回调调用后，表单就已经加在dom中了，现在修改它的submit事件
		
		var getUrl 					= this.getUrl;
		var submitCallback 	= this.submitCallback;
		var _self = this;
		$("form[data-yze-ajax-form-id='"+id+"']").unbind("submit");
		$("form[data-yze-ajax-form-id='"+id+"']").submit(function(){
			var postData = $(this).serialize();//这里的this指表单
			var action =  $(this).attr("action") || getUrl; //如果沒有指定action，那麼post仍然提交到getUrl中
			var method = $(this).attr("method") || "POST";
			
			$.ajax({
				url: 		action,
				type: 	method,
				headers: {
					"X-YZE-Request-Client" : "AJAX"
				},
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
