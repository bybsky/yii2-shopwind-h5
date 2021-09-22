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

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;
use common\library\Taskqueue;

/**
 * @Id Buyer_orderController.php 2018.4.17 $
 * @author mosir
 */

class Buyer_orderController extends \common\controllers\BaseUserController
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
		Taskqueue::run();
	}

    public function actionIndex()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['page', 'pageper']);
		$curmenu = empty($post->type) ? 'all_orders' : $post->type.'_orders';
		
		if(Yii::$app->request->isAjax)
		{
			$model = new \frontend\models\Buyer_orderForm();
			list($orders, $page) = $model->formData($post, $post->pageper);
			
			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			return array('result' => array_values($orders), 'totalPage' => $page->getPageCount());
		}
		else
		{
			$this->params['_foot_tags'] = Resource::import([
				'script' => 'jquery.ui/jquery.ui.js,jquery.ui/i18n/' . Yii::$app->language . '.js,dialog/dialog.js,jquery.plugins/jquery.infinite.js,jquery.plugins/jquery.form.js',
				'style' =>  'jquery.ui/themes/smoothness/jquery.ui.css,dialog/dialog.css'
			]);
			$this->params['infiniteParams'] = json_encode(ArrayHelper::toArray($post));
			
			$this->params['page'] = Page::seo(['title' => Language::get($curmenu)]);
			return $this->render('../buyer_order.index.html', $this->params);
		}
	}
	
	public function actionView()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['order_id']);

		$model = new \frontend\models\Buyer_orderViewForm();
		if(!($orderInfo = $model->formData($post))) {
			return Message::warning($model->errors);
		}
		// 是否申请过退款 - 手机端才需要
		$orderInfo = $model->getOrderRefund($orderInfo);		
		$this->params['order'] = $orderInfo;
		
		$this->params['page'] = Page::seo(['title' => Language::get('order_detail')]);
        return $this->render('../buyer_order.view.html', $this->params);
	}
	
	/* 取消订单（没付款之前）*/ 
	public function actionCancel()
	{
		$get = Basewind::trimAll(Yii::$app->request->get(), true);
		
		$model = new \frontend\models\Buyer_orderCancelForm();
		if(!($orders = $model->formData($get))) {
			return Message::warning($model->errors);
		}
		if(!Yii::$app->request->isPost)
		{
			$this->params['orders'] = $orders;
			$this->params['order_id'] = implode(',', array_keys($orders));
			
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.form.js');
		
			$this->params['page'] = Page::seo(['title' => Language::get('cancel_order')]);
			return $this->render('../buyer_order.cancel.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
            if(!$model->submit($post, $orders)) {
				return Message::warning($model->errors);
			}
            return Message::display(Language::get('cancel_order_successed'), ['buyer_order/index']);
		}
	}
	
	/* 确认订单（买家确认收货）*/
	public function actionConfirm()
	{
		$get = Basewind::trimAll(Yii::$app->request->get(), true, ['order_id']);
		
		$model = new \frontend\models\Buyer_orderConfirmForm();
		if(!($result = $model->formData($get))) {
			return Message::warning($model->errors);
		}
		list($orderInfo, $tradeInfo) = $result;
		
		if(!Yii::$app->request->isPost)
		{
			$this->params['order'] = $orderInfo;

			$this->params['page'] = Page::seo(['title' => Language::get('confirm_order')]);
			return $this->render('../buyer_order.confirm.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			
			if(!$model->submit($post, $orderInfo, $tradeInfo)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('confirm_order_successed'), ['buyer_order/evaluate', 'order_id' => $orderInfo['order_id']]);
		}
	}
	
	/* 评价订单（给卖家评价）*/
    public function actionEvaluate()
    {
		$get = Basewind::trimAll(Yii::$app->request->get(), true, ['order_id']);
		
		$model = new \frontend\models\Buyer_orderEvaluateForm();
		if(!($orderInfo = $model->formData($get))) {
			return Message::warning($model->errors);
		}
       
        if(!Yii::$app->request->isPost)
        {
            // 获取订单商品 
            $this->params['goods_list'] = $model->getOrderGoods($get);
			$this->params['order'] = $orderInfo;
			
			$this->params['_foot_tags'] = Resource::import([
				'script' => 'jquery.plugins/raty/jquery.raty.js',
				'style' => 'jquery.plugins/raty/jquery.raty.css'
			]);
			
			$this->params['page'] = Page::seo(['title' => Language::get('order_evaluate')]);
			return $this->render('../buyer_order.evaluate.html', $this->params);
        }
        else
        {
			$post = Basewind::trimAll(Yii::$app->request->post());
           
			if(!$model->submit($post, $orderInfo)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('evaluate_successed'), ['buyer_order/index']);
        }
    }
}