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
 * @Id GuideshopExportForm.php 2020.2.10 $
 * @author mosir
 */
class GuideshopExportForm extends Model
{
	public $errors = null;
	
	public static function download($list)
	{
		// 文件数组
		$record_xls = array();		
		$lang_bill = array(
    		'owner' 		=> '团长姓名',
			'phone_mob'		=> '团长电话',
			'name'			=> '门店名称',
    		'address' 		=> '门店地址',
			'status'		=> '门店状态',
			'created'  		=> '申请时间'
		);
		$record_xls[] = array_values($lang_bill);
		$folder = 'GUIDESHOP_'.Timezone::localDate('Ymdhis', Timezone::gmtime());
		
		$record_value = array();
		foreach($lang_bill as $key => $val) 
		{
			$record_value[$key] = '';
		}

		foreach($list as $key => $val)
    	{
			$record_value['owner'] 		= $val['owner'];
			$record_value['phone_mob']	= $val['phone_mob']; 
			$record_value['name']		= $val['name'];
			$record_value['address']	= $val['region_name'] . $val['address'];
			$record_value['status']		= self::getStatus($val['status']);
			$record_value['created']	= Timezone::localDate('Y-m-d', $val['created']);
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
}
