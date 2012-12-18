if( !this.yze_ajax_front_controller ){
	this.yze_ajax_front_controller = {
			getUrl:'', 
			triggerId:'',
			callback:''
	};
}

( function() {
	var submit = function(){
		var postData = $(this).serialize();
		var action =  $(this).attr("action") || yze_ajax_front_controller.getUrl; //如果沒有指定action，那麼post仍然提交到getUrl中
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
		        	var trigger_callback = $("#"+yze_ajax_front_controller.trigger_id).attr("data-yze-ajax-controller-callback");
		        	try{
		        		var json = JSON.parse(data);
		        	}catch(e){}
	        		if(json && trigger_callback){
	        			trigger_callback(json);
	        		}else{
	        			modifyForm(data);
	        		}
		        },
		        dataType: "html"
		    });
			return false; //阻止表单自己的提交
	};
	
	function modifyForm(data){
		var id = new Date();
		data = data.replace(/<form\s/ig, "<form data-yze-ajax-form-id='"+id+"' ");
		//alert(data);
		yze_ajax_front_controller.callback(data); //该回调调用后，表单就已经加在dom中了，现在修改它的submit事件
		
		$("form[data-yze-ajax-form-id='"+id+"']").unbind("submit", submit);
		$("form[data-yze-ajax-form-id='"+id+"']").submit(submit);
	}
	
	if (typeof yze_ajax_front_controller.get !== 'function') {
		yze_ajax_front_controller.get = function(url, params, callback, triggerId) {
			this.getUrl 		= url;
			this.triggerId 	= triggerId;
			this.callback 	= callback;
			
			$.ajax({
			        url: url,
			        type: "GET",
			        headers: {
			            "X-YZE-Request-Client" : "AJAX"
			        },
			        data: params,
			        error: function(jqXHR, textStatus, errorThrown) {alert(errorThrown);},
			        success: function(data, textStatus, jqXHR) {
			        	modifyForm(data);
			        },
			        dataType: "html"
			    });
			}
		}
})();

//var _ajax_front_controller = {
//	get : function(url, params, callback, trigger_id) {
//		$.ajax({
//	        url: url,
//	        type: "GET",
//	        headers: {
//	            "X-YZE-Request-Client" : "AJAX"
//	        },
//	        data: params,
//	        error: function(jqXHR, textStatus, errorThrown) {},
//	        success: function(data, textStatus, jqXHR) {
//	        	var id = new Date();
//				data = data.replace(/<form\s/ig, "<form data-yze-ajax-form-id='"+id+"' ");
//				//alert(data);
//				callback(data); //该回调调用后，表单就已经加在dom中了，现在修改它的submit事件
//				$("form[data-yze-ajax-form-id='"+id+"']").unbind("submit");
//				$("form[data-yze-ajax-form-id='"+id+"']").submit(function(){
//					var postData = $(this).serialize();
//					var action =  $(this).attr("action") || url; //如果沒有指定action，那麼post仍然提交到url中
//					var method = $(this).attr("method") || "POST";
//					
//					$.ajax({
//				        url: 		action,
//				        type: 	method,
//				        headers: {
//				            "X-YZE-Request-Client" : "AJAX"
//				        },
//				        data: 	postData,
//				        error: function(jqXHR, textStatus, errorThrown) {
//				        	alert(textStatus);
//				        },
//				        success: function(data, textStatus, jqXHR) {
//				        	//ajax POST的返回，可能是成功后的json，也可能是非json，如果失败后的重定向, 表单数据回显
//				        	var trigger_callback = $("#"+trigger_id);
//				        	try{
//					        	var json = JSON.parse(data);
//				        	}catch(e){}
//			        		if(json && trigger_callback){
//			        			trigger_callback(json);
//			        		}else{
//			        			callback(data);
//			        		}
//				        },
//				        dataType: "html"
//				    });
//					return false; //阻止自己的提交
//				});
//	        },
//	        dataType: "html"
//	    });
//	}
//};