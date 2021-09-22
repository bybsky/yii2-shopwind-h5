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
 * @Id IntegralExportForm.php 2018.8.6 $
 * @author mosir
 */
class IntegralExportForm extends Model
{
	public $errors = null;
	
	public static function download($list)
	{
		// 文件数组
		$record_xls = array();		
		$lang_bill = array(
			'userid' 	=> 'ID',
    		'username' 	=> '用户名',
			'amount' 	=> '积分',
		);
		$record_xls[] = array_values($lang_bill);
		$folder = 'INTEGRAL_'.Timezone::localDate('Ymdhis', Timezone::gmtime());
		
		$record_value = array();
		foreach($lang_bill as $key => $val) 
		{
			$record_value[$key] = '';
		}

		$amount = $quantity = 0;
		foreach($list as $key => $val)
    	{
			$record_value['userid']		= $val['userid'];
			$record_value['username']	= $val['username'];
			$record_value['amount']		= floatval($val['amount']);
        	$record_xls[] = $record_value;
    	}
		
		return \common\library\Page::export([
			'models' 	=> $record_xls, 
			'fileName' 	=> $folder,
		]);
	}
}
