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
use common\library\Def;

/**
 * @Id AppmarketController.php 2018.10.11 $
 * @author mosir
 */

class AppmarketController extends \common\controllers\BaseUserController
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
			$model = new \frontend\models\AppmarketForm();
			list($appmarket, $page) = $model->formData($post, $post->pageper);
			
			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			return array('result' => array_values($appmarket), 'totalPage' => $page->getPageCount());
		}
		else
		{
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.infinite.js');
			$this->params['infiniteParams'] = json_encode(ArrayHelper::toArray($post));
				
			$this->params['page'] = Page::seo(['title' => Language::get('applist')]);
			return $this->render('../appmarket.index.html', $this->params);
		}
	}
	
	public function actionMy()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['page']);
		
		if(Yii::$app->request->isAjax)
		{
			$model = new \frontend\models\AppmarketMyForm();
			list($apprenewal, $page) = $model->formData($post, $post->pageper);
			
			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			return array('result' => array_values($apprenewal), 'totalPage' => $page->getPageCount());
		}
		else
		{
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.infinite.js');
			$this->params['infiniteParams'] = json_encode(ArrayHelper::toArray($post));
				
			$this->params['page'] = Page::seo(['title' => Language::get('myapp')]);
			return $this->render('../appmarket.my.html', $this->params);
		}
	}
	
	public function actionView()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);
		
		$model = new \frontend\models\AppmarketViewForm();
		if(($appmarket = $model->formData($post)) === false) {
			return Message::warning($model->errors);
		}
		$this->params['appmarket'] = $appmarket;
			
		$this->params['page'] = Page::seo(['title' => Language::get($appmarket['title'])]);
        return $this->render('../appmarket.view.html', $this->params);
	}
	
	/* 购买应用 */
	public function actionBuy()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id', 'period']);
		
		$model = new \frontend\models\AppmarketBuyForm(['store_id' => $this->visitor['store_id']]);
		if(($appbuylog = $model->formData($post)) === false) {
			return Message::warning($model->errors);
		}
		return Message::result($appbuylog->bid);
	}
	
	/* 收银台 */
	public function actionCashier()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);
		
		$model = new \frontend\models\AppmarketCashierForm();
		if(($appbuylog = $model->submit($post, true)) === false) {
			return Message::warning($model->errors);
		}

		// 如果应用是免费的，则直接提示购买成功
		if($appbuylog->amount == 0) {
			return Message::display(Language::get('buy_ok'), ['appmarket/buylog']);	
		}
		
		// 到收银台付款
		return $this->redirect(['cashier/gateway', 'bizOrderId' => $appbuylog->orderId, 'bizIdentity' => Def::TRADE_BUYAPP]);
	}
	
	public function actionBuylog()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['page', 'pageper']);
		
		if(Yii::$app->request->isAjax)
		{
			$model = new \frontend\models\AppmarketBuylogForm();
			list($appbuylog, $page) = $model->formData($post, $post->pageper);
			
			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			return array('result' => array_values($appbuylog), 'totalPage' => $page->getPageCount());
		}
		else
		{
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.infinite.js');
			$this->params['infiniteParams'] = json_encode(ArrayHelper::toArray($post));
				
			$this->params['page'] = Page::seo(['title' => Language::get('buylog')]);
			return $this->render('../appmarket.buylog.html', $this->params);
		}
	}
}