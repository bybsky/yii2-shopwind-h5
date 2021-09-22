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
use common\library\Language;
use common\library\Def;

/**
 * @Id StoreExportForm.php 2018.8.10 $
 * @author mosir
 */
class StoreExportForm extends Model
{
	public $errors = null;
	
	public static function download($list)
	{
		// 文件数组
		$record_xls = array();		
		$lang_bill = array(
			'store_name'	=> '店铺名称',
    		'username' 		=> '用户名',
    		'owner_name' 	=> '店主姓名',
			'phone_tel'		=> '手机/电话',
    		'region_name' 	=> '所在地区',
    		'sgrade' 		=> '店铺等级',
			'recommended'   => '推荐',
			'state'			=> '状态',
			'add_time'  	=> '添加时间'
		);
		$record_xls[] = array_values($lang_bill);
		$folder = 'STORE_'.Timezone::localDate('Ymdhis', Timezone::gmtime());
		
		$record_value = array();
		foreach($lang_bill as $key => $val) 
		{
			$record_value[$key] = '';
		}

		foreach($list as $key => $val)
    	{
			$record_value['store_name'] = $val['store_name'];
			$record_value['username']	= $val['username']; 
			$record_value['owner_name']	= $val['owner_name'];
			$record_value['phone_tel']	= $val['phone_mob'].'/'.($val['tel'] ? $val['tel'] : $val['phone_tel']);
			$record_value['region_name']= $val['region_name'];
			$record_value['sgrade']		= self::getSgrade($val['sgrade']);
			$record_value['recommended']= $val['recommended'] ? '是' : '否';
			$record_value['state']		= self::getStatus($val['state']);
			$record_value['add_time']	= Timezone::localDate('Y-m-d', $val['add_time']);
        	$record_xls[] = $record_value;
    	}
		
		return \common\library\Page::export([
			'models' 	=> $record_xls, 
			'fileName' 	=> $folder,
		]);
	}
	
	private static function getStatus($status = null)
	{
		$result = array(
            Def::STORE_APPLYING  => Language::get('applying'),
			Def::STORE_NOPASS	 => Language::get('nopass'),
            Def::STORE_OPEN      => Language::get('open'),
            Def::STORE_CLOSED    => Language::get('close'),
        );
		if($status !== null) {
			return isset($result[$status]) ? $result[$status] : '';
		}
		return $result;		
	}
	
	private static function getSgrade($grade_id = 0)
	{
		$sgrades = \common\models\SgradeModel::getOptions();
		return $sgrades[$grade_id] ? $sgrades[$grade_id] : '';
	}	
}
