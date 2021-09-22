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
use yii\helpers\Url;

use common\models\UserModel;
use common\models\CouponModel;
use common\models\CouponsnModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;
use common\library\Timezone;

/**
 * @Id Seller_couponController.php 2018.5.20 $
 * @author mosir
 */

class Seller_couponController extends \common\controllers\BaseSellerController
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
			$query = CouponModel::find()->where(['store_id' => $this->visitor['store_id']])->orderBy(['coupon_id' => SORT_DESC]);	
			$page = Page::getPage($query->count(), $post->pageper);
			$coupons = $query->offset($page->offset)->limit($page->limit)->asArray()->all();		
			foreach($coupons as $key => $val)
			{
				$coupons[$key]['min_amount'] = ceil($val['min_amount']);
				$coupons[$key]['coupon_value'] = ceil($val['coupon_value']);
				
				if($val['end_time'] && $val['end_time'] < Timezone::gmtime()){
					$coupons[$key]['available'] = 0;
				}
				$val['end_time'] > 0 && $coupons[$key]['end_time'] = Timezone::localDate('Y-m-d', $val['end_time']);
			}
			
			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			return array('result' => array_values($coupons), 'totalPage' => $page->getPageCount());
		}
		else
		{
			$this->params['_foot_tags'] = Resource::import([
				'script' => 'jquery.plugins/jquery.form.js,jquery.ui/jquery.ui.js,jquery.ui/i18n/' . Yii::$app->language . '.js, dialog/dialog.js,weui/js/jquery-weui.min.js,jquery.plugins/jquery.infinite.js',
				'style' => 'weui/lib/weui.min.css,weui/css/jquery-weui.min.css,dialog/dialog.css'
			]);
			$this->params['infiniteParams'] = json_encode(ArrayHelper::toArray($post));
			
			$this->params['page'] = Page::seo(['title' => Language::get('seller_coupon')]);
        	return $this->render('../seller_coupon.index.html', $this->params);
		}
	}
	
	public function actionAdd()
	{
		if(!Yii::$app->request->isPost)
		{
			$this->params['action'] = Url::toRoute(['seller_coupon/add']);
			$this->params['now'] = Timezone::gmtime();
			
			$this->params['_foot_tags'] = Resource::import([
				'script' => 'jquery.plugins/jquery.form.js,weui/js/jquery-weui.min.js',
				'style' =>  'weui/lib/weui.min.css,weui/css/jquery-weui.min.css'
			]);
			
			$this->params['page'] = Page::seo(['title' => Language::get('coupon_add')]);
        	return $this->render('../seller_coupon.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['clickreceive']);
			$model = new \frontend\models\Seller_couponForm(['store_id' => $this->visitor['store_id']]);
   			if(!$model->save($post, true)) {
				return Message::warning($model->errors);
			}
			
			return Message::display(Language::get('add_ok'),['seller_coupon/index']);
		}
	}
	
	public function actionEdit()
	{
		$id = intval(Yii::$app->request->get('id'));
		
		if(!$id || !($coupon = CouponModel::find()->where(['store_id' => $this->visitor['store_id'], 'coupon_id' => $id])->asArray()->one())) {
			return Message::warning(Language::get('no_coupon'));
		}
		
		if(!Yii::$app->request->isPost)
		{
			$this->params['coupon'] = $coupon;
			$this->params['action'] = Url::toRoute(['seller_coupon/edit', 'id' => $id]);
			
			$this->params['_foot_tags'] = Resource::import([
				'script' => 'jquery.plugins/jquery.form.js,weui/js/jquery-weui.min.js',
				'style' =>  'weui/lib/weui.min.css,weui/css/jquery-weui.min.css'
			]);
			
			$this->params['page'] = Page::seo(['title' => Language::get('coupon_edit')]);
        	return $this->render('../seller_coupon.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['clickreceive']);
			$model = new \frontend\models\Seller_couponForm(['coupon_id' => $id, 'store_id' => $this->visitor['store_id']]);
			
			if(!$model->save($post, true)) {
				return Message::warning($model->errors);
			}
			
			return Message::display(Language::get('edit_ok'),['seller_coupon/index']);
		}
	}
	
	public function actionDelete()
    {
        $post = Basewind::trimAll(Yii::$app->request->get(), true);
		if(!$post->id) {
			return Message::warning(Language::get('no_coupon'));
		}
		
		$coupons = CouponModel::find()->select('coupon_id')->where(['store_id' => $this->visitor['store_id']])->andWhere(['in', 'coupon_id', explode(',', $post->id)])->andWhere('available = 0 OR (available = 1 AND end_time < :end_time)', [':end_time' => Timezone::gmtime()])->column();
		
		if(empty($coupons) || !is_array($coupons)) {
			return Message::warning(Language::get('no_coupon'));
		}
		
		if(!CouponModel::deleteAll(['in', 'coupon_id', $coupons])) {
			return Message::warning(Language::get('drop_fail'));
		}
        return Message::display(Language::get('drop_ok'));
    }
	
	/* 优惠券发放 */
	public function actionExtend()
    {
        $id = intval(Yii::$app->request->get('id'));
		if(!$id || !($coupon = CouponModel::find()->alias('coupon')->select('coupon.*,s.store_name')->joinWith('store s', false)->where(['s.store_id' => $this->visitor['store_id'], 'coupon_id' => $id])->asArray()->one())) {
			return Message::warning(Language::get('no_coupon'));
		}
		
        if (!Yii::$app->request->isPost)
        {
			$this->params['id'] = $id;
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.form.js');
			
			return $this->render('../seller_coupon.extend.html', $this->params);
        }
        else
        {
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			 
			if(!$post->username) {
				return Message::warning(Language::get('involid_data'));	
			}
			
			if(($allName = explode("\n", str_replace(array("\r","\r\n"), "\n", $post->username))) && is_array($allName)) {
				$users = UserModel::find()->select('userid,username')->where(['in', 'username', $allName])->all();
			}
	
			if(empty($users)) {
				return Message::warning(Language::get('involid_data'));
			}
            if (count($users) > 30) {
				return Message::warning(Language::get('amount_gt'));
            }
			
			// 是否还有足够的优惠券来发放
			if(CouponModel::find()->select('surplus')->where(['coupon_id' => $id])->scalar() < count($users)) {
				return Message::warning(Language::get('extend_no_enough'));
			}
			
			$sends = array();
			foreach($users as $user)
			{
				// 还有未使用的优惠卷不能再发放（暂时不明白为什么要做此限制，先屏蔽）
				/*if(CouponsnModel::find()->alias('cs')->select('cs.coupon_sn')->where(['userid' => $user->userid])->andWhere(['and', ['>','cs.remain_times', '0'], ['>', 'c.end_time', Timezone::gmtime()]])->joinWith('coupon c', false, 'INNER JOIN')->exists()){
					continue;
				}*/
				
				$model = new CouponsnModel();
				$model->coupon_sn = $model->createRandom();
				$model->coupon_id = $id;
				$model->remain_times = 1;
				$model->userid = $user->userid;
				if($model->save()) {
					if(!CouponModel::updateAllCounters(['surplus' => -1], ['coupon_id' => $id])) {
						$model->delete();
						continue;
					}
					$sends[] = array_merge(['coupon_sn' => $model->coupon_sn], ArrayHelper::toArray($user));
				}
			}
			
			foreach($sends as $send)
			{
				$pmer = Basewind::getPmer('touser_send_coupon', ['coupon' => $coupon, 'user' => $send]);
				if($pmer) {
					$pmer->sendFrom(0)->sendTo($send['userid'])->send();
				}
				
				// 发送给买家优惠券通知
				$mailer = Basewind::getMailer('touser_send_coupon', ['coupon' => $coupon, 'user' => $send]);
				if($mailer && ($toEmail = UserModel::find()->select('email')->where(['userid' => $send['userid']])->scalar())) {
					$mailer->setTo($toEmail)->send();
				}
				
			}
			return Message::display(Language::get('extend_successed'));    
        }
    }
}