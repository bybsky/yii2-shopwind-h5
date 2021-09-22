$(function(){
	if($('body').find('img.lazyload').length > 0) {
		$("img.lazyload").lazyLoad();
	}
	
	// 处理移动端下:hover 无效的问题
	document.body.addEventListener('touchstart', function () {});
	
	$(".float-back-top").hide();
	 $(document).on('scroll', function(event) {
		if ($(window).scrollTop() > 420) {
            $(".float-back-top").show();
         } else {
            $(".float-back-top").hide();
         }
		 
		 // 控制详情页的标题栏
		 if ($(window).scrollTop() > 0)
		 {
			 var h = ($('.goods-detail .col-img').height()-$(window).scrollTop()) / $('.goods-detail .col-img').height();
			 
			 $('.J_BarGradient .barbg').css('opacity', 1-h); // 0-1
			 $('.J_BarGradient .barbtn a').css('opacity', h/2);  // 0.5-0
			 
			 if(h <= 0.5) {
				 // 0-1
				 $('.J_BarGradient .barbtn a').css('color', '#000').css('background', '#fff').css('opacity', (0.5-h)/0.5); 
				 if(h <= 0) {
					 $('.J_BarGradient').css('border-bottom', '1px #f4f4f4 solid');
				 }
				 $('.J_BarGradient .barbtn span').css('opacity', (0.5-h)/0.5); 
			 }
			 
			 if(h >　0)
			 {
				 
				 $('.J_BarGradient').css('border-bottom', 'none');
				 if(h > 0.5) {
					// 1-0.5
					$('.J_BarGradient .barbtn a').css('color', '#fff').css('background', '#000').css('opacity', h-0.5);
					$('.J_BarGradient .barbtn span').css('opacity', 0);
				 }
			 }
		 }
		 
		// 控制通用标题栏
		if ($(window).scrollTop() >= $('.J_BarWrap .barbg').height())
		{
			//首页
			$('#page-layout-default-index .J_BarWrap .top-bar').addClass('leave').css('opacity', $(window).scrollTop()*0.006);
			
			//店铺首页
			$('#page-layout-store-index .J_BarWrap .top-bar').addClass('leave').css('opacity', $(window).scrollTop()*0.006);
			
			// 用户中心首页
			if($(window).scrollTop() >= 120) {
				$('#page-layout-user-index .bar-wrap .top-bar').css('position', 'fixed').css('background-color', '#E4393C').css('background-position', '-9999px -99999px');
				$('#page-layout-user-index .bar-wrap .top-bar .barbtn li a').css('opacity', ($(window).scrollTop()-149)*0.1);
				$('#page-layout-user-index .curlocal-title').css('opacity', 1);
			}
			
			// 店铺搜索页/店铺促销页
			if($(window).scrollTop() >= 100) {
				$('#page-layout-store-search .J_BarWrap .top-bar').addClass('leave').css('opacity', 1);
				$('#page-layout-store-search .store-menus').addClass('fixed');
				$('#page-layout-store-search .listTab').addClass('fixed');
				$('#page-layout-store-search .sort-eject').addClass('fixed');
				
				$('#page-layout-store-limitbuy .J_BarWrap .top-bar').addClass('leave').css('opacity', 1);
				$('#page-layout-store-limitbuy .store-menus').addClass('fixed');
			}
			
		}
		else
		{
			// 首页/店铺首页/店铺促销页
			$('#page-layout-default-index .J_BarWrap .top-bar').removeClass('leave').css('opacity', 1);
			$('#page-layout-store-index .J_BarWrap .top-bar').removeClass('leave').css('opacity', 1);
			$('#page-layout-store-search .J_BarWrap .top-bar').removeClass('leave').css('opacity', 1);
			$('#page-layout-store-limitbuy .J_BarWrap .top-bar').removeClass('leave').css('opacity', 1);
			
			// 用户中心首页
			$('#page-layout-user-index .bar-wrap .top-bar').css('position', 'relative').css('background-color', 'none').css('background-position', '0px 0px');
			$('#page-layout-user-index .bar-wrap .top-bar .barbtn li a').css('opacity', 0);
			$('#page-layout-user-index .curlocal-title').css('opacity', 0);
			
			// 店铺搜索页/店铺促销页
			$('#page-layout-store-search .store-menus').removeClass('fixed');
			$('#page-layout-store-search .listTab').removeClass('fixed');
			$('#page-layout-store-search .sort-eject').removeClass('fixed');
			$('#page-layout-store-limitbuy .store-menus').removeClass('fixed');
		}
		 
  	});
	
	//页面底部导航 点击箭头，显示隐藏导航
   	$('.global-nav__operate-wrap').on('click',function(){
		$('.global-nav').toggleClass('global-nav-current');
   	});
	$('.options .more').click(function(){
		$(this).parents('.attrv').find('li.hidden').toggle();
		if($(this).hasClass('unfold') == true)
		{
			$(this).find('span').html('查看更多');
			$(this).removeClass('unfold');
		}
		else
		{
			$(this).find('span').html('收起更多');
			$(this).addClass('unfold');
		}
	})
	
	$('.true-search-box .close').click(function(){
		history.go(-1);
	});
	
	// 兼容搜索商品，搜索店铺，搜索店铺内商品
	$('.J_SearchInputGradient').click(function(){
		var conditions = {};
		var searchType = $.trim($(this).attr('searchType'));
		if(searchType == '' || searchType == undefined || searchType == 'form') {
			searchType = 'index';
		}
		conditions['searchType'] = searchType;
		
		var store_id = parseInt($.trim($(this).attr('store_id')));
		if(store_id) {
			conditions['store_id'] = store_id;
		}
		if($.trim($(this).val() != '')) {
			conditions['keyword'] = $.trim($(this).val());
		}
		window.location.href = url(['search/form', conditions]);
	});
	
	// 通用搜索框按下回车键后触发，提交表单
	$('#page-layout-search-form .J_BarWrap input').keydown(function(event){
        //alert(event.keyCode);
		if(event.keyCode == 13) {
			$(this).parent('form').submit();
		}
	});
	$('.J_SearchInputHome').click(function(){
		var store_id = $(this).attr('store_id');
		if($.inArray(store_id, [undefined, '']) > - 1) {
			window.location.href = url(['search/form']);
		}
		else window.location.href = url(['search/form', {store_id:parseInt(store_id)}]);
	});
	
	// 页面右上角弹窗导航菜单
	$('.J_PageMenu').click(function(){
		var html = $('.J_PageMenuBox').html();
		layer.open({
    		content: html,
			shade: 'background-color: rgba(0,0,0,.1)',
			style: 'width:13px;min-width:130px;position:absolute; top:3px; right:0px;background-color:transparent;box-shadow:0 0 0 0;',
			anim: 'up'
  		});
	});
	
	$('.J_InputDel').click(function(){
		$(this).parent().find('input[type="text"], input[type="password"]').val('');
		$(this).hide();
	});
	
	$('.J_SwtcherInput').click(function(){
		if(!$(this).hasClass('disabled')) {
			$(this).toggleClass('checked');
		}
	});
	
	// 当需要按住背景色改变而又不好使用A标签的情况下，使用此JS替代
	$('.J_TouchActive').on('click', function () {
		//alert($(this).find('.J_TouchUri').attr('href'));
		$(this).css('background', '#eee');
		//setTimeout(function(){
			//$(this).css('background', '#f9f9f9');
			window.location.href = $(this).find('.J_TouchUri').attr('href');
			
		//},5000)
		
	});
});

function clearInput(o) 
{
    if ($(o).val().length > 0) {
        $(o).next('.input-del').show();
    } else {
        $(o).next('.input-del').hide();
    }
}

function pageBack()
{
	window.history.back();
}

