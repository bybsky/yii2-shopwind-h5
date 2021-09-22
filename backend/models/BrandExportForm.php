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
use common\library\Page;

/**
 * @Id BrandExportForm.php 2018.8.9 $
 * @author mosir
 */
class BrandExportForm extends Model
{
	public $errors = null;
	
	public static function download($list)
	{
		// 文件数组
		$record_xls = array();		
		$lang_bill = array(
			'brand_id' 		=> '品牌ID',
    		'brand_name' 	=> '品牌名称',
    		'brand_logo' 	=> '品牌标识',
    		'recommended' 	=> '推荐',
    		'tag' 			=> '品牌类别',
		);
		$record_xls[] = array_values($lang_bill);
		$folder = 'BRAND_'.Timezone::localDate('Ymdhis', Timezone::gmtime());
		
		$record_value = array();
		foreach($lang_bill as $key => $val) {
			$record_value[$key] = '';
		}

		foreach($list as $key => $val)
    	{
			$record_value['brand_id'] 	= $val['brand_id'];
			$record_value['brand_name']	= $val['brand_name']; 
			$record_value['brand_logo']	= Page::urlFormat($val['brand_logo']);
			$record_value['recommended']= ($val['recommended'] == 1) ? '是' : '否';
			$record_value['tag']		= $val['tag'];
        	$record_xls[] = $record_value;
    	}
		
		return \common\library\Page::export([
			'models' 	=> $record_xls, 
			'fileName' 	=> $folder,
		]);
	}
}
