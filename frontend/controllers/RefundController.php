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

use common\models\RefundModel;
use common\models\RefundMessageModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;

/**
 * @Id RefundController.php 2018.10.17 $
 * @author mosir
 */

class RefundController extends \common\controllers\BaseUserController
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
			$model = new \frontend\models\RefundForm();
			list($recordlist, $page) = $model->formData($post, $post->pageper);
		
			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			return array('result' => array_values($recordlist), 'totalPage' => $page->getPageCount());
		}
		else
		{
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.infinite.js');
			$this->params['infiniteParams'] = json_encode(ArrayHelper::toArray($post));
		
			$this->params['page'] = Page::seo(['title' => Language::get('refund_apply')]);
        	return $this->render('../refund.index.html', $this->params);
		}
	}
	
	public function actionAdd()
    {
		$get = Basewind::trimAll(Yii::$app->request->get(), true, ['order_id']);
		
		$model = new \frontend\models\RefundForm();
		list($refund) = $model->getData($get);
		if(!$refund) {
			return Message::warning($model->errors);
		}
		
		if(!Yii::$app->request->isPost)
		{
			$this->params['refund'] = $refund;
			$this->params['shippeds'] = $model->getShippedOptions();
			$this->params['reasons'] = $model->getRefundReasonOptions();
			
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.form.js');
		
			$this->params['page'] = Page::seo(['title' => Language::get('refund_add')]);
        	return $this->render('../refund.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['shipped']);
			
			$model = new \frontend\models\RefundForm();
			if(!($refund = $model->save($post, $get, true))) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('refund_apply_ok'), ['refund/view', 'id' => $refund->refund_id]);
		}
	}
	
	public function actionEdit()
    {
		$get = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);
		
		if(!Yii::$app->request->isPost)
		{
			$model = new \frontend\models\RefundForm();
			
			list($refund) = $model->getData($get);
			if(!$refund) {
				return Message::warning($model->errors);
			}
			$this->params['refund'] = $refund;
			$this->params['shippeds'] = $model->getShippedOptions();
			$this->params['reasons'] = $model->getRefundReasonOptions();
			
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.form.js');
		
			$this->params['page'] = Page::seo(['title' => Language::get('refund_edit')]);
        	return $this->render('../refund.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['shipped']);
			
			$model = new \frontend\models\RefundForm();
			if(!($refund = $model->save($post, $get, true))) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('refund_edit_ok'), ['refund/view', 'id' => $refund->refund_id]);
		}
	}
	
	public function actionView()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);
		
		$model = new \frontend\models\RefundViewForm();
		list($refund) = $model->formData($post);
		if(!$refund) {
			return Message::warning($model->errors);
		}
		$this->params['refund'] = $refund;

		$this->params['page'] = Page::seo(['title' => Language::get('refund_view')]);
        return $this->render('../refund.view.html', $this->params);
	}
	
	public function actionMessage()
	{
		if(!Yii::$app->request->isPost)
		{
			$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id', 'page','pageper']);
			
			if(Yii::$app->request->isAjax)
			{
				$model = new \frontend\models\RefundMessageForm();
				list($recordlist, $page) = $model->formData($post, $post->pageper);
		
				Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
				return array('result' => array_values($recordlist), 'totalPage' => $page->getPageCount());
			}
			else
			{
				$refund = RefundModel::find()->select('refund_id,status,seller_id')->where(['refund_id' => $post->id])->asArray()->one();
				if(!$refund) {
					return Message::warning(Language::get('no_such_refund'));
				}
				$this->params['refund'] = $refund;
		
				$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.infinite.js,jquery.plugins/jquery.form.js');
				$this->params['infiniteParams'] = json_encode(ArrayHelper::toArray($post));
			
				$this->params['page'] = Page::seo(['title' => Language::get('refund_message')]);
				return $this->render('../refund.message.html', $this->params);
			}
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['id']);
			
			$model = new \frontend\models\RefundMessageForm();
			if(!$model->save($post, true)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('send_message_ok'));			
		}
	}
	
	/* 平台介入处理退款争议 */
	public function actionIntervene()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);
		
		$model = new \frontend\models\RefundInterveneForm();
		if(!$model->save($post, true)) {
			return Message::warning($model->errors);
		}
		return Message::display(Language::get('intervene_apply_ok'));
	}
	
	/* 取消退款 */ 
	public function actionCancel()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);
		
		if(!$post->id || !($refund = RefundModel::find()->select('tradeNo')->where(['refund_id' => $post->id, 'buyer_id' => Yii::$app->user->id])->andWhere(['not in', 'status', ['SUCCESS','CLOSED']])->one())) {
			return Message::warning(Language::get('cancel_disallow'));
		}
		
		if(RefundModel::deleteAll(['refund_id' => $post->id])) {
			if(RefundMessageModel::deleteAll(['refund_id' => $post->id])) {
				return Message::display(Language::get('refund_cancel_ok'), ['deposit/record', 'tradeNo' => $refund->tradeNo]);
			}
		}
		return Message::warning(Language::get('refund_cancel_fail'));
	}
	
	// 卖家退款管理
	public function actionReceive()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		
		if(Yii::$app->request->isAjax)
		{
			$model = new \frontend\models\RefundForm(['visitor' => 'seller']);
			list($recordlist, $page) = $model->formData($post, $post->pageper);
		
			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			return array('result' => array_values($recordlist), 'totalPage' => $page->getPageCount());
		}
		else
		{
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.infinite.js');
			$this->params['infiniteParams'] = json_encode(ArrayHelper::toArray($post));
			
			$this->params['page'] = Page::seo(['title' => Language::get('refund_receive')]);
			return $this->render('../refund.receive.html', $this->params);
		}
	}
	
	/* 卖家同意退款 */
	public function actionAgree()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);
		
		$model = new \frontend\models\RefundAgreeForm();
		if(!$model->submit($post)) {
			return Message::warning($model->errors);
		}
		
		return Message::display(Language::get('seller_agree_refund_ok'), ['refund/view', 'id' => $post->id]);	
	}
	
	/* 卖家拒绝退款 */
	public function actionRefuse()
	{
		$get = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);
		
		$model = new \frontend\models\RefundRefuseForm();
		if(!($refund = $model->formData($get))) {
			return Message::warning($model->errors);
		}
		
		if(!Yii::$app->request->isPost)
		{
			$this->params['refund'] = $refund;
			
			$this->params['page'] = Page::seo(['title' => Language::get('refund_refuse')]);
			return $this->render('../refund.refuse.html', $this->params);	
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['id']);
			
			if(!$model->save($post, true)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('refuse_ok'), ['refund/view', 'id' => $post->id]);
		}
	}
}