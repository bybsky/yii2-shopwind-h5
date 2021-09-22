$(function(){
	/* 全选 */
    $('.checkall').click(function(){
        $('.checkitem').prop('checked', this.checked);
    });
	
	$(".show_image").mouseover(function(){
        $(this).parent().siblings(".show_img").show();
    });
    $(".show_image").mouseout(function(){
        $(this).parent().siblings(".show_img").hide();
    });
	$(".type-file-file").change(function(){
		$(this).siblings(".type-file-text").val($(this).val());
	});
	
	//自定义radio样式
    $(".cb-enable").click(function(){
        $(this).addClass('selected');
		$(this).siblings('.cb-disable').removeClass('selected');
		$(this).siblings('input:first').attr('checked', true);
		$(this).siblings('input:first').click();
    });
    $(".cb-disable").click(function(){
		$(this).addClass('selected');
		$(this).siblings('.cb-enable').removeClass('selected');
		$(this).siblings('input:last').attr('checked', true);
		$(this).siblings('input:last').click();
    });
	
	$('body').on('click', '.J_BatchDel', function() { 
   		if($('.checkitem:checked').length == 0){    //没有选择
			parent.layer.msg('没有选择项',{icon:2});
    		return false;
      	}
 		//获取选中的项 
      	var items = '';
   		$('.checkitem:checked').each(function(){
        	items += this.value + ',';
    	});
     	items = items.substr(0, (items.length - 1));
		var uri = $(this).attr('uri');
		uri = uri + ((uri.indexOf('?') != -1) ? '&' : '?') + $(this).attr('name') + '=' + items;
		ajaxRequest($(this), uri);
	});
	
	var wWidth = $(window).width();
    var wHeight = $(window).height();
    //$('#page_main').width(wWidth - $('#left').width() - parseInt($('#left').css('margin-right')));
    $('#page_main').height(wHeight - $('#head').height());
});

function clear_file()
{
	$.getJSON(url(['template/clearfile']), function(data){
		if(data.done) {
			layer.msg(data.msg);
			return true;
		}
		layer.msg(lang.clear_empty);
	})
}

function fg_delete(id, controller, action, reason) {
	var id = id;
	if (typeof(id) == 'number') {
    	id = new Array(id.toString());
	} else if (typeof(id) == 'string') {
		id = new Array(id);
	}
	var action = action ? action : 'delete';
	
	parent.layer.confirm('删除后将不能恢复，确认删除这 ' + id.length + ' 项吗？',{icon: 3, title:'提示'},function(index){
		if(reason === true){
			parent.layer.prompt({
				formType: 2,
				value: '',
				title: '删除原因'
			}, function(value, index, elem){
				$.ajaxSettings.async = false;
				$.getJSON(url([controller+'/'+action]), {id:id.join(','), content:value}, function(data){
					if (data.done){
						parent.layer.close(index);
						parent.layer.msg(data.msg);
						$("#flexigrid").flexReload();
					} else {
						parent.layer.msg(data.msg);
						$("#flexigrid").flexReload();
					}
				});
			});
		}
		else
		{
			$.ajaxSettings.async = false;
			$.getJSON(url([controller+'/'+action]), {id:id.join(',')}, function(data){
				if (data.done){
					parent.layer.close(index);
					parent.layer.msg(data.msg);
					$("#flexigrid").flexReload();
				} else {
					parent.layer.close(index);
					parent.layer.msg(data.msg);
					$("#flexigrid").flexReload();
				}
			});
		}
		parent.layer.close(index);
	},function(index){
		parent.layer.close(index);
	});
}

function fg_csv(id, controller, action, model) {
    var id = id.join(',');
	var action = action ? action : 'export';
	var model = model ? model : '';
    window.location.href = url([controller+'/'+action, $.extend($("#formSearch").serializeJson(), {id:id, model:model})]);
}

function fg_cancel(id, controller, action, reason) {
	var id = id;
	if (typeof(id) == 'number') {
    	id = new Array(id.toString());
	} else if (typeof(id) == 'string') {
		id = new Array(id);
	}
	var action = action ? action : 'cancel';
	
	parent.layer.confirm('确认取消这 ' + id.length + ' 项吗？',{icon: 3, title:'提示'},function(index){
		if(reason === true){
			parent.layer.prompt({
				formType: 2,
				value: '',
				title: '取消原因'
			}, function(value, index, elem){
				$.ajaxSettings.async = false;
				$.getJSON(url([controller+'/'+action]), function(data) {
					if (data.done){
						parent.layer.close(index);
						parent.layer.msg(data.msg);
						$("#flexigrid").flexReload();
					} else {
						parent.layer.close(index);
						parent.layer.msg(data.msg);
						$("#flexigrid").flexReload();
					}
				});
			});
		}
		else
		{
			$.ajaxSettings.async = false;
			$.getJSON(url([controller+'/'+action]), {id:id.join(',')}, function(data){
				if (data.done){
					parent.layer.close(index);
					parent.layer.msg(data.msg);
					$("#flexigrid").flexReload();
				} else {
					parent.layer.close(index);
					parent.layer.msg(data.msg);
					$("#flexigrid").flexReload();
				}
			});
		}
		parent.layer.close(index);
	},function(index){
		parent.layer.close(index);
	});
}
