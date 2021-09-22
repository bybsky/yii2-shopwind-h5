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

use common\models\CouponModel;
use common\models\CouponsnModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Timezone;
use common\library\Page;

/**
 * @Id CouponController.php 2018.11.20 $
 * @author luckey
 */

class CouponController extends \common\controllers\BaseMallController
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
		$this->params = ArrayHelper::merge($this->params, Page::getAssign('mall'));
	}
	
	/* 领券中心 */
	public function actionIndex()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['page', 'pageper']);
		
		if(Yii::$app->request->isAjax)
		{
			$query = CouponModel::find()->alias('c')->select('c.*,s.store_name,s.store_logo')->joinWith('store s', false)->where(['clickreceive' => 1, 'available' => 1])->andWhere(['>', 'c.end_time', Timezone::gmtime()])->andWhere(['or', ['total' => 0], ['and', ['>', 'total', 0], ['>', 'surplus', 0]]])->orderBy(['coupon_id' => SORT_DESC]);
			$page = Page::getPage($query->count(), $post->pageper);
			$coupons = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
			foreach($coupons as $key => $val) 
			{
				//if($val['total'] > 0){
					//$coupons[$key]['surratio'] = round((1 - $val['surplus'] / $val['total']) * 100, 0);
				//}
				$coupons[$key]['store_logo'] = empty($val['sotre_logo']) ? Yii::$app->params['default_store_logo'] : $val['sotre_logo'];
				$coupons[$key]['start_time'] = Timezone::localDate('Y-m-d', $val['start_time']);
			    $coupons[$key]['end_time'] = Timezone::localDate('Y-m-d', $val['end_time']);
			}

			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			return array('result' => array_values($coupons), 'totalPage' => $page->getPageCount());
		}
		else
		{
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.infinite.js');
			$this->params['infiniteParams'] = json_encode(ArrayHelper::toArray($post));
		
			$this->params['page'] = Page::seo(['title' => Language::get('coupon_list')]);
			return $this->render('../coupon.index.html', $this->params);
		}
	}
	
	/* 浏览优惠券 */
	public function actionSearch()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['store_id']);
		$coupons = CouponModel::find()->where(['clickreceive' => 1, 'available' => 1, 'store_id' => $post->store_id])->andWhere(['>', 'end_time', Timezone::gmtime()])->andWhere(['or', ['total' => 0], ['and', ['>', 'total', 0], ['>', 'surplus', 0]]])->orderBy(['coupon_id' => SORT_DESC])->asArray()->all();
		$this->params['coupons'] = $coupons;
		
		$this->params['page'] = Page::seo(['title' => Language::get('coupon_list')]);
		return $this->render('../coupon.search.html', $this->params);
	}
	
	/* 领取优惠券 */
	public function actionReceive()
	{
		if(Yii::$app->user->isGuest) {
			return Message::warning(Language::get('login_please'));
		}
		
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);
		if(!($coupon = CouponModel::find()->where(['coupon_id' => $post->id, 'clickreceive' => 1, 'available' => 1])->andWhere(['>', 'end_time', Timezone::gmtime()])->one())) {
			return Message::warning(Language::get('no_such_coupon'));
		}
		if($coupon->store_id == Yii::$app->user->id) {
			return Message::warning(Language::get('not_receive_self'));
		}
		if($coupon->total > 0 && $coupon->surplus <= 0) {
			return Message::warning(Language::get('coupon_receive_all'));
		}

		if(CouponsnModel::find()->where(['userid' => Yii::$app->user->id, 'coupon_id' => $coupon->coupon_id])->andWhere(['>', 'remain_times', 0])->exists()) {
			return Message::warning(Language::get('coupon_has_receive'));
		}
		
		$model = new CouponsnModel();
		$model->coupon_sn = $model->createRandom();
		$model->coupon_id = $coupon->coupon_id;
		$model->remain_times = 1;
		$model->userid = Yii::$app->user->id;
		if(!$model->save()) {
			return Message::warning($model->errors);
		}
		if(!CouponModel::updateAllCounters(['surplus' => -1],['coupon_id' => $coupon->coupon_id])){
			$model->delete();
			return Message::warning(Language::get('receive_fail'));
		}
		return Message::display(Language::get('receive_success'));
	}
}