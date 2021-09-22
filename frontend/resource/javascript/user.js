$(function(){
	$(".fixed-layer .backtop").hide();
	 $(document).scroll(function() {
		if ($(window).scrollTop() > 250) {
            $(".fixed-layer .backtop").show();
         } else {
            $(".fixed-layer .backtop").hide();
         }
  	});
	$('.fixed-layer .backtop').on('click', function(event) {
		window.scrollTo(0,0);
  	});
	$('.fixed-layer .menu-mini').click(function(){
		$(this).parent().toggleClass('active-fixed-layer');
		$(this).parent().find('.menu-fixed-list').toggle();
	});
	
	$('.J_CheckItem').click(function(){
		var items = getCheckItemIds();
		if(items) {
			$('.float-layer').hide();
			$('.J_BatchOpt').show();
		}
		else
		{
			$('.float-layer').show();
			$('.J_BatchOpt').hide();
		}
	});	
	$('.J_BatchOpt a').click(function(){
		var items = getCheckItemIds();
		if(items)
		{
			var uri = $(this).attr('uri');
            uri += (uri.indexOf('?') > -1 ? '&' : '?') + $(this).attr('name') + '=' + items;
			location.href = uri;
		}
	});
	
	$('body').on('click', '*[ectype="gselector"]', function(event){
        var id = $(this).attr('gs_id');
        var name = $(this).attr('gs_name');
        var callback = $(this).attr('gs_callback');
        var type = $(this).attr('gs_type');
        var store_id = $(this).attr('gs_store_id');
        var title = $(this).attr('gs_title') ? $(this).attr('gs_title') : '';
        var width = $(this).attr('gs_width');
		var style = $(this).attr('gs_class');
		var opacity = $(this).attr('gs_opacity');
		var uri = $(this).attr('gs_uri').indexOf('?') > -1 ? $(this).attr('gs_uri') + '&' : $(this).attr('gs_uri') + '?';
		var position = $(this).attr('gs_position') ? $(this).attr('gs_position') : 'center';
        ajax_form(id, title, uri+'title='+title+'&store_id='+store_id+'&id='+id+'&name='+name+'&callback='+callback, width, style, opacity, position);
        return false;
    });
	
});
function pageBack()
{
	window.history.back();
}
function getCheckItemIds()
{
	if($('.checkitem:checked').length == 0){
		return false;
	}
	if($(this).attr('presubmit')){
		if(!eval($(this).attr('presubmit'))){
			return false;
		}
	}
	var items = '';
	$('.checkitem:checked').each(function(){
		items += this.value + ',';
	});
	items = items.substr(0, (items.length - 1));
		
	return items;
}