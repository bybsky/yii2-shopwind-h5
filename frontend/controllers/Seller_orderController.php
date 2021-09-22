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
use common\library\Plugin;
use common\library\Taskqueue;

/**
 * @Id Seller_orderController.php 2018.5.16 $
 * @author mosir
 */

class Seller_orderController extends \common\controllers\BaseSellerController
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
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['pageper']);
		$curmenu = empty($post->type) ? 'all_orders' : $post->type.'_orders';
		
		if(Yii::$app->request->isAjax)
		{
			$model = new \frontend\models\Seller_orderForm(['store_id' => $this->visitor['store_id']]);
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
			return $this->render('../seller_order.index.html', $this->params);
		}
	}
	
	public function actionView()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['order_id']);

		$model = new \frontend\models\Seller_orderViewForm(['store_id' => $this->visitor['store_id']]);
		if(!($orderInfo = $model->formData($post))) {
			return Message::warning($model->errors);
		}
		// 是否申请过退款 - 手机端才需要
		$orderInfo = $model->getOrderRefund($orderInfo);
		$this->params['order'] = $orderInfo;
		
		$this->params['page'] = Page::seo(['title' => Language::get('order_detail')]);
        return $this->render('../seller_order.view.html', $this->params);
	}
	
	/* 调整费用 */
    public function actionAdjustfee()
    {
		$get = Basewind::trimAll(Yii::$app->request->get(), true, ['order_id', 'page']);
		
		$model = new \frontend\models\Seller_orderAdjustfeeForm(['store_id' => $this->visitor['store_id']]);
		if(!($orderInfo = $model->formData($get))) {
			return Message::warning($model->errors);
		}
		
        if (!Yii::$app->request->isPost)
        {
			$this->params['order'] = $orderInfo;
			
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.form.js');
		
			$this->params['page'] = Page::seo(['title' => Language::get('adjust_fee')]);
			return $this->render('../seller_order.adjust_fee.html', $this->params);
        }
        else
        {
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			if(!$model->submit($post, $orderInfo)) {
				return Message::warning($model->errors);
			}
            return Message::display(Language::get('adjust_fee_ok'), ['seller_order/index', 'type' => $get->type, 'page' => $get->page]);
        }
    }
	
	/* 待发货的订单发货 */
    public function actionShipped()
    {
		$get = Basewind::trimAll(Yii::$app->request->get(), true, ['order_id', 'page']);
		
		$model = new \frontend\models\Seller_orderShippedForm(['store_id' => $this->visitor['store_id']]);
		if(!($orderInfo = $model->formData($get))) {
			return Message::warning($model->errors);
		}
		
        if (!Yii::$app->request->isPost)
        {
			$this->params['order'] = $orderInfo;

			if(($expresser = Plugin::getInstance('express')->autoBuild())) {
				$this->params['express_company'] = $expresser->getCompanys();
			}
			
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.form.js');
		
			$this->params['page'] = Page::seo(['title' => Language::get('shipped_order')]);
			return $this->render('../seller_order.shipped.html', $this->params);
		} 
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			if(!$model->submit($post, $orderInfo)) {
				return Message::warning($model->errors);
			}
            return Message::display(Language::get('shipped_order_shipped'), ['seller_order/index', 'type' => $get->type, 'page' => $get->page]);
		}
    }
	
	/* 取消订单（没付款之前） */
	public function actionCancel()
	{
		$get = Basewind::trimAll(Yii::$app->request->get(), true);
		
		$model = new \frontend\models\Seller_orderCancelForm(['store_id' => $this->visitor['store_id']]);
		if(!($orders = $model->formData($get))) {
			return Message::warning($model->errors);
		}

		if(!Yii::$app->request->isPost)
		{
			$this->params['orders'] = $orders;
			$this->params['order_id'] = implode(',', array_keys($orders));
			
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.form.js');
		
			$this->params['page'] = Page::seo(['title' => Language::get('cancel_order')]);
			return $this->render('../seller_order.cancel.html', $this->params);
		}
		else
		{
            $post = Basewind::trimAll(Yii::$app->request->post(), true);
            if(!$model->submit($post, $orders)) {
				return Message::warning($model->errors);
			}
            return Message::display(Language::get('cancel_order_successed'), ['seller_order/index', 'type' => $get->type, 'page' => $get->page]);
		}
	}
	
	/* 卖家给订单添加备忘（没用到） */
    public function actionMemo()
    {
        $get = Basewind::trimAll(Yii::$app->request->get(), true, ['order_id', 'page']);
		
		$model = new \frontend\models\Seller_orderMemoForm(['store_id' => $this->visitor['store_id']]);
		if(!($orderInfo = $model->formData($get))) {
			return Message::warning($model->errors);
		}

        if (!Yii::$app->request->isPost)
        {
			$this->params['order'] = $orderInfo;
			return $this->render('../seller_order.memo.html', $this->params);
        }
        else
        {
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['flag']);
            if(!$model->submit($post, $orderInfo)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('cancel_order_successed'), ['seller_order/index', 'type' => $get->type, 'page' => $get->page]);
        }
    }
}