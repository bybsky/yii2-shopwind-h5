$(function(){
	$(window).load(setWorkspace);
	$(window).resize(setWorkspace);
	
	if($.getCookie('switchHistory')) {
		var switchHistory = $.getCookie('switchHistory');
		switchHistory = switchHistory.split('/');
		openItem(switchHistory[0], switchHistory[1]);
	} else openItem('dashboard');
	
	$('#nav li a.link').click(function(){
		var tabName = this.id.substr(4);
		openItem(tabName);
	});
	
	//自定义radio样式
    $(".cb-enable").click(function(){
        $(this).addClass('selected');
		$(this).siblings('.cb-disable').removeClass('selected');
		$(this).siblings('input:first').attr('checked', true);
    });
    $(".cb-disable").click(function(){
		$(this).addClass('selected');
		$(this).siblings('.cb-enable').removeClass('selected');
		$(this).siblings('input:last').attr('checked', true);
    });
	
	// 刷新框架
	$('#iframe_refresh').click(function(){
        $('#workspace').get(0).contentWindow.location.reload();
    });
	
	// 清空所有缓存
	$('#clear_cache').click(function(){
		$.getJSON(url(['default/clearCache']), function(data){
			parent.layer.msg(data.msg);
 		});
	});
});

/* 设置工作区 */
function setWorkspace(){
    var wWidth = $(window).width();
    var wHeight = $(window).height();
    $('#workspace').width(wWidth - $('#left').width() - parseInt($('#left').css('margin-right')));
    $('#workspace').height(wHeight - $('#head').height());
}

function switchTab(tabName){
    var id = '#tab_' + tabName;
    $('#nav').find('a.link').each(function(){
        $(this).removeClass('actived');
        $(this).addClass('link');
    });
    $(id).addClass('actived');
}

function switchMenu(tabName, menuName) {
	var obj = $('#tab_'+tabName);
	$('#submenuTitle').text(obj.text());
	$('#submenu').find('dd').remove();
	$('#submenu').append(obj.parent().find('.sub-menu').html());
	
    $('#submenu').find('a').each(function(){
        $(this).removeClass('selected');
    });
    $('#submenu').find('#item_' + menuName).addClass('selected');
}

function openItem(tab, menu, parent, obj){
    if((typeof(tab)) == 'undefined' || (tab == '')) {
		tab = parent;
	}

	if((typeof(menu) == 'undefined') || (menu == '')) {
		menu = $('#tab_'+tab).parent().find('.sub-menu').find('dd.default').find('a').attr('id').substr(5);
	}
	
	switchTab(tab);
	switchMenu(tab, menu);
	
	$.setCookie('switchHistory', tab+'/'+menu);

    /* 更新iframe的内容 */
    $('#workspace').show();
	
	var uri = $('#item_' + menu).attr('uri');
	if(uri == '' || (typeof uri == 'undefined')) {
		return;
	}
    $('#workspace').attr('src', $('#item_' + menu).attr('uri'));
}