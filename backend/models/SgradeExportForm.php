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

use common\library\Language;
use common\library\Timezone;

/**
 * @Id SgradeExportForm.php 2018.8.17 $
 * @author mosir
 */
class SgradeExportForm extends Model
{
	public $errors = null;
	
	public static function download($list)
	{
		// 文件数组
		$record_xls = array();		
		$lang_bill = array(
			'grade_id' 		=> '等级ID',
    		'grade_name' 	=> '等级名称',
    		'goods_limit' 	=> '允许发布商品数',
			'space_limit'	=> '允许上传空间大小（MB）',
			'skins_limit'   => '可用模板数',
    		'charge'		=> '收费标准',
    		'need_confirm'	=> '是否需要审核',
			'sort_order'	=> '排序'
		);
		$record_xls[] = array_values($lang_bill);
		$folder = 'SGRADE_'.Timezone::localDate('Ymdhis', Timezone::gmtime());
		
		$record_value = array();
		foreach($lang_bill as $key => $val) 
		{
			$record_value[$key] = '';
		}

		foreach($list as $key => $val)
    	{
			$record_value['grade_id'] 	= $val['grade_id'];
			$record_value['grade_name']	= $val['grade_name']; 
			$record_value['goods_limit']= $val['goods_limit'] ? $val['goods_limit'] : Language::get('no_limit');
			$record_value['space_limit']= $val['space_limit'] ? $val['space_limit'] : Language::get('no_limit');
			$record_value['skins_limit']= count(explode(',', $val['skins']));
			$record_value['charge']= count(explode(',', $val['charge']));
			$record_value['need_confirm']= $val['need_confirm'] == 0 ? '否' : '是';
			$record_value['sort_order']= $val['sort_order'];
        	$record_xls[] = $record_value;
    	}
		
		return \common\library\Page::export([
			'models' 	=> $record_xls, 
			'fileName' 	=> $folder,
		]);
	}
}
