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
 * @Id DepositAccountExportForm.php 2018.8.3 $
 * @author mosir
 */
class DepositAccountExportForm extends Model
{
	public $errors = null;
	
	public static function download($list)
	{
		// 文件数组
		$record_xls = array();		
		$lang_bill = array(
			'account' 		=> 	'账户名',
			'username' 		=> 	'会员名',
    		'real_name'		=> 	'真实姓名',
			'money' 		=> 	'金钱',
			'frozen' 		=> 	'冻结',
    		'pay_status' 	=> 	'开启余额支付',
			'add_time' 		=> 	'创建时间',
		);
		$record_xls[] = array_values($lang_bill);
		$folder = 'ACCOUNT_'.Timezone::localDate('Ymdhis', Timezone::gmtime());
		
		$record_value = array();
		foreach($lang_bill as $key => $val) 
		{
			$record_value[$key] = '';
		}

		foreach($list as $key => $val)
    	{
			$record_value['account']		= $val['account'];
			$record_value['username']		= $val['username'];
			$record_value['real_name']		= $val['real_name'];
			$record_value['money']			= $val['money'];
			$record_value['frozen']			= $val['frozen'];
			$record_value['pay_status']		= $val['pay_status'];
			$record_value['add_time']		= Timezone::localDate('Y/m/d H:i:s', $val['add_time']);
        	$record_xls[] = $record_value;
    	}
		
		return \common\library\Page::export([
			'models' 	=> $record_xls, 
			'fileName' 	=> $folder,
		]);
	}
}
