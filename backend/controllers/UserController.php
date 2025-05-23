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
use yii\captcha\CaptchaValidator;
use yii\helpers\Url;

use common\models\UserModel;
use common\models\UserPrivModel;
use common\models\UserEnterModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Timezone;
use common\library\Page;

/**
 * @Id UserController.php 2018.7.25 $
 * @author mosir
 */

class UserController extends \common\controllers\BaseAdminController
{
	/**
	 * 初始化
	 */
	public function init()
	{
		parent::init();
	}
	
	public function beforeAction($action)
    {
		$this->extraAction = ['login', 'logout', 'jslang'];
		return parent::beforeAction($action);
    }
	
    public function actionIndex() 
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['rp', 'page']);
		
		if(!Yii::$app->request->isAjax) 
		{
			$this->params['filtered'] = $this->getConditions($post);
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/flexigrid.js');
			$this->params['page'] = Page::seo(['title' => Language::get('user_list')]);
			return $this->render('../user.index.html', $this->params);
		}
		else
		{
			$query = UserModel::find()->alias('u')->select('u.*, up.store_id as priv_store_id, up.privs,s.store_id')->joinWith('userPriv up', false)->joinWith('store s', false)->indexBy('userid');
			$query = $this->getConditions($post, $query);

			$orderFields = ['username', 'real_name', 'email', 'phone_mob', 'create_time', 'last_login', 'logins', 'last_ip'];
			if(in_array($post->sortname, $orderFields) && in_array(strtolower($post->sortorder), ['asc', 'desc'])) {
				$query->orderBy([$post->sortname => strtolower($post->sortorder) == 'asc' ? SORT_ASC : SORT_DESC]);
			} else $query->orderBy(['userid' => SORT_ASC]);
			
			$page = Page::getPage($query->count(), $post->rp ? $post->rp : 10);
			
			$result = ['page' => $post->page, 'total' => $query->count()];
			foreach ($query->offset($page->offset)->limit($page->limit)->asArray()->each() as $key => $val)
			{
				$list = array();
				$operation = "<a class='btn red' onclick=\"fg_delete({$key},'user')\"><i class='fa fa-trash-o'></i>删除</a>";
				$operation .= "<a class='btn blue' href='".Url::toRoute(['user/edit', 'id' => $key])."'><i class='fa fa-pencil-square-o'></i>编辑</a><a class='btn red' href='".Url::toRoute(['user/enter', 'id' => $key])."'><i class='fa fa-clock-o'></i>登陆记录</a>";
				$list['operation'] = $operation;
				$list['username'] = $val['username'];
				$list['real_name'] = $val['real_name'];
				$list['email'] = $val['email'];
				$list['phone_mob'] = $val['phone_mob'];
				$userPriv = UserPrivModel::find()->select('privs')->where(['userid' => $key, 'store_id' => 0])->one();
				$list['if_admin'] = $userPriv['privs'] ? "<em class='yes'><i class='fa fa-check-circle'></i>是</em>" : "<a href='".Url::toRoute(['admin/add', 'id' => $key])."'>设为管理员</a>";
				$list['create_time'] = Timezone::localDate('Y-m-d', $val['create_time']);
				$list['logins'] = $val['logins'];
				$list['last_login'] = Timezone::localDate('Y-m-d H:i:s', $val['last_login']);
				$list['last_ip'] = $val['last_ip'];
				$result['list'][$key] = $list;
			}
			return Page::flexigridXML($result);
		}
	}
	
	public function actionAdd()
	{
		if(!Yii::$app->request->isPost)
		{
			$this->params['user'] = array('gender' => 0);
			$this->params['page'] = Page::seo(['title' => Language::get('user_add')]);
			return $this->render('../user.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['gender', 'imforbid', 'locked']);
			
			$model = new \backend\models\UserForm();
			if(!($user = $model->save($post, true))) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('add_ok'), ['user/index']);
		}
	}
	
	public function actionEdit()
	{
		$id = intval(Yii::$app->request->get('id'));
		
		if(!Yii::$app->request->isPost)
		{
			$this->params['user'] = UserModel::get($id);
			
			$this->params['page'] = Page::seo(['title' => Language::get('user_edit')]);
			return $this->render('../user.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['gender', 'imforbid', 'locked']);
			
			$model = new \backend\models\UserForm(['userid' => $id]);
			if(!($user = $model->save($post, true))) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_ok'), ['user/index']);
		}
	}
	
	public function actionDelete()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		
		if(empty($post->id)) {
			return Message::warning(Language::get('no_user_to_drop'));
		}
		if(UserPrivModel::isManager($post->id)) {
			return Message::warning(Language::get('cannot_drop_admin'));
		}

		$model = new \backend\models\UserDeleteForm(['userid' => $post->id]);
		if(!$model->delete($post, true)) {
			return Message::warning($model->errors);
		}
		if(in_array(Yii::$app->user->id, explode(',', $post->id))) {
			Yii::$app->user->logout();
		}
		return Message::display(Language::get('drop_ok'), ['user/index']);
	}
	
	public function actionExport()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		if($post->id) $post->id = explode(',', $post->id);
		
		$query = UserModel::find()->indexBy('userid')->orderBy(['userid' => SORT_DESC]);
		if(!empty($post->id)) {
			$query->andWhere(['in', 'userid', $post->id]);
		}
		else {
			$query = $this->getConditions($post, $query);
		}
		if($query->count() == 0) {
			return Message::warning(Language::get('no_such_user'));
		}
		return \backend\models\UserExportForm::download($query->all());		
	}

    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }
		
		if(!Yii::$app->request->isPost)
		{
			$this->params['page'] = Page::seo(['title' => Language::get('user_login')]);
			return $this->render('../login.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			
			if(isset(Yii::$app->params['captcha_status']['backend']) && Yii::$app->params['captcha_status']['backend']) {
				$captchaValidator = new CaptchaValidator(['captchaAction' => 'default/captcha']);
				if(!$captchaValidator->validate($post->captcha)) {
					return Message::warning(Language::get('captcha_failed'));
				}
			}
			
			if(empty($post->username) || empty($post->password)) {
				return Message::warning(Language::get('username_password_error'));
			}
			
			$identity = UserModel::find()->where(['username' => $post->username])->one();
			if(!$identity) {
				return Message::warning(Language::get('username_password_error'));
			}
			if(!$identity->validatePassword($post->password)) {
				return Message::warning(Language::get('username_password_error'));
			}
			if(!UserPrivModel::isManager($identity->userid)) {
				return Message::warning(Language::get('not_admin'));
			}
			if(!Yii::$app->user->login($identity)) {
				return Message::warning(Language::get('login_failure'));
			}
			UserModel::afterLogin($identity);
			return Message::display(Language::get('login_successed'), ['default/index']);
		}
    }
	
	public function actionEnter()
	{
		if(!Yii::$app->request->isAjax) 
		{
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/flexigrid.js');
			$this->params['page'] = Page::seo(['title' => Language::get('user_enter')]);
			return $this->render('../user.enter.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);
			
			$query = UserEnterModel::find()->where(['userid' => $post->id]);
			
			$orderFields = ['ip', 'region_name', 'add_time'];
			if(in_array($post->sortname, $orderFields) && in_array(strtolower($post->sortorder), ['asc', 'desc'])) {
				$query->orderBy([$post->sortname => strtolower($post->sortorder) == 'asc' ? SORT_ASC : SORT_DESC]);
			} else $query->orderBy(['id' => SORT_DESC]);
			
			$page = Page::getPage($query->count(), $post->rp ? $post->rp : 10);
			
			$result = ['page' => $post->page, 'total' => $query->count()];
			foreach ($query->offset($page->offset)->limit($page->limit)->asArray()->each() as $key => $val)
			{
				$list = array();
				$list['username'] = $val['username'];
				$list['ip'] = $val['ip'];
				$list['region_name'] = $val['region_name'];
				$list['add_time'] = Timezone::localDate('Y-m-d H:i:s', $val['add_time']);
				$result['list'][$key] = $list;
			}
			return Page::flexigridXML($result);
		}
	}
	
	public function actionLogout()
    {
        Yii::$app->user->logout();
        return $this->redirect(Yii::$app->user->loginUrl);
    }
	
	/* 新增用户走势（图表）本月和上月的数据统计 */
	public function actionTrend()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		
		list($curMonthQuantity, $curDays, $beginMonth, $endMonth) = $this->getMonthTrend(Timezone::gmtime());
		list($preMonthQuantity, $preDays) = $this->getMonthTrend($beginMonth - 1);
		
		$series = array($curMonthQuantity, $preMonthQuantity);
		$legend = array('本月新增用户数','上月用户数');
		
		$days = $curDays > $preDays ? $curDays : $preDays;
		
		// 获取日期列表
		$xaxis = array();
		for($day = 1; $day <= $days; $day++) {
			$xaxis[] = $day.'日';
		}

		$this->params['echart'] = array(
			'id'		=>  mt_rand(),
			'theme' 	=> 'macarons',
			'width'		=> 890,
			'height'    => 288,
			'option'  	=> json_encode([
				'grid' => ['left' => '10', 'right' => '0', 'top' => '50', 'bottom' => '30', 'containLabel' => true],
				'tooltip' 	=> ['trigger' => 'axis'],
				'legend'	=> [
					'data' => $legend
				],
				'calculable' => true,
   				'xAxis' => [
        			[
						'type' => 'category', 
						'data' => $xaxis
        			]
    			],
				'yAxis' => [
        			[
            			'type' => 'value'
        			]
   				 ],
				 'series' => [
					[
						'name' => $legend[0],
						'type' => 'bar',
						'data' => $series[0],
					],
					[
						'name' => $legend[1],
						'type' => 'bar',
						'data' => $series[1],
					]
				]
			])
		);
		
		return $this->render('../echarts.html', $this->params);
	}
	
	/* 月数据统计 */
	private function getMonthTrend($month = 0)
	{
		// 本月
		if(!$month) $month = Timezone::gmtime();
		
		// 获取当月的开始时间戳和结束那天的时间戳
		list($beginMonth, $endMonth) = Timezone::getMonthDay(Timezone::localDate('Y-m', $month));
		
		$list = UserModel::find()->select('create_time')->where(['>=', 'create_time', $beginMonth])->andWhere(['<=', 'create_time', $endMonth])->asArray()->all();
		
		// 该月有多少天
		$days = round(($endMonth-$beginMonth) / (24 * 3600));
		
		// 按天算归类
		$amount = $quantity = array();
		foreach($list as $key => $val)
		{
			$day = Timezone::localDate('d', $val['create_time']);
	
			if(isset($quantity[$day-1])) {
				$quantity[$day-1]++;
			}
			else {
				$quantity[$day-1] = 1;
			}
		}
		
		// 给天数补全
		for($day = 1; $day <= $days; $day++)
		{
			if(!isset($quantity[$day-1])) {
				$quantity[$day-1] = 0;
			}
		}
		// 按日期顺序排序
		ksort($quantity);

		return array($quantity, $days, $beginMonth, $endMonth);
	}
	
	private function getConditions($post, $query = null)
	{
		if($query === null) {
			foreach(array_keys(ArrayHelper::toArray($post)) as $field) {
				if(in_array($field, ['username', 'real_name', 'email', 'phone_mob'])) {
					return true;
				}
			}
			return false;
		}
		if($post->username) {
			$query->andWhere(['username' => $post->username]);
		}
		if($post->real_name) {
			$query->andWhere(['real_name' => $post->real_name]);
		}
		if($post->email) {
			$query->andWhere(['email' => $post->email]);
		}
		if($post->phone_mob) {
			$query->andWhere(['phone_mob' => $post->phone_mob]);
		}
		return $query;
	}
}