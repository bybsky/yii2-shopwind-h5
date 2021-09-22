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

use common\models\MsgModel;
use common\models\MsgLogModel;
use common\models\MsgTemplateModel;
use common\models\UserModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Timezone;
use common\library\Page;
use common\library\Plugin;

/**
 * @Id MsgController.php 2018.8.23 $
 * @author mosir
 */

class MsgController extends \common\controllers\BaseAdminController
{
	/**
	 * 初始化
	 */
	public function init()
	{
		parent::init();
	}
	
	/**
	 * 短信发送页面
	 */
	public function actionIndex()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['rp', 'page']);

		if(!Yii::$app->request->isAjax) 
		{
			// 发送平台列表数组，从这里读取平台名称
			foreach(Plugin::getInstance('sms')->build()->getList() as $key => $value) {
				$this->params['smslist'][$key] = $value['name'];
			}

			$this->params['filtered'] = $this->getConditions($post);
			$this->params['status_list'] = array(Language::get('send_failed'), Language::get('send_success'));
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/flexigrid.js');
			
			$this->params['page'] = Page::seo(['title' => Language::get('msg_list')]);
			return $this->render('../msg.index.html', $this->params);
		}
		else
		{
			$query = MsgLogModel::find()->alias('ml')->select('ml.*,u.username')->joinWith('user u', false)->where(['ml.type' => 0])->indexBy('id');
			$query = $this->getConditions($post, $query);
			
			$orderFields = ['receiver', 'code', 'content','quantity','add_time','username','status','message'];
			if(in_array($post->sortname, $orderFields) && in_array(strtolower($post->sortorder), ['asc', 'desc'])) {
				$query->orderBy([$post->sortname => strtolower($post->sortorder) == 'asc' ? SORT_ASC : SORT_DESC]);
			} else $query->orderBy(['id' => SORT_DESC]);
			
			$page = Page::getPage($query->count(), $post->rp ? $post->rp : 10);
			
			$result = ['page' => $post->page, 'total' => $query->count()];
			foreach ($query->offset($page->offset)->limit($page->limit)->asArray()->each() as $key => $val)
			{
				$list = array();
				$list['operation'] 	= "<a class='btn red' onclick=\"fg_delete({$key},'msg')\"><i class='fa fa-trash-o'></i>".Language::get('drop')."</a>";
				$list['msguser'] 	= $val['username'] ? $val['username'] : Language::get('system');
				$list['receiver'] 	= $val['receiver'];
				$list['code'] 		= Plugin::getInstance('sms')->build()->getInfo($val['code'])['name'];
				$list['content'] 	= $val['content'];
				$list['quantity'] 	= $val['quantity'];
				$list['add_time'] 	= Timezone::localDate('Y-m-d H:i:s', $val['add_time']);
				$list['status'] 	= $val['status'] == 1 ? ($val['type'] == 1 ? Language::get('handle_ok') : Language::get('send_success')) : Language::get('send_failed');
				$list['message'] 	= $val['message'];
				$result['list'][$key] = $list;
			}
			return Page::flexigridXML($result);
		}
	}

	/**
	 * 短信充值页面
	 */
	public function actionRecharge()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['rp', 'page']);

		if(!Yii::$app->request->isAjax) 
		{
			$this->params['filtered'] = $this->getConditions($post);
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/flexigrid.js');
			
			$this->params['page'] = Page::seo(['title' => Language::get('msg_list')]);
			return $this->render('../msg.recharge.html', $this->params);
		}
		else
		{
			$query = MsgLogModel::find()->alias('ml')->select('ml.*,u.username')->joinWith('user u', false)->where(['ml.type' => 1])->indexBy('id');
			$query = $this->getConditions($post, $query);
			
			$orderFields = ['quantity','add_time','username','status','message'];
			if(in_array($post->sortname, $orderFields) && in_array(strtolower($post->sortorder), ['asc', 'desc'])) {
				$query->orderBy([$post->sortname => strtolower($post->sortorder) == 'asc' ? SORT_ASC : SORT_DESC]);
			} else $query->orderBy(['id' => SORT_DESC]);
			
			$page = Page::getPage($query->count(), $post->rp ? $post->rp : 10);
			
			$result = ['page' => $post->page, 'total' => $query->count()];
			foreach ($query->offset($page->offset)->limit($page->limit)->asArray()->each() as $key => $val)
			{
				$list = array();
				$list['operation'] 	= "<a class='btn red' onclick=\"fg_delete({$key},'msg')\"><i class='fa fa-trash-o'></i>".Language::get('drop')."</a>";
				$list['msguser'] 	= $val['username'] ? $val['username'] : Language::get('system');
				$list['quantity'] 	= $val['quantity'];
				$list['add_time'] 	= Timezone::localDate('Y-m-d H:i:s', $val['add_time']);
				$list['status'] 	= $val['status'] == 1 ? ($val['type'] == 1 ? Language::get('handle_ok') : Language::get('send_success')) : Language::get('send_failed');
				$list['message'] 	= $val['message'];
				$result['list'][$key] = $list;
			}
			return Page::flexigridXML($result);
		}
	}
	
	public function actionUser()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['rp', 'page']);
		
		if(!Yii::$app->request->isAjax) 
		{
			$this->params['filtered'] = $this->getConditions($post);
			$this->params['status_list'] = array(Language::get('closed'), Language::get('enable'));
		
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/flexigrid.js');
			
			$this->params['page'] = Page::seo(['title' => Language::get('msguser')]);
			return $this->render('../msg.user.html', $this->params);
		}
		else
		{
			$query = MsgModel::find()->alias('msg')->select('msg.*,u.username,u.phone_mob')->joinWith('user u', false)->indexBy('id');
			$query = $this->getConditions($post, $query);
			
			$orderFields = ['username','phone_mob','num','functions','state'];
			if(in_array($post->sortname, $orderFields) && in_array(strtolower($post->sortorder), ['asc', 'desc'])) {
				$query->orderBy([$post->sortname => strtolower($post->sortorder) == 'asc' ? SORT_ASC : SORT_DESC]);
			} else $query->orderBy(['id' => SORT_DESC]);

			// 发送短信的场景
			$functions = Plugin::getInstance('sms')->build()->getFunctions();
			
			$page = Page::getPage($query->count(), $post->rp ? $post->rp : 10);
			$result = ['page' => $post->page, 'total' => $query->count()];
			foreach ($query->offset($page->offset)->limit($page->limit)->asArray()->each() as $key => $val)
			{
				$functionText = '';
				$userFunctions = explode(',', $val['functions']);
				foreach($functions as $k => $v) {
					$checked = in_array($v, $userFunctions) ? 'checked="checked"' : '';
					$functionText .= "<input type='checkbox' onclick='javascript:return false;' {$checked} /> <label class='mr10' title='".Language::get($v)."'>".Language::get($v)."</label>";
				}
				$list = array();
				$list['operation'] = "<a class='btn green' href='".Url::toRoute(['msg/add', 'userid' => $val['userid']])."'><i class='fa fa-pencil-square-o'></i>".Language::get('rechargemsg')."</a>";
				$list['username'] = $val['username'];
				$list['phone_mob'] = $val['phone_mob'];
				$list['functions'] = $functionText;
				$list['num'] = $val['num'];
				$list['state'] = $val['state'] ? '<em class="yes"><i class="fa fa-check-circle"></i>'.Language::get('enable').'</em>' : '<em class="no"><i class="fa fa-ban"></i>'.Language::get('closed').'</em>';
				$result['list'][$key] = $list;
			}
			return Page::flexigridXML($result);
		}
	}
	
	/* 分配短信 */
	public function actionAdd()
	{
		if(!Yii::$app->request->isPost)
		{
			$post = Basewind::trimAll(Yii::$app->request->get(), true, ['userid']);
			$this->params['user'] = UserModel::find()->select('userid,username')->where(['userid' => $post->userid])->asArray()->one();
			
			$this->params['page'] = Page::seo(['title' => Language::get('msg_add')]);
			return $this->render('../msg.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['num']);
			
			$model = new \backend\models\MsgForm();
			if(!$model->save($post, true)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('handle_ok'), ['msg/user']);
		}
	}
	
	public function actionDelete()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		$post->id = explode(',', $post->id);
		if(is_array($post->id) && !empty($post->id)) {
			MsgLogModel::deleteAll(['in', 'id', $post->id]);
		}
		return Message::display(Language::get('drop_ok'));
	}

	/**
	 * 短信模板列表页面
	 */
	public function actionTemplate()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['rp', 'page']);

		if(!Yii::$app->request->isAjax) 
		{
			$this->params['filtered'] = $this->getConditions($post);
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/flexigrid.js');
			
			$this->params['page'] = Page::seo(['title' => Language::get('msg_templatelist')]);
			return $this->render('../msg.template.html', $this->params);
		}
		else
		{
			// 发送平台列表数组，从这里读取平台名称
			foreach(Plugin::getInstance('sms')->build()->getList() as $key => $value) {
				$smslist[$key] = $value['name'];
			}

			$query = MsgTemplateModel::find()->indexBy('id');
			$query = $this->getConditions($post, $query);

			$page = Page::getPage($query->count(), $post->rp ? $post->rp : 10);
			
			$result = ['page' => $post->page, 'total' => $query->count()];
			foreach ($query->offset($page->offset)->limit($page->limit)->asArray()->each() as $key => $val)
			{
				$list = array();
				$operation 	= "<a class='btn red' onclick=\"fg_delete({$key},'msg', 'deletetemplate')\"><i class='fa fa-trash-o'></i>".Language::get('drop')."</a>";
				$operation .= "<a class='btn green' href='".Url::toRoute(['msg/addtemplate', 'id' => $key, 'code' => $val['code']])."'><i class='fa fa-pencil-square-o'></i>".Language::get('edit')."</a>";
				$list['operation'] = $operation;
				$list['code'] 		= $smslist[$val['code']];
				$list['scene'] 		= Language::get($val['scene']);
				$list['signName'] 	= $val['signName'];
				$list['templateId'] = $val['templateId'];
				$list['content'] 	= $val['content'];
				$list['add_time'] 	= Timezone::localDate('Y-m-d H:i:s', $val['add_time']);
				$result['list'][$key] = $list;
			}
			return Page::flexigridXML($result);
		}
	}

	/**
	 * 新增/编辑短信模板
	 * 阿里大鱼短信平台需要短信模板
	 */
	public function actionAddtemplate()
	{
		$id = Yii::$app->request->get('id', 0);

		if(!Yii::$app->request->isPost)
		{
			if($id && ($template = MsgTemplateModel::find()->where(['id' => $id])->asArray()->one())) {
				$this->params['template'] = $template;
			}

			$smser = Plugin::getInstance('sms')->build();

			// 发送平台列表数组，从这里读取平台名称
			foreach($smser->getList() as $key => $value) {
				$this->params['smslist'][$key] = $value['name'];
			}

			// 发送短信的场景
			$this->params['scenelist'] = $smser->getFunctions(true);
			
			$this->params['page'] = Page::seo(['title' => Language::get('msg_addtemplate')]);
			return $this->render('../msg.template.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			
			$model = new \backend\models\MsgTemplateForm();
			$model->id = $id;
			if(!$model->save($post, true)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('handle_ok'), ['msg/template']);
		}
	}

	/**
	 * 删除短信模板
	 */
	public function actionDeletetemplate()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		$post->id = explode(',', $post->id);
		if(is_array($post->id) && !empty($post->id)) {
			MsgTemplateModel::deleteAll(['in', 'id', $post->id]);
		}
		return Message::display(Language::get('drop_ok'));
	}
	
	public function actionSend()
	{
		if(!Yii::$app->request->isPost)
		{
			$this->params['page'] = Page::seo(['title' => Language::get('msg_send')]);
			return $this->render('../msg.send.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			if(empty($post->content)) {
				return Message::warning(Language::get('content_no_null'));
			}

			$smser = Plugin::getInstance('sms')->autoBuild();
			if(!$smser) {
				return Message::warning(Language::get('send_failed'));
			}

			$smser->receiver = $post->receiver;
			if(!$smser->testsend($post->content)) {
				return Message::warning($smser->errors);
			}

			return Message::display(Language::get('send_success'), ['msg/index']);
		}
	}
	
	public function actionExport()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		if($post->id) $post->id = explode(',', $post->id);
		
		$query = MsgLogModel::find()->alias('ml')->select('ml.*,u.username')->joinWith('user u', false)->indexBy('id')->where(['ml.type' => 0])->orderBy(['id' => SORT_DESC]);
		if(!empty($post->id)) {
			$query->andWhere(['in', 'id', $post->id]);
		}
		else {
			$query = $this->getConditions($post, $query);
		}
		if($query->count() == 0) {
			return Message::warning(Language::get('no_such_msg'));
		}
		return \backend\models\MsgLogExportForm::download($query->asArray()->all());		
	}
	
	private function getConditions($post, $query = null)
	{
		if($query === null) {
			foreach(array_keys(ArrayHelper::toArray($post)) as $field) {
				if(in_array($field, ['username', 'receiver', 'code', 'status', 'phone_mob', 'type', 'templateId'])) {
					return true;
				}
			}
			return false;
		}
		if($post->username) {
			$query->andWhere(['username' => $post->username]);
		}
		// 针对发送记录才有
		if(isset($post->receiver) && $post->receiver) {
			$query->andWhere(['like', 'receiver', $post->receiver]);
		}
		// 针对发送记录才有
		if(isset($post->status) && $post->status !== '') {
			$query->andWhere(['status' => ($post->status == 1) ? 1 : 0]);
		}
		// 针对发送记录才有
		if($post->code) {
			$query->andWhere(['code' => $post->code]);
		}
		// 针对短信用户才有
		if(isset($post->phone_mob) && $post->phone_mob) {
			$query->andWhere(['like', 'phone_mob', $post->phone_mob]);
		}
		// 针对短信用户才有
		if(isset($post->state) && $post->state !== '') {
			$query->andWhere(['state' => ($post->state == 1) ? 1 : 0]);
		}
		// 针对新增/编辑短信模板才有
		if($post->templateId) {
			$query->andWhere(['templateId' => $post->templateId]);
		}

		return $query;
	}
}
