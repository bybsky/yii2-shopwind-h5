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

use common\models\DepositTradeModel;
use common\models\DepositAccountModel;
use common\models\BankModel;
use common\models\CashcardModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;
use common\library\Plugin;

/**
 * @Id DepositController.php 2018.3.20 $
 * @author mosir
 */

class DepositController extends \common\controllers\BaseUserController
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
		if(!($account = DepositAccountModel::find()->where(['userid' => Yii::$app->user->id])->asArray()->one())) {
			$account = ArrayHelper::toArray(DepositAccountModel::createDepositAccount(Yii::$app->user->id));
		}
		$this->params['deposit_account'] = $account;
		
		$query = BankModel::find()->where(['userid' => Yii::$app->user->id]);
		$this->params['myBank'] = ['count' => $query->count()];
		
		$this->params['page'] = Page::seo(['title' => Language::get('deposit_index')]);
        return $this->render('../deposit.index.html', $this->params);
    }
	
	/* 配置账户信息 */
	public function actionConfig()
	{
		if(!Yii::$app->request->isPost)
		{
			$this->params['deposit_account'] = DepositAccountModel::find()->where(['userid' => Yii::$app->user->id])->asArray()->one();
			
			$this->params['_foot_tags'] = Resource::import([
				'script' => 'jquery.ui/jquery.ui.js,jquery.ui/i18n/' . Yii::$app->language . '.js,dialog/dialog.js,jquery.plugins/jquery.form.js',
            	'style' =>  'jquery.ui/themes/smoothness/jquery.ui.css,dialog/dialog.css'
			]);
			
			$this->params['page'] = Page::seo(['title' => Language::get('deposit_config')]);
        	return $this->render('../deposit.config.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			
			$model = new \frontend\models\DepositConfigForm();
			if(!$model->save($post, true)) {
				return Message::warning($model->errors);
			}
            return Message::display(Language::get('config_account_successed'), ['deposit/index']);
		}
	}
	
	/* 查询单笔收支详细 */
	public function actionRecord()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		
		$model = new \frontend\models\DepositRecordForm();
		if(!($record = $model->formData($post))) {
			return Message::warning($model->errors);
		}
		$this->params['tradeInfo'] = $record;
		
		$this->params['page'] = Page::seo(['title' => Language::get('deposit_record')]);
        return $this->render('../deposit.record.html', $this->params);
	}
	
	/* 交易记录 */
	public function actionTradelist()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['page', 'pageper']);
		
		if(Yii::$app->request->isAjax)
		{
			$model = new \frontend\models\DepositTradelistForm();
			list($recordlist, $page) = $model->formData($post, $post->pageper);
			list($amount, $quantity) = $model->getTotal();
			
			$result = array(
				'list' 			=> array_values($recordlist), 
				'total_amount' 	=> $amount, 
				'total_bill' 	=> $quantity
			);
			
			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			return array('result' => $result, 'totalPage' => $page->getPageCount());
		}
		else
		{
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.infinite.js');
			$this->params['infiniteParams'] = json_encode(ArrayHelper::toArray($post));
			
			$this->params['page'] = Page::seo(['title' => Language::get('deposit_tradelist')]);
			return $this->render('../deposit.tradelist.html', $this->params);
		}
	}
	
	/* 财务明细 */
	public function actionRecordlist()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['page', 'pageper']);
		
		if(Yii::$app->request->isAjax)
		{
			$model = new \frontend\models\DepositRecordlistForm();
			list($recordlist, $page) = $model->formData($post, $post->pageper);
			list($income, $outlay) = $model->getTotal();
			
			$result = array(
				'list' 			=> array_values($recordlist), 
				'total_income' 	=> $income, 
				'total_outlay' 	=> $outlay
			);
			
			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			return array('result' => $result, 'totalPage' => $page->getPageCount());
		}
		else
		{
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.infinite.js');
			$this->params['infiniteParams'] = json_encode(ArrayHelper::toArray($post));
			
			$this->params['page'] = Page::seo(['title' => Language::get('deposit_recordlist')]);
			return $this->render('../deposit.recordlist.html', $this->params);
		}
	}
	
	/* 冻结明细 */
	public function actionFrozenlist()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['page', 'pageper']);
		
		if(Yii::$app->request->isAjax)
		{
			$model = new \frontend\models\DepositFrozenlistForm();
			list($recordlist, $page) = $model->formData($post, $post->pageper);
			list($amount) = $model->getTotal();
			
			$result = array(
				'list' 	 => array_values($recordlist),
				'amount' => $amount
			);
			
			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			return array('result' => $result, 'totalPage' => $page->getPageCount());
		}
		else
		{
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.infinite.js');
			$this->params['infiniteParams'] = json_encode(ArrayHelper::toArray($post));
			
			$this->params['page'] = Page::seo(['title' => Language::get('deposit_frozenlist')]);
			return $this->render('../deposit.frozenlist.html', $this->params);
		}
	}
	
	/* 充值 */
	public function actionRecharge()
	{
		if(!Yii::$app->request->isPost)
		{
			// 获取可用于充值的支付方式列表
			$this->params['payments'] = Plugin::getInstance('payment')->build()->getEnabled(0, true, ['not in', 'code', ['deposit', 'cod']]);
			
			$this->params['page'] = Page::seo(['title' => Language::get('deposit_recharge')]);
			return $this->render('../deposit.recharge.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			
			$model = new \frontend\models\DepositRechargeForm();
			list($tradeNo, $payment_code) = $model->formData($post);
			if($model->errors) {
				return Message::warning($model->errors);
			}
			
			// 获取交易数据
			list($errorMsg, $orderInfo) = DepositTradeModel::checkAndGetTradeInfo($tradeNo, Yii::$app->user->id);
			if($errorMsg !== false) {
				return Message::warning($errorMsg);
			}

			// 生成支付URL或表单
			list($payTradeNo, $payform) = Plugin::getInstance('payment')->build($payment_code, $post)->getPayform($orderInfo);
			$this->params['payform'] = array_merge($payform, ['payTradeNo' => $payTradeNo]);
				
			// 跳转到真实收银台
			$this->params['page'] = Page::seo(['title' => Language::get('cashier')]);
        	return $this->render('../cashier.payform.html', $this->params);
		}
	}
	
	/* 充值卡充值 */
	public function actionCardrecharge()
	{
		if(!Yii::$app->request->isPost)
		{
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.form.js');
			
			$this->params['page'] = Page::seo(['title' => Language::get('deposit_recharge')]);
			return $this->render('../deposit.cardrecharge.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			
			$model = new \frontend\models\DepositCardrechargeForm();
			if(!$model->submit($post)) {
				return Message::warning($model->errors);
			}
			
			$cashcard = CashcardModel::find()->select('money')->where(['cardNo' => $post->cardNo])->one();
			return Message::display(sprintf(Language::get('cashcard_recharge_ok'), $cashcard->money), ['deposit/tradelist']);
		}
	}
	
	/* 充值记录 */
	public function actionRechargelist()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['page', 'pageper']);
		
		if(Yii::$app->request->isAjax)
		{
			$model = new \frontend\models\DepositRechargelistForm();
			list($recordlist, $page) = $model->formData($post, $post->pageper);

			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			return array('result' => array_values($recordlist), 'totalPage' => $page->getPageCount());
		}
		else
		{
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.infinite.js');
			$this->params['infiniteParams'] = json_encode(ArrayHelper::toArray($post));
		
			$this->params['page'] = Page::seo(['title' => Language::get('deposit_rechargelist')]);
        	return $this->render('../deposit.rechargelist.html', $this->params);
		}
	}
	
	/* 提现申请 */
	public function actionWithdraw()
	{
		$this->params['deposit_account'] = DepositAccountModel::find()->select('money,nodrawal')->where(['userid' => Yii::$app->user->id])->asArray()->one();
		$bankList = BankModel::find()->where(['userid' => Yii::$app->user->id])->asArray()->all();
		$this->params['myBank'] = ['list' => $bankList, 'count' => count($bankList)];
		
		$this->params['page'] = Page::seo(['title' => Language::get('deposit_withdraw')]);
        return $this->render('../deposit.withdraw.html', $this->params);
	}
	
	/* 提现确认 */
	public function actionWithdrawconfirm()
	{
		if(!Yii::$app->request->isPost)
		{
			$post = Basewind::trimAll(Yii::$app->request->get(), true, ['bid']);
			$model = new \frontend\models\DepositWithdrawconfirmForm();
			if(!$model->valid($post, false)) {
				return Message::warning($model->errors);
			}
			$this->params['deposit_account'] = DepositAccountModel::find()->select('money,nodrawal')->where(['userid' => Yii::$app->user->id])->asArray()->one();
			$this->params['bank'] = BankModel::find()->where(['bid' => $post->bid])->asArray()->one();
			$this->params['withdraw'] = ['money' => $post->money];
		
			$this->params['page'] = Page::seo(['title' => Language::get('deposit_withdraw')]);
        	return $this->render('../deposit.withdraw_confirm.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['bid']);
			
			$model = new \frontend\models\DepositWithdrawconfirmForm();
			if(!$model->save($post, true)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('withdraw_ok_wait_verify'), ['deposit/tradelist']);
		}
	}
	
	/* 提现记录 */
	public function actionDrawlist()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['page', 'pageper']);
		
		if(Yii::$app->request->isAjax)
		{
			$model = new \frontend\models\DepositDrawlistForm();
			list($recordlist, $page) = $model->formData($post, $post->pageper);

			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			return array('result' => array_values($recordlist), 'totalPage' => $page->getPageCount());
		}
		else
		{
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.infinite.js');
			$this->params['infiniteParams'] = json_encode(ArrayHelper::toArray($post));
			
			$this->params['page'] = Page::seo(['title' => Language::get('deposit_drawlist')]);
			return $this->render('../deposit.drawlist.html', $this->params);
		}
	}
	
	/* 转账 */
	public function actionTransfer()
	{
		$depositAccount = DepositAccountModel::find()->select('money,account')->where(['userid' => Yii::$app->user->id])->asArray()->one();
		$this->params['deposit_account'] = $depositAccount;
		
		$this->params['page'] = Page::seo(['title' => Language::get('deposit_transfer')]);
        return $this->render('../deposit.transfer.html', $this->params);	
	}
	
	/* 转账确认 */
	public function actionTransferconfirm()
	{
		$get = Basewind::trimAll(Yii::$app->request->get(), true);
		
		$model = new \frontend\models\DepositTransferconfirmForm();
		if(!$model->valid($get)) {
			return Message::warning($model->errors);
		}
		
		if(!Yii::$app->request->isPost)
		{
			$this->params['transfer'] = ['money' => $get->money, 'fee' => $model->getTransferFee($get)];
			$this->params['party'] = $model->getPartyInfo($get);
		
			$this->params['page'] = Page::seo(['title' => Language::get('deposit_transfer')]);
        	return $this->render('../deposit.transfer_confirm.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			
			$post->account = $get->account;
			if(!$model->save($post, false)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('transfer_ok'), ['deposit/tradelist']);
		}	
	}
	
	/* 月账单下载 */
	public function actionMonthbill()
	{
		$model = new \frontend\models\DepositMonthbillForm();	
		$this->params['monthbill'] = $model->formData();
		
		$this->params['page'] = Page::seo(['title' => Language::get('deposit_monthbill')]);
        return $this->render('../deposit.monthbill.html', $this->params);
	}
	
	/* 下载某个月的对账单 */
	public function actionDownloadbill()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		if(empty($post->month)) {
			return Message::warning(Language::get('downloadbill_fail'));
		}
		return DepositAccountModel::downloadbill(Yii::$app->user->id, $post->month);
	}
	
	/* 关闭/开启余额支付 */
	public function actionPaystatus()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		$post->status = strtoupper($post->status);
		
		if(!in_array($post->status, ['ON','OFF'])){
			return Message::warning(Language::get('pay_status_error'));
		}
		
		if(!DepositAccountModel::updateAll(['pay_status' => $post->status], ['userid' => Yii::$app->user->id])) {
			return Message::warning(Language::get('pay_status_fail'));
		}
		$showlabel = in_array($post->status, ['ON']) ? Language::get('pay_status_enable') : Language::get('pay_status_closed');
		return Message::display($showlabel, ['deposit/index']);
	}
	
	public function actionCkpaypassword()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		return DepositAccountModel::checkAccountPassword($post->password, Yii::$app->user->id);
	}
}