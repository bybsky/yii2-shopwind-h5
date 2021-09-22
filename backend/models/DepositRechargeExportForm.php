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
 * @Id DepositRechargeExportForm.php 2018.8.3 $
 * @author mosir
 */
class DepositRechargeExportForm extends Model
{
	public $errors = null;
	
	public static function download($list)
	{
		// 文件数组
		$record_xls = array();		
		$lang_bill = array(
			'add_time' 		=> '创建时间',
			'tradeNo' 		=> '交易号',
    		'orderId' 		=> '商户订单号',
			'username' 		=> '用户名',
			'name' 			=> '名称',
			'amount' 		=> '金额',
			'status' 		=> '状态',
			'examine' 		=> '操作员',
		);
		$record_xls[] = array_values($lang_bill);
		$folder = 'RECHARGE_'.Timezone::localDate('Ymdhis', Timezone::gmtime());
		
		$record_value = array();
		foreach($lang_bill as $key => $val) 
		{
			$record_value[$key] = '';
		}
		foreach($list as $key => $val)
    	{
			$record_value['add_time']	= Timezone::localDate('Y/m/d H:i:s', $val['add_time']);
			$record_value['tradeNo']	= $val['tradeNo']; 
			$record_value['orderId']	= $val['orderId'];
			$record_value['username']	= $val['username'];
			$record_value['name']		= Language::get('recharge');
			$record_value['amount']		= $val['amount']; 
			$record_value['status']		= Language::get(strtolower($val['status']));
			$record_value['examine']	= $val['examine'];
        	$record_xls[] = $record_value;
    	}
		
		return \common\library\Page::export([
			'models' 	=> $record_xls, 
			'fileName' 	=> $folder,
		]);
	}
}
