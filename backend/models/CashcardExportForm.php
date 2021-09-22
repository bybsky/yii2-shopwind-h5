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

/*
 * @Id CashcardExportForm.php 2018.8.3 $
 * @author mosir
 */
class CashcardExportForm extends Model
{
	public $errors = null;
	
	public static function download($list)
	{
		// 文件数组
		$record_xls = array();		
		$lang_bill = array(
			'name' 			=> '卡名称',
			'cardNo'		=> '卡号',
			'password'		=> '密码',
			'money'			=> '卡金额',
			'add_time' 		=> '生成时间',
			'expire_time' 	=> '过期时间',
			'printed'		=> '制卡状态',
			'username'		=> '使用者',
			'active_time'	=> '激活时间',
		);
		$record_xls[] = array_values($lang_bill);
		$folder = 'Cashcard_'.Timezone::localDate('Ymdhis', Timezone::gmtime());
		
		$record_value = array();
		foreach($lang_bill as $key => $val) 
		{
			$record_value[$key] = '';
		}
		foreach($list as $key => $val)
    	{
			$record_value['name'] = $val['name'];
			$record_value['cardNo']	= $val['cardNo'];
			$record_value['password'] = $val['password'];
			$record_value['money'] = $val['money'];
			$record_value['add_time'] = Timezone::localDate('Y/m/d H:i:s', $val['add_time']);
			$record_value['expire_time'] = Timezone::localDate('Y/m/d H:i:s', $val['expire_time']);
			$record_value['printed'] = $val['printed'] == 0 ? '未制卡' : '已制卡';
			$record_value['username'] = $val['username'];
			$record_value['active_time'] = Timezone::localDate('Y/m/d H:i:s', $val['active_time']);
			
        	$record_xls[] = $record_value;
    	}
		
		return \common\library\Page::export([
			'models' 	=> $record_xls, 
			'fileName' 	=> $folder,
		]);
	}
}
