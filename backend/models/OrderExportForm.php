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
use common\library\Def;

/**
 * @Id OrderExportForm.php 2018.8.2 $
 * @author mosir
 */
class OrderExportForm extends Model
{
	public $errors = null;
	
	public static function download($list)
	{
		// 文件数组
		$record_xls = array();		
		$lang_bill = array(
			'seller_name' 	=> '店铺名称',
    		'order_sn' 		=> '订单编号',
    		'add_time' 		=> '下单时间',
    		'buyer_name' 	=> '买家名称',
    		'order_amount' 	=> '订单总额',
    		'payment_name' 	=> '付款方式',
			'consignee' 	=> '收货人姓名',
    		'consignee_address' => '收货人地址',
			'consignee_phone' 	=> '收货人电话',
			'pay_message'	=> '买家留言',
			'status'		=> '订单状态',
			'express_no'	=> '快递单号',
			'postscript'	=> '备注',
		);
		$record_xls[] = array_values($lang_bill);
		$folder = 'ORDER_'.Timezone::localDate('Ymdhis', Timezone::gmtime());
		
		$record_value = array();
		foreach($lang_bill as $key => $val) 
		{
			$record_value[$key] = '';
		}

		$amount = $quantity = 0;
		foreach($list as $key => $val)
    	{
			$quantity++;
			$amount += floatval($val['order_amount']);
			
			$record_value['seller_name'] 	= $val['seller_name'];
			$record_value['order_sn']		= $val['order_sn']; 
			$record_value['add_time']		= Timezone::localDate('Y/m/d H:i:s', $val['add_time']);
			$record_value['buyer_name']		= $val['buyer_name'];
			$record_value['order_amount']	= $val['order_amount'];
			$record_value['payment_name']	= $val['payment_name'];
			$record_value['consignee']		= $val['consignee'];
			$record_value['consignee_address']	= $val['region_name'].$val['address'];
			$record_value['consignee_phone']	= $val['phone_mob'];
			$record_value['pay_message']   	= $val['pay_message'];
			$record_value['status']			= Def::getOrderStatus($val['status']);
			$record_value['express_no']		= $val['express_no'];
			$record_value['postscript']		= $val['postscript'];
        	$record_xls[] = $record_value;
    	}
		$record_xls[] = array('seller_name' => '');// empty line
		$record_xls[] = array('seller_name' => sprintf('订单总数：%s笔，订单总额：%s元', $quantity, $amount));
		
		return \common\library\Page::export([
			'models' 	=> $record_xls, 
			'fileName' 	=> $folder,
		]);
	}
}
