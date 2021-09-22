$(function(){
	
	$("*[ectype='ul_cate'] a").click(function(){
        replaceParam('cate_id', this.id);
        return false;
    });
	
	$("*[ectype='ul_sgrade'] a").click(function(){
        replaceParam('sgrade', this.id);
        return false;
    });

    $("*[ectype='ul_region'] a").click(function(){
        replaceParam('region_id', this.id);
        return false;
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
	var href = (window.location.href).split('?');
    location.assign(formatUrl(href[0] + '?' + params.join('&')));
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
		if (pKey == key)
        {
            params.splice(i, 1);
        }
    }
    var href = (window.location.href).split('?');
    location.assign(formatUrl(href[0] + '?' + params.join('&')));
}

function formatUrl(href)
{
	href = href.replace('?&', '?');
	if($.inArray(href.substr(href.length-1,1), ['?', '&']) > -1) {
		return href.substr(0, href.length-1);
	}
	return href;
}

function setFilterOrder(orderby, o)
{
	if(orderby) 
	{
		var array = orderby.split('|');
		var activeSort = array[0]+'|'+array[1];
		$('*[ectype="'+activeSort+'"]').parents('.items').find('li a').removeClass('active');
		$('*[ectype="'+activeSort+'"]').addClass('active');
		if(activeSort == 'praise_rate|desc'){
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