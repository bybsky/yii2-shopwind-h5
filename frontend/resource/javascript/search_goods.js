$(function(){
   
	$("*[ectype='ul_cate'] a").click(function(){
        replaceParam('cate_id', this.id);
        return false;
    });
    $("*[ectype='ul_brand'] a").click(function(){
        replaceParam('brand', this.id);
        return false;
    });
	
    $("*[ectype='ul_price'] a").click(function(){
        replaceParam('price', this.id);
        return false;
    });
    $("*[ectype='ul_region'] a").click(function(){
        replaceParam('region_id', this.id);
        return false;
    });
	
	$("*[ectype='ul_prop'] a").click(function(){
		id = $(this).parents('*[ectype="ul_prop"]').attr('propsed')+'|'+this.id;
		if(id.substr(0,1) == '|') id = id.substr(1);
		replaceParam('props',id);
		return false;
    });
	
	$(".selected-attr a").click(function(){
		dropParam(this.id);
		return false;
	});
	
	$(".J_ChangeDisplayMode").click(function(){
		var currMode = $(this).hasClass('list') == true ? 'list'    : 'squares';
		var showMode = $(this).hasClass('list') == true ? 'squares' : 'list';
		$(this).removeClass(currMode).addClass(showMode);

		$("*[data-cookie='"+$(this).attr('id')+"']").removeClass(currMode).addClass(showMode);
		$.setCookie($(this).attr('id'), showMode);
	});
	
	$(".J_ActiveSort").click(function(){	
		if($(".J_SortEject").is(":hidden")) {
			$(".J_SortEject").show();
			$(this).find('i').addClass('up').html('&#xe620;');
		} else {
			$(".J_SortEject").hide();
			$(this).find('i').removeClass('up').html('&#xe61f;');
		}
	});
	
	$(".J_SortEject span a, .J_ActiveSortClick").click(function(){
		if($.trim($(this).attr('ectype')) == ''){
			dropParam('orderby');// default order
			return false;
		}
		else
		{
			var id = $.trim($(this).attr('ectype'));
			var sortStr = id.split('|');
			var dd = sortStr[1] ? sortStr[1] : '|desc';
			
			replaceParam('orderby', sortStr[0]+'|'+dd);
			return false;
		}
	});
	
	$('.J_SearchFilterPrice').click(function(){
		start_price = number_format($(this).parent().find('input[name="start_price"]').val(),0);
		end_price   = number_format($(this).parent().find('input[name="end_price"]').val(),0);
		if(start_price>=end_price){
			end_price = Number(start_price) + 200;
		}
		replaceParam('price', start_price+'-'+end_price);
		return false;
	});
	
});


/* 替换参数 */
function replaceParam(key, value)
{
	var params = location.search.substr(1).split('&');

    var found  = false;
    for (var i = 0; i < params.length; i++)
    {
        param = params[i];
        arr   = param.split('=');
        pKey  = arr[0];
        if (pKey == 'page')
        {
            params[i] = 'page=1';
        }
        if (pKey == key)
        {
            params[i] = key + '=' + value;
            found = true;
        }
    }
    if (!found)
    {
        params.push(key + '=' + value);
    }
	
	// 缓动关闭筛选层
	$('.J_GoodsFilterPop').animate({'right':'-110%','left' : '110%'});
	setTimeout(function() {
		var href = (window.location.href).split('?');
    	location.assign(formatUrl(href[0] + '?' + params.join('&')));
	}, 500);
}

/* 删除参数 */
function dropParam(key)
{
    var params = location.search.substr(1).split('&');
    for (var i = 0; i < params.length; i++)
    {
        param = params[i];
        arr   = param.split('=');
        pKey  = arr[0];
        if (pKey == 'page')
        {
            params[i] = 'page=1';
        }
	
		if (pKey == 'props' || pKey == 'brand')
		{
			arr1 = arr[1];
			arr1 = arr1.replace(key,'');
			arr1 = arr1.replace("||",'|');
			if(arr1.substr(0,1)=="|") {
				arr1 = arr1.substr(1,arr1.length-1);
			}
			if(arr1.substr(arr1.length-1,1) == "|") {
				arr1 = arr1.substr(0,arr1.length-1);
			}
			params[i]=pKey + "=" + arr1;
		}
        if (pKey == key || params[i]=='props=' || params[i]=='brand=')
        {
            params.splice(i, 1);
        }
    }
	// 缓动关闭筛选层
	$('.J_GoodsFilterPop').animate({'right':'-110%','left' : '110%'});
	setTimeout(function() {
		var href = (window.location.href).split('?');
    	location.assign(formatUrl(href[0] + '?' + params.join('&')));
	}, 500);
}

function formatUrl(href)
{
	href = href.replace('?&', '?');
	if($.inArray(href.substr(href.length-1,1), ['?', '&']) > -1) {
		return href.substr(0, href.length-1);
	}
	return href;
}
function setFilterPrice(filter_price)
{
	if(filter_price) {
		filter_price = filter_price.split('-');
		$('input[name="start_price"]').val(number_format(filter_price[0],0));
		$('input[name="end_price"]').val(number_format(filter_price[1],0));
	}
}
function setFilterOrder(orderby, o)
{
	if(orderby) 
	{
		var array = orderby.split('|');
		var activeSort = array[0]+'|'+array[1];
		$('*[ectype="'+activeSort+'"]').parents('.items').find('li a').removeClass('active');
		$('*[ectype="'+activeSort+'"]').addClass('active');
		if(activeSort == 'sales|desc'){
			$(".J_ActiveSort").removeClass('active').find('span').text(lang.all_sort);
		}else{
			$(".J_ActiveSort").find('span').text($('*[ectype="'+activeSort+'"]').find('ins').text());
		}
		
		if($(".J_SortEject").length > 0) {
			$(".J_SortEject").find('span a').removeClass('active');
		}
	}
	
	if(o != undefined)
	{
		if(o.id == ''){
			dropParam('orderby')
			return false;
		}
		else
		{
			dd = "|desc";
			if(orderby != '') {
				var array = orderby.split('|');
				if(array[0] == o.id && array[1] == "desc")
					dd = "|asc";
				else dd = "|desc";
			}
			replaceParam('orderby', o.id + dd);
			return false;
		}
	}
}