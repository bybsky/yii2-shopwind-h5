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

use common\library\Timezone;

/**
 * @Id RecommendExportForm.php 2018.8.14 $
 * @author mosir
 */
class RecommendExportForm extends Model
{
	public $errors = null;
	
	public static function download($list)
	{
		// 文件数组
		$record_xls = array();		
		$lang_bill = array(
			'recom_id' 		=> 'ID',
			'recom_name' 	=> '推荐类型名称',
			'goods_count'  	=> '商品数',
		);
		$record_xls[] = array_values($lang_bill);
		$folder = 'RECOMMEND_'.Timezone::localDate('Ymdhis', Timezone::gmtime());
		
		$record_value = array();
		foreach($lang_bill as $key => $val) 
		{
			$record_value[$key] = '';
		}

		foreach($list as $key => $val)
    	{
			$record_value['recom_id'] = $val['recom_id'];
			$record_value['recom_name'] = $val['recom_name'];
			$record_value['goods_count'] = count($val['recommendGoods']);
        	$record_xls[] = $record_value;
    	}
		
		return \common\library\Page::export([
			'models' 	=> $record_xls, 
			'fileName' 	=> $folder,
		]);
	}
}
