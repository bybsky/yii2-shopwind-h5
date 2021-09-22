$(function(){
    // 开启规格编辑器
    $('*[ectype="edit_spec"],*[ectype="add_spec"]').click(function(){

        spec_editor();
    });
    
    // 关闭规格
    $('*[ectype="disable_spec"]').click(function(){
		
		layer.open({
    		content: '关闭规格后将清空现有规格数据，要继续吗？'
    		,btn: ['确定', '取消']
    		,yes: function(index){
				layer.close(index);

        		SPEC = {"spec_qty":0,"spec_name_1":"","spec_name_2":"","specs":null};
        		spec_update();
    		}
  		});
    });
	// 添加规格项
    $('*[ectype="add_spec_item"]').unbind('click');
	$('body').on('click','*[ectype="add_spec_item"]',function(){
        var new_spec = $('#dialog_object_spec_editor').find('*[ectype="data"]:last').clone(true);
        new_spec.find('input[item="spec_id"]').val('');
		new_spec.find('.spec_image').find('i').html('&#xe6e8;');
		new_spec.find('input').each(function(index, element) {
            new_spec.find('input[type="text"][name="'+$(element).attr('name')+'"]').attr('name', $(this).attr('item')+'[]');
        });
        new_spec.insertAfter($('#dialog_object_spec_editor').find('*[ectype="data"]:last'));

    });

    // 删除规格项
	$('body').on('click','*[ectype="drop_spec_item"]',function(){
        $('#dialog_object_spec_editor').find('*[ectype="data"]').length > 1 && $(this).parent().parent().remove();
    });
	
	$('body').on('click','*[ectype="del_spec_item"]',function(){
		var text = $(this).text();
		if(text == lang.del_spec_item)
		{
			$(this).text(lang.finished);
			$('#dialog_object_spec_editor ul.th li:last').text(lang.handel);
			$('#dialog_object_spec_editor ul.td li .spec_image').hide();
			$('#dialog_object_spec_editor ul.td li .spec-item-del').show();
			hide_drop_button();
		}
		else
		{
			$(this).text(lang.del_spec_item);
			$('#dialog_object_spec_editor ul.th li:last').text(lang.spec_image);
			$('#dialog_object_spec_editor ul.td li .spec_image').show();
			$('#dialog_object_spec_editor ul.td li .spec-item-del').hide();
		}
	});
});

function spec_update(){

    /* spec name */
    var spec_name_1 = $('*[ectype="spec_result"]').find('*[col="spec_name_1"]');
    var spec_name_2 = $('*[ectype="spec_result"]').find('*[col="spec_name_2"]');
    if(SPEC.spec_name_1){
        spec_name_1.show();
        spec_name_1.text(SPEC.spec_name_1);
        spec_name_1.append('<input type="hidden" name="spec_name_1" value="' + SPEC.spec_name_1 + '" />');
    }else{
        spec_name_1.hide();
        spec_name_1.html('');
    }
    if(SPEC.spec_name_2){
        spec_name_2.show();
        spec_name_2.text(SPEC.spec_name_2);
        spec_name_2.append('<input type="hidden" name="spec_name_2" value="' + SPEC.spec_name_2 + '" />');
    }else{
        spec_name_2.hide();
        spec_name_2.html('');
    }

    /* spec item */
    $('*[ectype="spec_result"]').find('*[ectype="data"]').remove();
    var d_spec_item = $('*[ectype="spec_result"]').find('*[ectype="spec_item"]');
    d_spec_item.hide();
    SPEC.specs && $.each(SPEC.specs,function(i,item){
        var tpl = d_spec_item.clone(true);
        tpl.attr('ectype', 'data');
		tpl.append('<input type="hidden" name="sort_order['+ item.spec_id +']" value="' + i + '" />');
        if(SPEC.spec_name_1){
            tpl.find('*[item="spec_1"]').text(item.spec_1);
            tpl.find('*[item="spec_1"]').append('<input type="hidden" name="spec_1['+ item.spec_id +']" value="' + item.spec_1 + '" />');
        }else{
            tpl.find('*[item="spec_1"]').append('<input type="hidden" name="spec_1['+ item.spec_id +']" value="" />');
            (SPEC.spec_qty == "1" || SPEC.spec_qty == "0") && tpl.find('*[item="spec_1"]').hide();
        }
        if(SPEC.spec_name_2){
            tpl.find('*[item="spec_2"]').text(item.spec_2);
            tpl.find('*[item="spec_2"]').append('<input type="hidden" name="spec_2['+ item.spec_id +']" value="' + item.spec_2 + '" />');
        }else{
            tpl.find('*[item="spec_2"]').append('<input type="hidden" name="spec_2['+ item.spec_id +']" value="" />');
            (SPEC.spec_qty == "1" || SPEC.spec_qty == "0") && tpl.find('*[item="spec_2"]').hide();
        }
        tpl.find('*[item="price"]').append('<input type="hidden" name="price['+ item.spec_id +']" value="' + item.price + '" />' + item.price);
        tpl.find('*[item="stock"]').append('<input type="hidden" name="stock['+ item.spec_id +']" value="' + item.stock + '" />' + item.stock);
        tpl.find('*[item="sku"]').append('<input type="hidden" name="sku['+ item.spec_id +']" value="' + item.sku + '" /><input type="hidden" name="spec_id['+ item.spec_id +']" value="' + item.spec_id + '" />' + item.sku);
		if(item.spec_image){
			tpl.find('*[item="spec_image"]').append('<input type="hidden" name="spec_image['+ item.spec_id +']" value="' + item.spec_image + '" /><img height="20" width="20" src="' + url_format(item.spec_image) + '"/>');
		}else{
			tpl.find('*[item="spec_image"]').append('<input type="hidden" name="spec_image['+ item.spec_id +']" value=" " />');
		}
        tpl.show();
        d_spec_item.before(tpl);
    });

    if(SPEC.spec_qty == 0){
        $('*[ectype="no_spec"]').find('input').prop('disabled', false);
        $('*[ectype="no_spec"]').show();
        $('*[ectype="has_spec"]').find('input').prop('disabled', true);
        $('*[ectype="has_spec"]').hide();
    }else{
        $('*[ectype="no_spec"]').find('input').prop('disabled', true);
        $('*[ectype="no_spec"]').hide();
        $('*[ectype="has_spec"]').find('input').prop('disabled', false);
        $('*[ectype="has_spec"]').show();
    }
}



function hide_drop_button()
{
	$('#dialog_object_spec_editor').find('*[ectype="drop_spec_item"]').show();
	$('#dialog_object_spec_editor').find('*[ectype="drop_spec_item"]:first').hide();
}

/* 创建规格编辑器 */
function spec_editor(){
    /* 规格名称 */
    $('*[ectype="spec_editor"]').find('*[col="spec_name_1"]').val(SPEC.spec_name_1);
    $('*[ectype="spec_editor"]').find('*[col="spec_name_2"]').val(SPEC.spec_name_2);

    /* 初始化规格项 */
    $('*[ectype="spec_editor"]').find('*[ectype="data"]').remove(); // 移除所有规格项
    var d_spec_item = $('*[ectype="spec_editor"]').find('*[ectype="spec_item"]'); // 规格项模板
    d_spec_item.hide(); // 隐藏模板
    var spec_item; // 规格项目json数组
    if(SPEC.spec_qty ==0){
        spec_item = ['']; // 如果没有规格则显示一行空白规格项
    }else{
        spec_item = SPEC.specs;
    }
    spec_item && $.each(spec_item,function(i,item){ // 遍历生成规格项
        var tpl = d_spec_item.clone(true); // 克隆一个规格项
		
		// 兼容处理
		tpl.find('input').each(function(index, element) {
            tpl.find('input[item="'+$(element).attr('item')+'"]').attr('name', $(this).attr('item')+'[]');
        });
		
        tpl.attr('ectype', 'data'); // 赋值一个ectype与规格项模板区别
        item.spec_1 && tpl.find('*[item="spec_1"]').val(item.spec_1);
        item.spec_2 && tpl.find('*[item="spec_2"]').val(item.spec_2);
		item.spec_image && tpl.find('.spec_image').find('i').html('<img src="'+url_format(item.spec_image)+'" height="20" width="20"/>');
		item.spec_image && tpl.find('.spec_image').find('input[name="image"]').val('url_format(item.spec_image)');
        //tpl.find('.filePicker').attr('id', 'filePicker'+i);
		
		tpl.find('*[item="price"]').val(item.price);
        tpl.find('*[item="stock"]').val(item.stock);
        tpl.find('*[item="sku"]').val(item.sku);
        tpl.find('*[item="spec_id"]').val(item.spec_id);
        tpl.show();
        d_spec_item.before(tpl); // 将克隆的规格项放到模板前面，新增的规格项能按正序排列
    });
}

function save_spec()
{
        var bak_spec =  SPEC; // 备份
        /* 保存规格名称 */
        var spec_name_1 = $.trim($('#dialog_object_spec_editor').find('*[col="spec_name_1"]').val());
        var spec_name_2 = $.trim($('#dialog_object_spec_editor').find('*[col="spec_name_2"]').val());

        /* 规格名称是否重复和为空 */
        if(!spec_name_1 && !spec_name_2){
            layer.open({content:lang.spec_name_required});
            return;
        }else{
            if(spec_name_1 == spec_name_2){
                layer.open({content:lang.duplicate_spec_name + ' ' + '[' + spec_name_1+ ']'});
                return;
            }
        }
        SPEC = {};
        SPEC.spec_name_1 = spec_name_1;
        SPEC.spec_name_2 = spec_name_2;

        /* 保存规格数量 */
        if(SPEC.spec_name_1 && SPEC.spec_name_2){
            SPEC.spec_qty = "2";
        }else if(!SPEC.spec_name_1 && !SPEC.spec_name_2){
            SPEC.spec_qty = "0"; // 这种情况不会出现，因前面为空检查已经返回
        }else{
            SPEC.spec_qty = "1";
        }

        /* 保存规格项 */
        var arr_spec_name = new Array(); // 累积规格项名称。检查重复
        var spec_duplicate = new Array(); // 重复的规格项
        var price_error = new Array();
        var complate = true; // 是否完成
        SPEC.specs = [];
        $('#dialog_object_spec_editor').find('*[ectype="data"]').each(function(){
            var spec_1 = SPEC.spec_name_1 ? $.trim($(this).find('*[item="spec_1"]').val()) : null;
            var spec_2 = SPEC.spec_name_2 ? $.trim($(this).find('*[item="spec_2"]').val()) : null;
            var price = $.trim($(this).find('*[item="price"]').val());
            var stock = $.trim($(this).find('*[item="stock"]').val());
            var sku = $.trim($(this).find('*[item="sku"]').val());
            var spec_id = $.trim($(this).find('*[item="spec_id"]').val());
			var spec_image = $.trim($(this).find('.spec_image').find('i').find('img').attr('src'));

            var valid = (spec_1 || spec_2) ? true : false; // 该行数据是否有效

            if(SPEC.spec_qty == 1){ // 一个规格
                var spec_pos = SPEC.spec_name_1 ? 1 : 2;
                eval('if(spec_' + spec_pos + ' || (!spec_' + spec_pos + ' && !price && !stock && !sku)){}else{complate = false;}');
            }else{ // 两个规格
                if((spec_1 && spec_2) || (!spec_1 && !spec_2 && !price && !stock && !sku)){

                }else{
                    complate = false;
                }
            }

            var item = [spec_1,spec_2].join(';');
            if($.inArray(item, arr_spec_name) > -1){
                if($.inArray(item, spec_duplicate) == -1){
                    spec_duplicate.push(item);
                }
            }else{
                item != ';' && arr_spec_name.push(item);
            }
            /* 判断价格非法 */
            if(isNaN(price) || price <0 || !price){
                valid && price_error.push(item);
            }
            item != ';' && SPEC.specs.push({
                'spec_1':spec_1,
                'spec_2':spec_2,
                'price': Number(price).toFixed(2),
                'stock': Number(stock),
                'sku':sku,
				'spec_image':spec_image,
                'spec_id':spec_id
            });
        });
        if(arr_spec_name.length == 0){
                complate = false;
        }
        if(complate == false){
            layer.open({content:lang.spec_not_complate});
            SPEC = {};
            SPEC = bak_spec; // 还原备份
            return;
        }
        if(spec_duplicate.length>0){
            var spec_msg = '';
            $.each(spec_duplicate,function(i,val){
                spec_msg += val + ' ';
            });

            layer.open({content:lang.duplicate_spec + ' ' + spec_msg});
            SPEC = {};
            SPEC = bak_spec; // 还原备份
            return;
        }
        /* 判断价格 */
        if(price_error.length>0){
            var msg = lang.follow_rules_invalid + ' ';
            $.each(price_error,function(i,val){
                msg += val + ' ';
            });

            layer.open({content:msg});
            SPEC = {};
            SPEC = bak_spec; // 还原备份
            return;
        }
	
        // 更新显示规格项（移动端不需要）
        spec_update();
		
	return true;
}