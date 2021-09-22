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
 * @Id DepositDrawExportForm.php 2018.8.3 $
 * @author mosir
 */
class DepositDrawExportForm extends Model
{
	public $errors = null;
	
	public static function download($list)
	{
		// 文件数组
		$record_xls = array();		
		$lang_bill = array(
			'add_time' 			=> '申请时间',
			'tradeNo'			=> '交易号',
			'orderId'			=> '商户订单号',
			'account_name'		=> '收款人姓名',
			'num' 				=> '收款人银行账号',
			'bank_name' 		=> '开户行',
			'amount'			=> '金额',
			'status'			=> '状态',
			'remark'			=> '提现备注',
		);
		$record_xls[] = array_values($lang_bill);
		$folder = 'DRAW_'.Timezone::localDate('Ymdhis', Timezone::gmtime());
		
		$record_value = array();
		foreach($lang_bill as $key => $val) 
		{
			$record_value[$key] = '';
		}
		foreach($list as $key => $val)
    	{
			$record_value['add_time']	= Timezone::localDate('Y/m/d H:i:s', $val['add_time']);
			$record_value['tradeNo']	= $val['tradeNo'];
			$record_value['orderId']    = $val['orderId'];
			$card_info = unserialize($val['card_info']);
			$record_value['account_name'] 	= $card_info['account_name'];
			$record_value['num']			= $card_info['num'];
			$record_value['bank_name']		= $card_info['bank_name'] . $card_info['open_bank'];
			
			$record_value['amount']			= $val['amount'];
			$record_value['status']  		= Language::get(strtolower($val['status']));
			$record_value['remark']  		= $val['buyer_remark'];
			
        	$record_xls[] = $record_value;
    	}
		
		return \common\library\Page::export([
			'models' 	=> $record_xls, 
			'fileName' 	=> $folder,
		]);
	}
}
