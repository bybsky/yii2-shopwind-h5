<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace backend\models;

use Yii;
use yii\base\Model;

use common\models\GcategoryModel;

use common\library\Timezone;

/**
 * @Id GoodsExportForm.php 2018.8.9 $
 * @author mosir
 */
class GoodsExportForm extends Model
{
	public $errors = null;
	
	public static function download($list)
	{
		// 文件数组
		$record_xls = array();		
		$lang_bill = array(
			'goods_id' 		=> 	'ID',
			'goods_name' 	=> 	'商品名称',
    		'price' 		=> 	'价格',
    		'store_name' 	=> 	'店铺名称',
			'brand' 		=>  '品牌',
    		'cate_name' 	=> 	'所属分类',
    		'if_show' 		=> 	'上架',
    		'closed' 		=> 	'禁售',
			'views'  		=>  '浏览数',
		);
		$record_xls[] = array_values($lang_bill);
		$folder = 'GOODS_'.Timezone::localDate('Ymdhis', Timezone::gmtime());
		
		$record_value = array();
		foreach($lang_bill as $key => $val) 
		{
			$record_value[$key] = '';
		}

		$amount = $quantity = 0;
		foreach($list as $key => $val)
    	{
			$quantity++;
			$amount += floatval($val['price']);
			
			$record_value['goods_id']	= $val['goods_id'];
			$record_value['goods_name']	= $val['goods_name'];
			$record_value['price']		= $val['price'];
			$record_value['store_name']	= $val['store_name'];
			$record_value['brand']		= $val['brand'];
			$record_value['cate_name']	= GcategoryModel::formatCateName($val['cate_name'],false, '/');
			$record_value['if_show']	= $val['if_show'] == 0 ? '否' : '是';
			$record_value['closed']		= $val['closed'] == 0 ? '否' : '是';
			$record_value['views']		= intval($val['views']);
        	$record_xls[] = $record_value;
    	}
		$record_xls[] = array('goods_name' => '');// empty line
		$record_xls[] = array('goods_name' => sprintf('商品总数：%s笔，商品总额：%s元', $quantity, $amount));

		return \common\library\Page::export([
			'models' 	=> $record_xls, 
			'fileName' 	=> $folder,
		]);
	}
}
