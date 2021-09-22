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
 * @Id RefundExportForm.php 2018.8.29 $
 * @author mosir
 */
class RefundExportForm extends Model
{
	public $errors = null;
	
	public static function download($list)
	{
		// 文件数组
		$record_xls = array();		
		$lang_bill = array(
			'refund_sn' 			=> '退款单编号',
    		'buyer_name' 			=> '买家',
    		'store_name' 			=> '卖家',
			'total_fee' 			=> '交易金额',
			'refund_goods_fee' 		=> '退款金额',
    		'refund_shipping_fee' 	=> '退运费',
			'created' 				=> '申请时间',
			'status'				=> '退款状态',
			'intervene' 			=> '客服介入',
		);
		$record_xls[] = array_values($lang_bill);
		$folder = 'REFUND_'.Timezone::localDate('Ymdhis', Timezone::gmtime());
		
		$record_value = array();
		foreach($lang_bill as $key => $val) 
		{
			$record_value[$key] = '';
		}

		$amount = $quantity = 0;
		foreach($list as $key => $val)
    	{
			$quantity++;
			$amount += floatval($val['refund_total_fee']);
			
			$record_value['refund_sn']	= $val['refund_sn'];
			$record_value['buyer_name']	= $val['buyer_name'];
			$record_value['store_name']	= $val['store_name'];
			$record_value['total_fee']	= $val['total_fee'];
			$record_value['refund_goods_fee'] = $val['refund_goods_fee'];
			$record_value['refund_shipping_fee'] = $val['refund_shipping_fee'];
			$record_value['created'] = Timezone::localDate('Y/m/d H:i:s',$val['created']);
			$record_value['status']	= Language::get('REFUND_'.strtoupper($val['status']));
			$record_value['intervene'] =	$val['intervene'] ? '是':'否' ;
        	$record_xls[] = $record_value;
    	}
		$record_xls[] = array('refund_sn' => '');// empty line
		$record_xls[] = array('refund_sn' => sprintf('退款总数：%s笔，退款总额：%s元', $quantity, $amount));
		
		return \common\library\Page::export([
			'models' 	=> $record_xls, 
			'fileName' 	=> $folder,
		]);
	}
}
