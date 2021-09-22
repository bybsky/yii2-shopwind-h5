<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\controllers;

use Yii;
use yii\helpers\ArrayHelper;

use common\models\CouponsnModel;
use common\models\StoreModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;
use common\library\Timezone;

/**
 * @Id My_couponController.php 2018.11.20 $
 * @author luckey
 */

class My_couponController extends \common\controllers\BaseUserController
{
	/**
	 * 初始化
	 * @var array $view 当前视图
	 * @var array $params 传递给视图的公共参数
	 */
	public function init()
	{
		parent::init();
		$this->view  = Page::setView('mall');
		$this->params = ArrayHelper::merge($this->params, Page::getAssign('user'));
	}
	
    public function actionIndex()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['page', 'pageper']);
		if(Yii::$app->request->isAjax)
		{
			$query = CouponsnModel::find()->alias('cs')->select('cs.coupon_sn,cs.remain_times,c.*')->joinWith('coupon c',false)->where(['userid' => Yii::$app->user->id]);
			$page = Page::getPage($query->count(), $post->pageper);
			$coupons = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
			foreach($coupons as $key => $val)
			{
				$coupons[$key]['valid'] = 0;
				if($val['store_id']){
					$coupons[$key] += (array)StoreModel::find()->select('store_name,store_logo')->where(['store_id' => $val['store_id']])->asArray()->one();
				}
				if(($val['remain_times'] > 0) && ($val['end_time'] == 0 || $val['end_time'] > Timezone::gmtime())) {
					$coupons[$key]['valid'] = 1;
				}
				empty($val['store_logo']) && $coupons[$key]['store_logo'] = Yii::$app->params['default_store_logo'];
				$val['end_time'] > 0 && $coupons[$key]['end_time'] = Timezone::localDate('Y-m-d', $val['end_time']);
			}
			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			return array('result' => array_values($coupons), 'totalPage' => $page->getPageCount());
		}
		else
		{
			$this->params['_foot_tags'] = Resource::import([
				'script' => 'jquery.plugins/jquery.form.js,weui/js/jquery-weui.min.js,jquery.plugins/jquery.infinite.js',
				'style' => 'weui/lib/weui.min.css,weui/css/jquery-weui.min.css'
			]);
			$this->params['infiniteParams'] = json_encode(ArrayHelper::toArray($post));
			
			$this->params['page'] = Page::seo(['title' => Language::get('coupon_list')]);
			return $this->render('../my_coupon.index.html', $this->params);
		}
		
	}

	public function actionDelete()
    {
        $post = Basewind::trimAll(Yii::$app->request->get(), true);
		
		if(!$post->id){
			return Message::warning(Language::get('no_such_item'));
		}
		
		if(!CouponsnModel::deleteAll(['and', ['userid' => Yii::$app->user->id], ['in', 'coupon_sn', explode(',', $post->id)]])) {
			return Message::warning(Language::get('drop_fail'));	
		}
        return Message::display(Language::get('drop_ok'),['my_coupon/index']);
    }
	

}