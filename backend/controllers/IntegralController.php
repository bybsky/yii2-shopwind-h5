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

use common\models\IntegralModel;
use common\models\IntegralLogModel;
use common\models\IntegralSettingModel;
use common\models\UserModel;
use common\models\SgradeModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Timezone;
use common\library\Page;

/**
 * @Id IntegralController.php 2018.8.6 $
 * @author mosir
 */

class IntegralController extends \common\controllers\BaseAdminController
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
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/flexigrid.js');
			$this->params['page'] = Page::seo(['title' => Language::get('integral_list')]);
			return $this->render('../integral.index.html', $this->params);
		}
		else
		{
			$query = UserModel::find()->alias('u')->select('u.userid,u.username,i.amount')->joinWith('integral i', false)->indexBy('userid');
			$query = $this->getConditions($post, $query);
	
			$orderFields = ['userid', 'username', 'amount'];
			if(in_array($post->sortname, $orderFields) && in_array(strtolower($post->sortorder), ['asc', 'desc'])) {
				$query->orderBy([$post->sortname => strtolower($post->sortorder) == 'asc' ? SORT_ASC : SORT_DESC]);
			} else $query->orderBy(['userid' => SORT_ASC]);
			
			$page = Page::getPage($query->count(), $post->rp ? $post->rp : 10);
			
			$result = ['page' => $post->page, 'total' => $query->count()];
			foreach ($query->offset($page->offset)->limit($page->limit)->asArray()->each() as $key => $val)
			{
				$list = array();
				$operation = "<a class='btn blue' href='".Url::toRoute(['integral/recharge', 'id' => $key])."'><i class='fa fa-yen'></i>充值</a>";
				$operation .= "<a class='btn green' href='".Url::toRoute(['integral/logs', 'id' => $key])."'><i class='fa fa-search-plus'></i>查看记录</a>";
				$list['operation'] = $operation;
				$list['userid'] = $val['userid'];
				$list['username'] = $val['username'];
				$list['amount'] = floatval($val['amount']);
				$result['list'][$key] = $list;
			}
			return Page::flexigridXML($result);
		}
	}
	
	public function actionLogs() 
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['rp', 'page', 'id']);
		if(!$post->id) {
			return Message::warning(Language::get('no_such_user'));
		}
		
		if(!Yii::$app->request->isAjax) 
		{
			$this->params['user'] = UserModel::find()->select('userid,username')->where(['userid' => $post->id])->asArray()->one();
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/flexigrid.js');
			$this->params['page'] = Page::seo(['title' => Language::get('integral_logs')]);
			return $this->render('../integral.logs.html', $this->params);
		}
		else
		{
			$query = IntegralLogModel::find()->where(['userid' => $post->id])->indexBy('log_id');
	
			$orderFields = ['type', 'changes', 'balance', 'state', 'add_time', 'flag'];
			if(in_array($post->sortname, $orderFields) && in_array(strtolower($post->sortorder), ['asc', 'desc'])) {
				$query->orderBy([$post->sortname => strtolower($post->sortorder) == 'asc' ? SORT_ASC : SORT_DESC]);
			} else $query->orderBy(['log_id' => SORT_ASC]);
			
			$page = Page::getPage($query->count(), $post->rp ? $post->rp : 10);
			
			$result = ['page' => $post->page, 'total' => $query->count()];
			foreach ($query->offset($page->offset)->limit($page->limit)->asArray()->each() as $key => $val)
			{
				$list = array();
				$list['type'] = Language::get($val['type']);
				$list['changes'] = $val['changes'] > 0 ? "<span class='plus'>+{$val['changes']}</span>" : "<span class='minus'>{$val['changes']}</span>";
				$list['balance'] = $val['balance'];
				$list['state'] = IntegralModel::getStatusLabel($val['state']);
				$list['add_time'] = Timezone::localDate('Y-m-d H:i:s', $val['add_time']);
				$list['flag'] = $val['flag'] . ($val['order_id'] ? sprintf("[订单号：%s] <a href='%s'>查看订单</a>", $val['order_sn'], Url::toRoute(['order/view', 'id' => $val['order_id']])) : "");
				$result['list'][$key] = $list;
			}
			return Page::flexigridXML($result);
		}
	}
	
	public function actionRecharge()
	{
		$id = intval(Yii::$app->request->get('id'));
		
		if(!$id || !($user = UserModel::find()->alias('u')->select('u.userid,u.username,i.amount')->joinWith('integral i', false)->where(['u.userid' => $id])->asArray()->one())) {
			return Message::warning(Language::get('no_such_user'));
		}
		if(!Yii::$app->request->isPost)
		{
			$this->params['user'] = $user;
			
			$this->params['page'] = Page::seo(['title' => Language::get('integral_recharge')]);
			return $this->render('../integral.recharge.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);

			if(!IntegralSettingModel::getSysSetting('enabled')) {
				return Message::warning(Language::get('recharge_valid'));
			}
			
			$model = new \backend\models\IntegralRechargeForm(['userid' => $id]);
			if(!$model->save($post, true)) {
				return Message::warning($model->errors ? $model->errors : Language::get('recharge_fail'));
			}
			return Message::display(Language::get('edit_ok'), Url::toRoute(['integral/logs', 'id' => $id]));
		}
	}
	
	public function actionSetting()
	{
		if(!Yii::$app->request->isPost)
		{
			$sgrades = SgradeModel::find()->select('grade_id,grade_name')->asArray()->all();
			foreach($sgrades as $key => $val) {
				$sgrades[$key]['buygoods'] = IntegralSettingModel::getSysSetting(['buygoods', $val['grade_id']]);
			}
			$this->params['sgrades'] = $sgrades;
			$this->params['setting'] = IntegralSettingModel::getSysSetting();
			
			$this->params['page'] = Page::seo(['title' => Language::get('integral_setting')]);
			return $this->render('../integral.setting.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			
			$model = new \backend\models\IntegralSettingForm();
			if(!$model->save($post, true)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_ok'));	
		}
	}
	
	public function actionExport()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		if($post->id) $post->id = explode(',', $post->id);
		
		$query = UserModel::find()->alias('u')->select('u.userid,u.username,i.amount')->joinWith('integral i', false)->indexBy('userid')->orderBy(['userid' => SORT_ASC]);
		if(!empty($post->id)) {
			$query->andWhere(['in', 'u.userid', $post->id]);
		}
		else {
			$query = $this->getConditions($post, $query);
		}
		if($query->count() == 0) {
			return Message::warning(Language::get('no_such_user'));
		}
		return \backend\models\IntegralExportForm::download($query->asArray()->all());
	}
	
	private function getConditions($post, $query = null)
	{
		if($query === null) {
			foreach(array_keys(ArrayHelper::toArray($post)) as $field) {
				if(in_array($field, ['username'])) {
					return true;
				}
			}
			return false;
		}
		if($post->username) {
			$query->andWhere(['username' => $post->username]);
		}
		return $query;
	}
}