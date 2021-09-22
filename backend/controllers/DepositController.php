<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace backend\controllers;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

use common\models\UserModel;
use common\models\DepositTradeModel;
use common\models\DepositRecordModel;
use common\models\DepositWithdrawModel;
use common\models\DepositRechargeModel;
use common\models\DepositAccountModel;
use common\models\DepositSettingModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;
use common\library\Timezone;

/**
 * @Id DepositController.php 2018.8.3 $
 * @author mosir
 */

class DepositController extends \common\controllers\BaseAdminController
{
	/**
	 * 初始化
	 */
	public function init()
	{
		parent::init();
	}
	
	public function actionIndex()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['rp', 'page']);
		
		if(!Yii::$app->request->isAjax) 
		{
			$this->params['filtered'] = $this->getConditions($post);
			$this->params['search_options'] = $this->getSearchOption();
			$this->params['pay_status_list'] = $this->getPayStatus();
			
			$this->params['_foot_tags'] = Resource::import([
				'script' => 'jquery.plugins/flexigrid.js,jquery.ui/jquery.ui.js,jquery.ui/i18n/' . Yii::$app->language . '.js',
            	'style'=> 'jquery.ui/themes/smoothness/jquery.ui.css'
			]);
			
			$this->params['page'] = Page::seo(['title' => Language::get('deposit_account_list')]);
			return $this->render('../deposit.index.html', $this->params);
		}
		else
		{
			$query = DepositAccountModel::find()->alias('da')->select('da.*,u.username')->joinWith('user u',false)->indexBy('account_id');
			$query = $this->getConditions($post, $query);
			
			$orderFields = ['username','account','real_name','money','frozen','pay_status','add_time'];
			if(in_array($post->sortname, $orderFields) && in_array(strtolower($post->sortorder), ['asc', 'desc'])) {
				$query->orderBy([$post->sortname => strtolower($post->sortorder) == 'asc' ? SORT_ASC : SORT_DESC]);
			} else $query->orderBy(['account_id' => SORT_DESC]);
			
			$page = Page::getPage($query->count(), $post->rp ? $post->rp : 10);
			
			$result = ['page' => $post->page, 'total' => $query->count()];
			foreach ($query->offset($page->offset)->limit($page->limit)->asArray()->each() as $key => $val)
			{
				$list = array();
				$operation = "<a class='btn red' onclick=\"fg_delete({$key},'deposit')\"><i class='fa fa-trash-o'></i>删除</a>";
				$operation .= "<span class='btn'><em><i class='fa fa-cog'></i>设置 <i class='arrow'></i></em><ul>";
				$operation .= "<li><a href='".Url::toRoute(['deposit/edit', 'id' => $key])."'>编辑</a></li>";
				$operation .= "<li><a href='".Url::toRoute(['deposit/recharge', 'id' => $key])."'>充值</a></li>";
				$operation .= "<li><a href='".Url::toRoute(['deposit/monthbill', 'userid' => $val['userid']])."'>月账单</a></li>";
				$operation .= "</ul>";
				$list['operation'] 	= $operation;
				$list['account'] 	= $val['account'];
				$list['username'] 	= $val['username'];
				$list['real_name'] 	= $val['real_name'];
				$list['money'] 		= $val['money'];
				$list['frozen'] 	= $val['frozen'];
				$list['pay_status'] = $val['pay_status'] == 'OFF' ? '<em class="no"><i class="fa fa-ban"></i>否</em>' : '<em class="yes"><i class="fa fa-check-circle"></i>是</em>';
				$list['add_time'] 	= Timezone::localDate('Y-m-d H:i:s', $val['add_time']);
				$result['list'][$key]	= $list;
			}
			return Page::flexigridXML($result);
		}
	}
	
	public function actionEdit()
	{
		$id = intval(Yii::$app->request->get('id'));
		if(!$id || !($account = DepositAccountModel::findOne($id))) {
			return Message::warning(Language::get('no_such_account'));
		}
		
		if(!Yii::$app->request->isPost)
		{
			$this->params['account'] = ArrayHelper::toArray($account);
			$this->params['page'] = Page::seo(['title' => Language::get('deposit_account')]);
			return $this->render('../deposit.account.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			
			$model = new \backend\models\DepositAccountForm(['account_id' => $id]);
			if(!$model->save($post, true)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_ok'), ['deposit/index']);	
		}
		
	}
	
	// 目前只有用户不存在了，才允许删除
	public function actionDelete()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		foreach(explode(',', $post->id) as $id) {
			if($id && ($model = DepositAccountModel::findOne($id)) && !UserModel::findOne($model->userid)) {
				if(!$model->delete()) {
					return Message::warning($model->errors);
				}
			}
		}
		return Message::display(Language::get('drop_ok'));
	}
	
	public function actionSetting()
	{
		if(!Yii::$app->request->isPost)
		{
			$this->params['setting'] = DepositSettingModel::getSystemSetting();
			$this->params['page'] = Page::seo(['title' => Language::get('deposit_setting')]);
			return $this->render('../deposit.setting.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			
			$model = new \backend\models\DepositSettingForm(['userid' => 0]);
			if(!$model->save($post, true)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_ok'));	
		}
	}
	
	public function actionTradelist()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['rp', 'page']);
		
		if(!Yii::$app->request->isAjax) 
		{
			$this->params['filtered'] = $this->getTradeConditions($post);
			$this->params['status_list'] = array(
				'PENDING' => Language::get('TRADE_PENDING'),
				'ACCEPTED' => Language::get('TRADE_ACCEPTED'),
				'SHIPPED' => Language::get('TRADE_SHIPPED'),
				'SUCCESS' => Language::get('TRADE_SUCCESS'),
				'CLOSED'  => Language::get('TRADE_CLOSED'),
				'WAIT_ADMIN_VERIFY' => Language::get('TRADE_WAIT_ADMIN_VERIFY')
			);
			$this->params['search_options'] = array('tradeNo' => Language::get('tradeNo'), 'bizOrderId' => Language::get('orderId'));
		
			$this->params['_foot_tags'] = Resource::import([
				'script' => 'jquery.plugins/flexigrid.js,jquery.ui/jquery.ui.js,jquery.ui/i18n/' . Yii::$app->language . '.js',
            	'style'=> 'jquery.ui/themes/smoothness/jquery.ui.css'
			]);
			
			$this->params['page'] = Page::seo(['title' => Language::get('deposit_tradelist')]);
			return $this->render('../deposit.tradelist.html', $this->params);
		}
		else
		{
			$query = DepositTradeModel::find()->alias('dt')->select('dt.trade_id,dt.tradeNo,dt.bizOrderId,dt.title,dt.amount,dt.status,dt.flow,dt.buyer_id,dt.add_time,dab.account,dab.real_name')->joinWith('depositAccountBuyer dab',false)->indexBy('trade_id');
			$query = $this->getTradeConditions($post, $query);
			
			$orderFields = ['bizOrderId','add_time','tradeNo','title','amount', 'status'];
			if(in_array($post->sortname, $orderFields) && in_array(strtolower($post->sortorder), ['asc', 'desc'])) {
				$query->orderBy([$post->sortname => strtolower($post->sortorder) == 'asc' ? SORT_ASC : SORT_DESC]);
			} else $query->orderBy(['trade_id' => SORT_DESC]);
			
			$page = Page::getPage($query->count(), $post->rp ? $post->rp : 10);
			
			$result = ['page' => $post->page, 'total' => $query->count()];
			foreach ($query->offset($page->offset)->limit($page->limit)->asArray()->each() as $key => $val)
			{
				$list = array();
				$operation = "<a class='btn red' onclick=\"fg_delete('{$key}','deposit','tradedelete')\"><i class='fa fa-trash-o'></i>删除</a>";
				$list['operation'] 	= $operation;
				$list['add_time'] 	= Timezone::localDate('Y-m-d H:i:s', $val['add_time']);
				$list['tradeNo'] 	= $val['tradeNo'];
				$list['bizOrderId'] = $val['bizOrderId'];
				$list['title'] 		= $val['title'];
				$list['buyer_name'] = $val['real_name'] ? $val['real_name'] : $val['account'];
				
				$partyInfo = DepositTradeModel::getPartyInfoByRecord($val['buyer_id'], $val);
				$list['party'] 		= $partyInfo['name'];
				
				$list['amount'] 	= $val['flow'] == 'income' ? '<span style="color:#C00"><strong>+'.$val['amount'].'</strong></span>' : '<span style="color:#03C"><strong>-'.$val['amount'].'</strong></span>';
				$list['status'] 	= Language::get(strtolower($val['status']));
				$result['list'][$key]	= $list;
			}
			return Page::flexigridXML($result);
		}
	}
	
	public function actionDrawlist()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['rp', 'page']);
		
		if(!Yii::$app->request->isAjax) 
		{
			$this->params['filtered'] = $this->getTradeConditions($post);
			$this->params['status_list'] = array(
				'SUCCESS' => Language::get('TRADE_SUCCESS'),
				'WAIT_ADMIN_VERIFY' => Language::get('TRADE_WAIT_ADMIN_VERIFY')
			);
			$this->params['search_options'] = array('tradeNo' => Language::get('tradeNo'), 'orderId' => Language::get('orderId'), 'username' => Language::get('username'));
		
			$this->params['_foot_tags'] = Resource::import([
				'script' => 'jquery.plugins/flexigrid.js,jquery.ui/jquery.ui.js,jquery.ui/i18n/' . Yii::$app->language . '.js',
            	'style'=> 'jquery.ui/themes/smoothness/jquery.ui.css'
			]);
			
			$this->params['page'] = Page::seo(['title' => Language::get('deposit_drawlist')]);
			return $this->render('../deposit.drawlist.html', $this->params);
		}
		else
		{
			$query = DepositWithdrawModel::find()->alias('dw')->select('dw.*,dt.trade_id,dt.tradeNo,dt.add_time,dt.amount,dt.status,u.username')->joinWith('depositTrade dt', false)->joinWith('user u', false)->indexBy('draw_id');
			$query = $this->getTradeConditions($post, $query);
			
			$orderFields = ['orderId','tradeNo', 'username', 'add_time','amount','status'];
			if(in_array($post->sortname, $orderFields) && in_array(strtolower($post->sortorder), ['asc', 'desc'])) {
				$query->orderBy([$post->sortname => strtolower($post->sortorder) == 'asc' ? SORT_ASC : SORT_DESC]);
			} else $query->orderBy(['draw_id' => SORT_DESC]);
			
			$page = Page::getPage($query->count(), $post->rp ? $post->rp : 10);
			
			$result = ['page' => $post->page, 'total' => $query->count()];
			foreach ($query->offset($page->offset)->limit($page->limit)->asArray()->each() as $key => $val)
			{
				$list = array();
				
				if($val['status'] == 'WAIT_ADMIN_VERIFY'){
					$status_label = '<span style="color:#f60">'.Language::get(strtolower($val['status'])).'</span>';
				}elseif($val['status'] == 'CLOSED'){
					$status_label = '<span style="color:#999">'.Language::get(strtolower($val['status'])).'</span>';
				}else{
					$status_label = '<span style="color:#2F792E">'.Language::get(strtolower($val['status'])).'</span>';
				}
				$card_info = unserialize($val['card_info']);
				
				$operation = "<a class='btn red' onclick=\"fg_delete('{$key}','deposit','drawdelete')\"><i class='fa fa-trash-o'></i>删除</a>";
				if($val['status'] == 'WAIT_ADMIN_VERIFY'){
					$info = $card_info['bank_name'].','.Language::get($card_info['type']).','.$card_info['account_name'].','.$card_info['num'].','.$card_info['open_bank'];
					$operation .= "<a class='btn orange' onclick=\"fg_drawverify('{$key}','{$info}')\"><i class='fa fa-check-square'></i>审核</a>";
				}
				$list['operation'] 	= $operation;
				$list['add_time'] 	= Timezone::localDate('Y-m-d H:i:s', $val['add_time']);
				$list['tradeNo'] 	= $val['tradeNo'];
				$list['orderId'] 	= $val['orderId'];
				$list['username'] 	= $val['username'];	
				$list['name'] 		= Language::get('withdraw');
				$list['amount'] 	= $val['amount'];
				$list['card_info'] 	= $card_info['bank_name'].'<span class="gray">( '.Language::get($card_info['type']).','.$card_info['account_name'].','.$card_info['num'].','.$card_info['open_bank'].' )</span>';
				$list['status'] 	= $status_label;
				$result['list'][$key] = $list;
			}
			return Page::flexigridXML($result);
		}
	}
	/* 提现审核（通过） */
	public function actionDrawadopt()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);
		if(!$post->id || !($draw = DepositWithdrawModel::find()->alias('dw')->select('dw.*,dt.tradeNo,dt.status,dt.amount')->joinWith('depositTrade dt', false)->where(['draw_id' => $post->id])->asArray()->one())) {
			return Message::warning(Language::get('no_such_draw'));
		}
		if($draw['status'] != 'WAIT_ADMIN_VERIFY') {
			return Message::warning(Language::get('verify_error'));
		}
		
		// 变更交易状态
		if(($model = DepositTradeModel::find()->where(['tradeNo' => $draw['tradeNo']])->one())) {
			$model->status = 'SUCCESS';
			$model->end_time = Timezone::gmtime();
			if(!$model->save()) {
				return Message::warning($model->errors);
			}
			// 扣减当前用户的冻结金额
			if(!DepositAccountModel::updateDepositFrozen($draw['userid'], $draw['amount'], 'reduce')) {
				// TODO...
			}
		}
		return Message::display(Language::get('verify_ok'));
	}
	
	/* 提现审核（拒绝） */
	public function actionDrawrefuse()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);
		if(!$post->id || !($draw = DepositWithdrawModel::find()->alias('dw')->select('dw.*,dt.tradeNo,dt.status,dt.amount')->joinWith('depositTrade dt', false)->where(['draw_id' => $post->id])->asArray()->one())) {
			return Message::warning(Language::get('no_such_draw'));
		}
		if($draw['status'] != 'WAIT_ADMIN_VERIFY') {
			return Message::warning(Language::get('verify_error'));
		}
		if(!$post->remark) {
			return Message::warning(Language::get('refuse_remark_empty'));
		}
		
		// 变更交易状态
		if(($model = DepositTradeModel::find()->where(['tradeNo' => $draw['tradeNo']])->one())) {
			$model->status = 'CLOSED';
			$model->end_time = Timezone::gmtime();
			if(!$model->save()) {
				return Message::warning($model->errors);
			}
			// 管理员增加备注（拒绝原因）
			DepositRecordModel::updateAll(['remark' => $post->remark], ['tradeNo' => $draw['tradeNo'], 'userid' => $draw['userid'], 'tradeType' => 'WITHDRAW']);
		
			// 扣减当前用户的冻结金额
			if(!DepositAccountModel::updateDepositFrozen($draw['userid'], $draw['amount'], 'reduce')) {
				// TODO...
			}
			// 将冻结金额退回到账户余额（变更账户余额）
			$record = new DepositRecordModel();
			$record->tradeNo = $draw['tradeNo'];
			$record->userid = $draw['userid'];
			$record->amount = $draw['amount'];
			$record->balance = DepositAccountModel::updateDepositMoney($draw['userid'], $draw['amount']);
			$record->tradeType =  'TRANSFER';
			$record->tradeTypeName = Language::get('draw_return');
			$record->flow = 'income';
			$record->remark = $post->remark;
			if(!$record->save()) {
				// TODO...
			}
		}
		return Message::display(Language::get('refuse_draw_ok'));
	}
	
	/* 管理员手动给账户充值 */
	public function actionRecharge()
	{
		$id = intval(Yii::$app->request->get('id', 0));
		if(!$id || !($account = DepositAccountModel::findOne($id))) {
			return Message::warning(Language::get('no_such_account'));
		}
		if(!Yii::$app->request->isPost)
		{
			$this->params['account'] = ArrayHelper::toArray($account);
			
			$this->params['page'] = Page::seo(['title' => Language::get('deposit_recharge')]);
			return $this->render('../deposit.recharge.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			
			$model = new \backend\models\DepositRechargeForm(['userid' => $account->userid]);
			if(!($store = $model->save($post, true))) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_ok'), ['deposit/rechargelist']);
		}
	}
	
	public function actionRechargelist()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['rp', 'page']);
		
		if(!Yii::$app->request->isAjax) 
		{
			$this->params['filtered'] = $this->getTradeConditions($post);
			$this->params['status_list'] = array(
				'PENDING' => Language::get('TRADE_PENDING'),
				'SUCCESS' => Language::get('TRADE_SUCCESS'),
				'CLOSED'  => Language::get('TRADE_CLOSED')
			);
			$this->params['search_options'] = array('tradeNo' => Language::get('tradeNo'), 'orderId' => Language::get('orderId'), 'username' => Language::get('username'));
		
			$this->params['_foot_tags'] = Resource::import([
				'script' => 'jquery.plugins/flexigrid.js,jquery.ui/jquery.ui.js,jquery.ui/i18n/' . Yii::$app->language . '.js',
            	'style'=> 'jquery.ui/themes/smoothness/jquery.ui.css'
			]);
			
			$this->params['page'] = Page::seo(['title' => Language::get('deposit_rechargelist')]);
			return $this->render('../deposit.rechargelist.html', $this->params);
		}
		else
		{
			$query = DepositRechargeModel::find()->alias('dr')->select('dr.*,dt.trade_id,dt.tradeNo,dt.add_time,dt.amount,dt.status,u.username')->joinWith('depositTrade dt', false)->joinWith('user u', false)->indexBy('recharge_id');
			$query = $this->getTradeConditions($post, $query);
			
			$orderFields = ['orderId','tradeNo', 'username', 'add_time','amount','status', 'examine'];
			if(in_array($post->sortname, $orderFields) && in_array(strtolower($post->sortorder), ['asc', 'desc'])) {
				$query->orderBy([$post->sortname => strtolower($post->sortorder) == 'asc' ? SORT_ASC : SORT_DESC]);
			} else $query->orderBy(['recharge_id' => SORT_DESC]);
			
			$page = Page::getPage($query->count(), $post->rp ? $post->rp : 10);
			
			$result = ['page' => $post->page, 'total' => $query->count()];
			foreach ($query->offset($page->offset)->limit($page->limit)->asArray()->each() as $key => $val)
			{
				$list = array();
				$operation = "<a class='btn red' onclick=\"fg_delete({$key},'deposit','rechargedelete')\"><i class='fa fa-trash-o'></i>删除</a>";
				$list['operation'] 	= $operation;
				$list['add_time'] 	= Timezone::localDate('Y-m-d H:i:s', $val['add_time']);
				$list['tradeNo'] 	= $val['tradeNo'];
				$list['orderId'] 	= $val['orderId'];
				$list['username'] 	= $val['username'];
				$list['name'] 		= Language::get('recharge');
				$list['amount'] 	= $val['amount'];
				$list['remark'] 	= DepositRecordModel::find()->select('remark')->where(['tradeNo' => $val['tradeNo'], 'userid' => $val['userid']])->scalar();
				$list['status'] 	= Language::get(strtolower($val['status']));
				$list['examine'] 	= $val['examine'];
				$result['list'][$key] = $list;
			}
			return Page::flexigridXML($result);
		}
	}
	
	/* 月账单下载 */
	public function actionMonthbill()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['rp', 'page', 'userid']);
		
		if(!Yii::$app->request->isAjax) 
		{
			if(!$post->userid || !($user = UserModel::find()->select('userid,username')->where(['userid' => $post->userid])->asArray()->one())) {
				return Message::warning(Language::get('no_such_user'));
			}
			$this->params['user'] = $user;
			
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/flexigrid.js');
			
			$this->params['page'] = Page::seo(['title' => Language::get('deposit_monthbill')]);
			return $this->render('../deposit.monthbill.html', $this->params);
		}
		else
		{
			$query = DepositRecordModel::find()->alias('dr')->select('')->joinWith('depositTrade dt', false)->where(['status' => 'SUCCESS', 'userid' => $post->userid])->andWhere(['>', 'end_time', 0])->indexBy('record_id')->orderBy(['record_id' => SORT_DESC]);
			
			$page = Page::getPage($query->count(), $post->rp ? $post->rp : 10);
			
			
			$monthbill = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
			
			// 按月进行归类
			$bill_list = array();
			foreach($monthbill as $key => $bill) {
				$year_month = Timezone::localDate('Y-m', $bill['end_time']);
				$bill_list[$year_month][$bill['flow'].'_money'] += $bill['amount'];
				$bill_list[$year_month][$bill['flow'].'_count'] += 1;
				
				// 如果是支出，判断是否是服务费
				if($bill['flow'] == 'outlay' && ($bill['tradeType'] == 'SERVICE')) {
					$bill_list[$year_month][$bill['tradeType'].'_money'] += $bill['amount'];
					$bill_list[$year_month][$bill['tradeType'].'_count'] += 1;
				}
			}
			$result = ['page' => $post->page, 'total' => count($bill_list)];
			foreach ($bill_list as $key => $val)
			{
				$list = array();
				$operation = "<a class='btn green' href='".Url::toRoute(['deposit/downloadbill', 'userid' => $post->userid, 'month' => $key])."'><i class='fa fa-download'></i>下载</a>";
				$list['operation']    = $operation;
				$list['month'] 		  = $key;
				$list['income_count'] = $val['income_count'] ? $val['income_count'] : 0;
				$list['income_money'] = $val['income_money'] ? $val['income_money'] : 0;
				$list['outlay_count'] = $val['outlay_count'] ? $val['outlay_count'] : 0;
				$list['outlay_money'] = $val['outlay_money'] ? $val['outlay_money'] : 0;
				$list['charge_count'] = $val['SERVICE_count'] ? $val['SERVICE_count'] : 0;
				$list['charge_money'] = $val['SERVICE_money'] ? $val['SERVICE_money'] : 0;
				$result['list'][$key] = $list;
			}
			return Page::flexigridXML($result);
		}
	}
	
	/* 下载某个用户某个月的对账单 */
	public function actionDownloadbill()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['userid']);
		if(!$post->userid || !$post->month) {
			return Message::warning(Language::get('downloadbill_fail'));
		}
		return DepositAccountModel::downloadbill($post->userid, $post->month);
	}	
	
	public function actionExport()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		if($post->id) $post->id = explode(',', $post->id);
		
		if($post->model == 'account') 
		{
			$query = DepositAccountModel::find()->alias('da')->select('da.*,u.username')->joinWith('user u', false)->indexBy('account_id')->orderBy(['account_id' => SORT_DESC]);
			if(!empty($post->id)) {
				$query->andWhere(['in', 'account_id', $post->id]);
			}
			else {
				$query = $this->getConditions($post, $query);
			}
			if($query->count() == 0) {
				return Message::warning(Language::get('no_such_account'));
			}
			$model = new \backend\models\DepositAccountExportForm();
		}
		if($post->model == 'trade') 
		{
			$query = DepositTradeModel::find()->alias('dt')->select('dt.trade_id,dt.tradeNo,dt.bizOrderId,dt.title,dt.amount,dt.status,dt.flow,dt.buyer_id,dt.add_time,dab.account,dab.real_name')->joinWith('depositAccountBuyer dab',false)->indexBy('trade_id')->orderBy(['trade_id' => SORT_DESC]);
			if(!empty($post->id)) {
				$query->andWhere(['in', 'trade_id', $post->id]);
			}
			else {
				$query = $this->getTradeConditions($post, $query);
			}
			if($query->count() == 0) {
				return Message::warning(Language::get('no_such_trade'));
			}
			$model = new \backend\models\DepositTradeExportForm();
		}
		if($post->model == 'draw') 
		{
			$query = DepositWithdrawModel::find()->alias('dw')->select('dw.*,dt.trade_id,dt.tradeNo,dt.add_time,dt.amount,dt.status,u.username')->joinWith('depositTrade dt', false)->joinWith('user u', false)->indexBy('draw_id')->orderBy(['draw_id' => SORT_DESC]);
			if(!empty($post->id)) {
				$query->andWhere(['in', 'draw_id', $post->id]);
			}
			else {
				$query = $this->getTradeConditions($post, $query);
			}
			if($query->count() == 0) {
				return Message::warning(Language::get('no_such_draw'));
			}
			$model = new \backend\models\DepositDrawExportForm();
		}
		if($post->model == 'recharge') 
		{
			$query = DepositRechargeModel::find()->alias('dw')->select('dw.*,dt.trade_id,dt.tradeNo,dt.add_time,dt.amount,dt.status,u.username')->joinWith('depositTrade dt', false)->joinWith('user u', false)->indexBy('recharge_id')->orderBy(['recharge_id' => SORT_DESC]);
			if(!empty($post->id)) {
				$query->andWhere(['in', 'recharge_id', $post->id]);
			}
			else {
				$query = $this->getTradeConditions($post, $query);
			}
			if($query->count() == 0) {
				return Message::warning(Language::get('no_such_recharge'));
			}
			$model = new \backend\models\DepositRechargeExportForm();
		}
		return $model->download($query->asArray()->all());
	}
	
	private function getSearchOption()
	{
		return array(
            'account'	=> Language::get('account'),
			'username' 	=> Language::get('username'),
            'real_name' => Language::get('real_name'),
		);
	}
	
	private function getPayStatus()
	{
		return array('ON' => Language::get('yes'), 'OFF'=> Language::get('no'));
	}
	
	private function getConditions($post, $query = null)
	{
		if($query === null) {
			foreach(array_keys(ArrayHelper::toArray($post)) as $field) {
				if(in_array($field, ['search_name', 'pay_status', 'add_time_from', 'add_time_to', 'money_from', 'money_to'])) {
					return true;
				}
			}
			return false;
		}
		
		if($post->field && $post->search_name && in_array($post->field, array_keys($this->getSearchOption()))) {
			$query->andWhere([$post->field => $post->search_name]);
		}
		if($post->pay_status) {
			$query->andWhere(['pay_status' => (strtoupper($post->pay_status) == 'ON') ? 'ON' : 'OFF']);
		}
		if($post->add_time_from) $post->add_time_from = Timezone::gmstr2time($post->add_time_from);
		if($post->add_time_to) $post->add_time_to = Timezone::gmstr2time_end($post->add_time_to);
		if($post->add_time_from && $post->add_time_to) {
			$query->andWhere(['and', ['>=', 'add_time', $post->add_time_from], ['<=', 'add_time', $post->add_time_to]]);
		}
		if($post->add_time_from && (!$post->add_time_to || ($post->add_time_to <= $post->add_time_from))) {
			$query->andWhere(['>=', 'add_time', $post->add_time_from]);
		}
		if(!$post->add_time_from && ($post->add_time_to && ($post->add_time_to > Timezone::gmtime()))) {
			$query->andWhere(['<=', 'add_time', $post->add_time_to]);
		}
		
		if($post->money_from) $post->money_from = floatval($post->money_from);
		if($post->money_to) $post->money_to = floatval($post->money_to);
		if($post->money_from && $post->money_to) {
			$query->andWhere(['and', ['>=', 'money', $post->money_from], ['<=', 'money', $post->money_to]]);
		}
		if($post->money_from && (!$post->money_to || ($post->money_to < 0))) {
			$query->andWhere(['>=', 'money', $post->money_from]);
		}
		if(!$post->money_from && ($post->money_to > 0)) {
			$query->andWhere(['<=', 'money', $post->money_to]);
		}
		return $query;
	}

	private function getTradeConditions($post, $query = null)
	{
		if($query === null) {
			foreach(array_keys(ArrayHelper::toArray($post)) as $field) {
				if(in_array($field, ['search_name', 'status', 'add_time_from', 'add_time_to', 'amount_from', 'amount_to'])) {
					return true;
				}
			}
			return false;
		}
		
		if($post->field && $post->search_name && in_array($post->field, ['bizOrderId', 'orderId', 'tradeNo', 'username'])) {
			$query->andWhere([$post->field => $post->search_name]);
		}
		if($post->status) {
			$query->andWhere(['dt.status' => $post->status]);
		}
		if($post->add_time_from) $post->add_time_from = Timezone::gmstr2time($post->add_time_from);
		if($post->add_time_to) $post->add_time_to = Timezone::gmstr2time_end($post->add_time_to);
		if($post->add_time_from && $post->add_time_to) {
			$query->andWhere(['and', ['>=', 'dt.add_time', $post->add_time_from], ['<=', 'dt.add_time', $post->add_time_to]]);
		}
		if($post->add_time_from && (!$post->add_time_to || ($post->add_time_to <= $post->add_time_from))) {
			$query->andWhere(['>=', 'dt.add_time', $post->add_time_from]);
		}
		if(!$post->add_time_from && ($post->add_time_to && ($post->add_time_to > Timezone::gmtime()))) {
			$query->andWhere(['<=', 'dt.add_time', $post->add_time_to]);
		}
		
		if($post->amount_from) $post->amount_from = floatval($post->amount_from);
		if($post->amount_to) $post->amount_to = floatval($post->amount_to);
		if($post->amount_from && $post->amount_to) {
			$query->andWhere(['and', ['>=', 'dt.amount', $post->amount_from], ['<=', 'dt.amount', $post->amount_to]]);
		}
		if($post->amount_from && (!$post->amount_to || ($post->amount_to < 0))) {
			$query->andWhere(['>=', 'dt.amount', $post->amount_from]);
		}
		if(!$post->amount_from && ($post->amount_to > 0)) {
			$query->andWhere(['<=', 'dt.amount', $post->amount_to]);
		}
		return $query;
	}
}
