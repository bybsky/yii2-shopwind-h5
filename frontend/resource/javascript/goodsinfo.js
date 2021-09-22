/* spec对象 */
function spec(id, spec1, spec2, spec_image, price, stock, goods_id)
{
    this.id    = id;
    this.spec1 = spec1;
    this.spec2 = spec2;
    this.price = price;
    this.stock = stock;
	this.goods_id = goods_id;
	this.spec_image = spec_image;
}

/* goodsspec对象 */
function goodsspec(specs, specQty, defSpec)
{
    this.specs = specs;
    this.specQty = specQty;
    this.defSpec = defSpec;
    this.spec1 = null;
    this.spec2 = null;
    if (this.specQty >= 1)
    {
        for(var i = 0; i < this.specs.length; i++)
        {
            if (this.specs[i].id == this.defSpec)
            {
                this.spec1 = this.specs[i].spec1;
                if (this.specQty >= 2)
                {
                    this.spec2 = this.specs[i].spec2;
                }
                break;
            }
        }
    }

    // 取得某字段的不重复值，如果有spec1，以此为条件
    this.getDistinctValues = function(field, spec1, distinct = true, items = false)
    {
        var values = new Array();
        for (var i = 0; i < this.specs.length; i++)
        {
            var value ;
			if(items == true){
				value = this.specs[i];
			}else{
				value = this.specs[i][field];
			}
            if (spec1 != '' && spec1 != this.specs[i].spec1) continue;
			if (distinct == false){
				values.push(value);
				continue;
			} 
			if($.inArray(value, values) < 0){
				values.push(value);
			}
        }
        return (values);
    }	

    // 取得选中的spec
    this.getSpec = function()
    {
        for (var i = 0; i < this.specs.length; i++)
        {
            if (this.specQty >= 1 && this.specs[i].spec1 != this.spec1) continue;
            if (this.specQty >= 2 && this.specs[i].spec2 != this.spec2) continue;

            return this.specs[i];
        }
        return null;
    }

    // 初始化
    this.init = function()
    {
		var spec = this.getSpec();
        if (this.specQty >= 1)
        {
			var specImage = this.getDistinctValues('spec1', '', true, true);
            var spec1Values = this.getDistinctValues('spec1', '');
			var stock = this.getDistinctValues('stock', '' , false);
            for (var i = 0; i < spec1Values.length; i++)
            {
				var aclass ,liclass,canclick,bhidden,spec_img;
				aclass = liclass = canclick = bhidden = spec_img = "";
				if(specImage[i].spec_image){
					spec_img = "<img width='20' height='20' src='" + url_format(specImage[i].spec_image) + "'/><span>" + spec1Values[i] + "</span>";
				}else{
					spec_img = "<span>" + spec1Values[i] + "</span>";
				}
				if(this.specQty == 1 && stock[i] == 0 ){
					aclass = "class='none'";
					bhidden = "style='display:none'"
				}else{
					canclick = " onclick='selectSpec(1, this)'";
				}
                if (spec1Values[i] == this.spec1){
					liclass = "class='solid'";
				}else{
					liclass = "class='dotted'";
				}

				$(".handle ul:eq(0)").append("<li " + liclass + canclick + "><a href='javascript:;' title='"+ spec1Values[i] +"' " + aclass + ">" + spec_img + "<b " + bhidden + "></b></a></li>");

            }
        }
        if (this.specQty >= 2)
        {
            var spec2Values = this.getDistinctValues('spec2', this.spec1);
			var stock = this.getDistinctValues('stock', this.spec1, false);
            for (var i = 0; i < spec2Values.length; i++)
            {
				var aclass ,liclass,canclick,bhidden;
				aclass = liclass = canclick = bhidden = "";
				if(stock[i] == 0){
					aclass = "class='none'";
					bhidden = "style='display:none'"
				}else{
					canclick = "onclick='selectSpec(2, this)'";
				}
                if (spec2Values[i] == this.spec2){
					liclass = "class='solid'";
				}else{
					liclass = "class='dotted'";
				}
				
				$(".handle ul:eq(1)").append("<li " + liclass + canclick + "><a href='javascript:;' title='"+ spec2Values[i] +"' " + aclass + "><span>" + spec2Values[i] + "</span><b " + bhidden + "></b></a></li>");
            }
        }
        var spec = this.getSpec();
		setGoodsProInfo(spec.goods_id, spec.id, spec.price);
        $("[ectype='current_spec']").html(spec.spec1 + ' ' + spec.spec2);
		$("[ectype='goods_stock']").html(spec.stock);
		if(spec.spec_image){
			$(".pop-select-spec .info img").attr('src',spec.spec_image);
		}
    }
}

/* 选中某规格 num=1,2 */
function selectSpec(num, liObj)
{
    goodsspec['spec' + num] = $(liObj).find('a span').html();
	
    $(liObj).attr("class", "solid");
    $(liObj).siblings(".solid").attr("class", "dotted");
	if(num == 1)
	{
		if($(liObj).find('img').length > 0)
		{
			$(".pop-select-spec .info img").attr('src',$(liObj).find('img').attr('src'));
		}
		else
		{
			$(".pop-select-spec .info img").attr('src',$(".tempWrap ul li:last").find('img').attr('src'));
		}
	}

    // 当有2种规格并且选中了第一个规格时，刷新第二个规格
    if (num == 1 && goodsspec.specQty == 2)
    {
        goodsspec.spec2 = null;
        $(".aggregate").html("");
        $(".handle ul:eq(1) li[class='handle_title']").siblings().remove();

        var spec2Values = goodsspec.getDistinctValues('spec2', goodsspec.spec1);
		var stock = goodsspec.getDistinctValues('stock', goodsspec.spec1);
        for (var i = 0; i < spec2Values.length; i++)
        {
			var aclass ,liclass,canclick;
			aclass = canclick = "";
			if(!stock[i] || stock[i] == 0 ){
				aclass = "class='none'";
			}else{
				canclick = "onclick='selectSpec(2, this)'";
			}

			$(".handle ul:eq(1)").append("<li class='dotted' " + canclick + "><a href='javascript:;' title='"+ spec2Values[i] +"' " + aclass + "><span>" + spec2Values[i] + "</span><b></b></a></li>");
			
        }
    }
    else
    {
        var spec = goodsspec.getSpec();
        if (spec != null)
        {
			setGoodsProInfo(spec.goods_id, spec.id, spec.price);
			$("[ectype='current_spec']").html(spec.spec1 + ' ' + spec.spec2);
            $("[ectype='goods_stock']").html(spec.stock);
        }
    }
}

$(function(){
    goodsspec.init();
	
	$('.J_SelectSpecLayer').find('.handle').css('top', $('.J_SelectSpecLayer').find('.info').height()+20);

	/* 商品图切换 */
	TouchSlide({ slideCell:"#slides",titCell:".hd",mainCell:".bd",effect:"leftLoop", autoPlay:true,autoPage:true, titOnClassName:"active", delayTime:1000, interTime: 5000});
	
	$('.buy-quantity em').click(function(){
		var type = $(this).attr('change');
		var _v = Number($('#quantity').val());
		var stock = Number($('*[ectype="goods_stock"]').text());
		
		if(type == 'reduce')
		{
			if(_v > 1)
			{
				$('#quantity').val(_v-1);
			}
		}
		else if(_v < stock) {
			$('#quantity').val(_v+1);
		}else{
			layer.open({ content:lang.no_enough_goods, time: 5});
		}
		
	});
		
	$('.buy-quantity #quantity').keyup(function(){
		var _v = Number($('#quantity').val());
		var stock = Number($('*[ectype="goods_stock"]').text());
		if(_v > stock){ 
			layer.open({ content:lang.no_enough_goods, time: 5});
			$(this).val(stock);
		}
		if(_v < 1 || isNaN(_v)) {
			layer.open({ content:lang.invalid_quantity, time: 5});
			$(this).val(1);
		}
	});

	$('.J_GoBuy').popLayer({
		popLayer : '.J_SelectSpecLayer',
		top: '20%',
		//fixedBody: true,
		callback : function(e){
			var type = e.attr('ectype');
			$('.J_BtnConfirm').find('.'+type).show().siblings().hide();
		}
	});
	
	/* 抢购倒计时 */
	$.each($('.countdown'),function(){
		var theDaysBox  = $(this).find('.NumDays');
		var theHoursBox = $(this).find('.NumHours');
		var theMinsBox  = $(this).find('.NumMins');
		var theSecsBox  = $(this).find('.NumSeconds');
			
		countdown(theDaysBox, theHoursBox, theMinsBox, theSecsBox)
	});
	
});

function buy(toPay)
{
    if (goodsspec.getSpec() == null)
    {
		layer.open({ content:lang.select_specs, time: 2});
        return;
    }
    var spec_id = goodsspec.getSpec().id;

    var quantity = $("#quantity").val();
    if (quantity == '')
    {
		layer.open({ content:lang.input_quantity, time: 2});
        return;
    }
    if (parseInt(quantity) < 1 || isNaN(quantity))
    {
		layer.open({ content:lang.invalid_quantity, time: 2});
        return;
    }
    add_to_cart(spec_id, quantity, toPay);
}

// 加载城市的运费(指定城市id或者根据ip自动判断城市id)
function load_city_logistic(template_id,store_id,city_id)
{
	var html = '';
	$.getJSON(url(['logistic/index', {template_id: template_id, store_id: store_id, city_id: city_id}]), function(data){
		if (data.done){
			var logistic = data.retval;
			html = logistic.name + lang.colon + (logistic.start_fees <= 0 ? lang.logistic_free : price_format(logistic.start_fees));
			$('.postage-info').html(html);
		}
		else {
			$('.postage-info').html(data.msg);
		}
	});
}

// 获取促销商品，会员价格等的优惠信息
function setGoodsProInfo(goods_id, spec_id, price)
{
	$.getJSON(url(['goods/promoinfo', {goods_id: goods_id, spec_id: spec_id}]),function(data) {
		if (data.done){
			pro_price = data.retval.price;
			pro_type  = data.retval.type;
			
			// 目前只有限时打折商品有倒计时效果
			if($.inArray(pro_type, ['limitbuy']) > -1) {
				$('.J_CountDown').css('display', 'block');
			}
			else if($.inArray(pro_type, ['exclusive']) > -1) {
				$('.J_ProType-exclusive').css('display', 'block');
			}
			$(".J_IsPro").css('display','block');
			$(".J_IsNotPro").css('display','none');
			$("[ectype='goods_price']").html('<del>'+price_format(price)+'</del>');
			$("[ectype='goods_pro_price']").html(price_format(pro_price));
			
			// 计算获得的积分
			setGoodsIntegralInfo(pro_price);
		}
		else
		{
			$("[ectype='goods_price']").html(price_format(price));
			$('.J_CountDown').css('display', 'none');
			$('.J_ProType-exclusive').css('display', 'none');
			$(".J_IsPro").css('display','none');
			$(".J_IsNotPro").css('display','block');
			
			// 计算获得的积分
			setGoodsIntegralInfo(price);
		}
	});
}

// 在页面显示不同价格的赠送积分
function setGoodsIntegralInfo(price)
{
	var integral = (price * $('.J_BuyIntegralNum').attr('data-value')).toFixed(2);
	//var integral = Math.floor(price * $('.J_BuyIntegralNum').attr('data-value'));
	if(integral > 0) {
		$('.J_PromotoolPop').css('border-bottom-width', 1);
		$('.J_GetIntegralPop').show();
		$('.J_BuyIntegralNum').html(integral);
	} else {
		$('.J_GetIntegralPop').hide();
		$('.J_PromotoolPop').css('border-bottom-width', 0);
	}
}