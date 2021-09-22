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
 * @Id UserExportForm.php 2018.7.30 $
 * @author mosir
 */
class UserExportForm extends Model
{
	public $errors = null;
	
	public static function download($list)
	{
		// 文件数组
		$record_xls = array();		
		$lang_bill = array(
			'username' 		=> '会员名',
    		'real_name' 	=> '真实姓名',
    		'email' 		=> '电子邮箱',
			'phone_mob' 	=> '手机号码',
    		'im_qq' 		=> 'QQ',
    		'im_ww' 		=> '旺旺',
    		'create_time' 	=> '注册时间',
			'last_login' 	=> '最后登录时间',
			'last_ip' 		=> '最后登录ip',
    		'logins' 		=> '登录次数',
		);
		$record_xls[] = array_values($lang_bill);
		$folder ='USER_'.Timezone::localDate('Ymdhis', Timezone::gmtime());
		
		$record_value = array();
		foreach($lang_bill as $key => $val)
		{
			$record_value[$key] = '';
		}

		foreach($list as $key => $val)
    	{
			$record_value['username']	= $val->username;
			$record_value['real_name']	= $val->real_name;
			$record_value['email']		= $val->email;
			$record_value['phone_mob']	= $val->phone_mob;
			$record_value['im_qq']		= $val->im_qq;
			$record_value['im_ww']		= $val->im_ww;
			$record_value['create_time']= Timezone::localDate('Y-m-d H:i:s', $val->create_time);
			$record_value['last_login']	= Timezone::localDate('Y/m/d H:i:s', $val->last_login);
			$record_value['last_ip']	= $val->last_ip;
			$record_value['logins']   	= $val->logins;
        	$record_xls[] = $record_value;
    	}
		return \common\library\Page::export([
			'models' 	=> $record_xls, 
			'fileName' 	=> $folder,
		]);
	}
}
