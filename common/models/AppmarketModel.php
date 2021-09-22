<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\Url;

use common\models\ApprenewalModel;

use common\library\Language;
use common\library\Timezone;

/**
 * @Id AppmarketModel.php 2018.5.7 $
 * @author mosir
 */

class AppmarketModel extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%appmarket}}';
    }
	
	public static function getList()
	{
		return ['limitbuy', 'meal', 'fullfree', 'fullprefer', 'exclusive', 'distribute'];
	}
	
	public static function getAppList($code = '')
	{
		$all = array();
		foreach(self::getList() as $key => $val) {
			$all[$key] = array('key' => $val, 'value' => Language::get($val));
		}
		
		if(!empty($code)) {
			foreach($all as $key => $val) {
				if($val['key'] == $code) {
					return $val;
				}
			}
			return '';
		}
		return $all;
	}
	public static function getPeriodList($code = 0)
	{
		$all = array (
			array('key' => 1, 'value' => '1个月'),
			array('key' => 2, 'value' => '2个月'),
			array('key' => 3, 'value' => '3个月'),
			array('key' => 4, 'value' => '4个月'),
			array('key' => 5, 'value' => '5个月'),
			array('key' => 6, 'value' => '半年'),
			array('key' => 7, 'value' => '7个月'),
			array('key' => 8, 'value' => '8个月'),
			array('key' => 9, 'value' => '9个月'),
			array('key' => 10, 'value' => '10个月'),
			array('key' => 11, 'value' => '11个月'),
			array('key' => 12, 'value' => '一年'),
			array('key' => 24, 'value' => '二年'),
			array('key' => 36, 'value' => '三年'),
		);
		if(!empty($code)) {
			foreach($all as $key => $val) {
				if($val['key'] == $code) {
					return $val;
				}
			}
			return '';
		}
		return $all;
	}
	
	public static function getCheckAvailableInfo($appid = '', $store_id = 0)
	{
		$result = true;
		
		if(!($appmarket = parent::find()->select('purchase')->where(['status' => 1, 'appid' => $appid])->one())) {
			$result = array('msg' => Language::get('appDisAvailable'), 'result_code' => 0);
		}
		else
		{
			// 如果是订购模式，需要订购后才可以使用，不是订购模式，可直接使用
			if($appmarket->purchase) 
			{
				// 在此处判断用户是否购买了该营销工具
				if(($apprenewal = ApprenewalModel::find()->select('expired')->where(['appid' => $appid, 'userid' => $store_id])->orderBy(['rid' => SORT_DESC])->one())) {
					
					// 如果购买了，那么检查是否到期
					if($apprenewal->expired <= Timezone::gmtime()) {
						
						// 如果到期了，且应用可用
						if($appmarket->status) {
							$result = array('msg' => sprintf(Language::get('appHasExpired'), Url::toRoute(['appmarket/view', 'appid' => $appid])), 'result_code' => -1);
						}
					}
				} 
				// 如果没有购买过
				else {

					$result = array('msg' => sprintf(Language::get('appHasNotBuy'), Url::toRoute(['appmarket/view', 'appid' => $appid])), 'result_code' => -2);
				}
			}
		}
		return $result;
	}
}
