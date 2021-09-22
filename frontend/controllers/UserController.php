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
use yii\captcha\CaptchaValidator;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

use common\models\UserModel;
use common\models\StoreModel;
use common\models\IntegralSettingModel;
use common\models\UploadedFileModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Timezone;
use common\library\Page;
use common\library\Def;
use common\library\Plugin;

/**
 * @Id UserController.php 2018.3.13 $
 * @author mosir
 */

class UserController extends \common\controllers\BaseUserController
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
	
	/**
	 * 排除特定Action外，其他需要登录后访问
	 * @param $action
	 * @var array $extraAction
	 */
	public function beforeAction($action)
    {
		$this->extraAction = ['index', 'login', 'phonelogin', 'register', 'checkUser', 'checkEmail', 'checkPhone', 'jslang'];
		return parent::beforeAction($action);
    }
	
    public function actionIndex()
    {
		// 在用户中心显示积分
		if(IntegralSettingModel::getSysSetting('enabled')) 
		{
			$this->params['integral_enabled'] = true;
			
			$model = new \frontend\models\My_integralForm();
			if(($integral = $model->formData())) {
				// $this->params['visitor']['integral'] = $integral['amount'];
				$this->params['visitor']['signined'] = $integral['signined'];
			}
		}

		// 买家提醒：待付款、待确认、待评价订单数
		$model = new \frontend\models\UserForm();
		$this->params['buyer_stat'] = $model->getBuyerStat();
		
		// 卖家提醒
        //if(($store = StoreModel::getInfo(Yii::$app->user->id))) // 移动端不需要读那么多数据
		if(($store = StoreModel::find()->select('store_id,state,apply_remark')->where(['store_id' => Yii::$app->user->id])->one()))
        {
			if(in_array($store['state'], [Def::STORE_OPEN, Def::STORE_CLOSED])) {
				// 卖家提醒：已提交、待发货、待回复
				$this->params['seller_stat'] = $model->getSellerStat();
			} 
			else {
				$this->params['apply'] = [
					'state' => $store['state'],
					'remark' => $store['apply_remark']
				];
			}
		}
		
		// 收藏量
		if(($collect = $model->getCollect())) {
			$this->params['visitor'] = array_merge($this->params['visitor'], $collect);
		}
		
		// 当前用户中心菜单（移动端只有该控制器，该方法需要这个数据）
		$this->params['_usermenu'] = Page::setMenu('overview');
		$this->params['page'] = Page::seo(['title' => Language::get('user_center')]);
        return $this->render('../user.index.html', $this->params);
    }

    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
			return $this->goHome();
        }
		
		if(!Yii::$app->request->isPost) 
		{
			if(!($redirect = Yii::$app->request->get('redirect'))) {
				$redirect = Yii::$app->request->referrer ? Yii::$app->request->referrer : Url::toRoute('user/index');
			}
			$this->params['redirect'] = $redirect;
			$this->params['isWeixin'] = Basewind::isWeixin();
			
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.form.js');
			
			$this->params['page'] = Page::seo(['title' => Language::get('user_login')]);
			return $this->render('../login.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['rememberMe']);
			$rememberMe = $post->rememberMe ? 3600 * 24 * 30 : 0;// one month
			
			if(isset(Yii::$app->params['captcha_status']['login']) && Yii::$app->params['captcha_status']['login']) {
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
			if($identity->locked) {
				return Message::warning(Language::get('userlocked'));
			}
			
			// 登录用户
			if(!Yii::$app->user->login($identity, $rememberMe)) {
				return Message::warning(Language::get('login_fail'));
			}
			UserModel::afterLogin($identity);
			return Message::display(Language::get('login_successed'), $post->redirect);
		}
    }
	
	/* 手机及短信验证码登录 */
	public function actionPhonelogin()
	{
		if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }
		
		if(!Yii::$app->request->isPost) 
		{
			if(!($redirect = Yii::$app->request->get('redirect'))) {
				$redirect = Url::toRoute('user/index');
			}
			$this->params['redirect'] = $redirect;
			
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.form.js');
			
			$this->params['page'] = Page::seo(['title' => Language::get('user_login')]);
			return $this->render('../phonelogin.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			
			if(empty($post->code) || (md5($post->phone_mob.$post->code) != Yii::$app->session->get('phone_code') || (Yii::$app->session->get('last_send_time_phone_code') + 120 < Timezone::gmtime()))) {
				return Message::warning(Language::get('phone_code_check_failed'));
			}
			
			$identity = UserModel::find()->where(['phone_mob' => $post->phone_mob])->one();
			if(!$identity) { // 手机号未注册
				$model = new \frontend\models\UserRegisterForm(['username' => UserModel::generateName($post->phone_mob), 'password' => rand(), 'phone_mob' => $post->phone_mob]);
        		if (!($identity = $model->register())) {
					return Message::warning(Language::get('login_fail'));
				}
			}
			if($identity->locked) {
				return Message::warning(Language::get('userlocked'));
			}
			
			// 登录用户
			if(!Yii::$app->user->login($identity)) {
				return Message::warning(Language::get('login_fail'));
			}
			UserModel::afterLogin($identity);
			return Message::display(Language::get('login_successed'), $post->redirect);
		}
	}

    public function actionRegister()
    {
		if (!Yii::$app->user->isGuest) {
			return $this->redirect(['user/index']);
        }
	
		if(!Yii::$app->request->isPost) 
		{
			if(($smser = Plugin::getInstance('sms')->autoBuild())) {
				$this->params['phone_captcha'] = in_array('register', (array) $smser->config['scene']);
			}

			if(!($redirect = Yii::$app->request->get('redirect'))) {
				$redirect = Url::toRoute('user/index');
			}
			$this->params['redirect'] = $redirect;
			
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.form.js');			
			$this->params['page'] = Page::seo(['title' => Language::get('user_register')]);
        	return $this->render('../register.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);

			// 手机短信验证
			if(($smser = Plugin::getInstance('sms')->autoBuild())) {
				if(in_array('register', (array) $smser->config['scene'])) {
					if(empty($post->check_code) || (md5($post->phone_mob.$post->check_code) != Yii::$app->session->get('phone_code'))) {
						return Message::warning(Language::get('phone_code_check_failed'));
					}
					elseif(Timezone::gmtime() - Yii::$app->session->get('last_send_time_phone_code') > 120) {
						return Message::warning(Language::get('phone_code_check_timeout'));
					}
				}
			}
			
			// 验证码验证
			if(isset(Yii::$app->params['captcha_status']['register']) && Yii::$app->params['captcha_status']['register']) {
				$captchaValidator = new CaptchaValidator(['captchaAction' => 'default/captcha']);
				if(!$captchaValidator->validate($post->captcha)) {
					return Message::warning(Language::get('captcha_failed'));
				}
			}
						
			$model = new \frontend\models\UserRegisterForm();
        	if ($model->load(Yii::$app->request->post(), '') && $model->validate() && ($user = $model->register())) {
				if (Yii::$app->user->login($user)) {
         			return Message::display(Language::get('register_successed'), $post->redirect);
				} 
				else { /* validate ok but login failed... */}
			}
			return Message::warning($model->errors);
		}
    }
	
	public function actionSetting()
	{
		$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.form.js');
		
		$this->params['page'] = Page::seo(['title' => Language::get('setting')]);
        return $this->render('../user.setting.html', $this->params);
	}
	
	public function actionProfile()
	{
		if(!Yii::$app->request->isPost)
		{
			$this->params['_foot_tags'] = Resource::import([
				'script' => 'jquery.plugins/jquery.form.js,weui/js/jquery-weui.min.js',
				'style' =>  'weui/lib/weui.min.css,weui/css/jquery-weui.min.css'
			]);
			
			$this->params['page'] = Page::seo(['title' => Language::get('basic_information')]);
			return $this->render('../user.profile.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			
			$model = UserModel::findOne(Yii::$app->user->id);
			foreach($post as $key => $val) {
				if(in_array($key, ['real_name', 'im_qq', 'birthday', 'gender'])) {
					$model->$key = $val;
				}
			}
			if($model->save() == false) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_profile_successed'), false);
		}
	}
	
	/* 上传头像，并保存到数据库 */
	public function actionPortrait()
	{
		$post = Basewind::trimAll(Yii::$app->request->post(), true);
		
		if(!($portrait = UploadedFileModel::getInstance()->upload($post->fileVal, 0, Def::BELONG_PORTRAIT, Yii::$app->user->id, $post->fileVal))) {
			return Message::warning(Language::get('edit_portrait_failed'));
		}
		$model = UserModel::findOne(Yii::$app->user->id);
		$model->portrait = $portrait;
		if($model->save() == false) {
			return Message::warning($model->errors);
		}
		return Message::display(Language::get('edit_portrait_successed'));
	}
	
	/* 修改密码 */
	public function actionPassword()
	{
		if(!Yii::$app->request->isPost) 
		{
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.form.js');
			
			$this->params['page'] = Page::seo(['title' => Language::get('edit_password')]);
			return $this->render('../user.password.html', $this->params);
		}
		else
		{
			$model = new \frontend\models\UserPasswordForm();
        	if ($model->load(Yii::$app->request->post(), '') && $model->validate() && $model->resetPassword()) {
        		return Message::display(Language::get('edit_password_successed'), ['user/index']);
        	} 
			return Message::warning($model->errors);
		}
	}
	
	/* 修改邮箱 */
	public function actionEmail()
	{
		if(!Yii::$app->request->isPost) 
		{
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.form.js');
			
			$this->params['page'] = Page::seo(['title' => Language::get('edit_email')]);
			return $this->render('../user.email.html', $this->params);
		}
		else
		{
			$model = new \frontend\models\UserEmailForm();
        	if ($model->load(Yii::$app->request->post(), '') && $model->validate() && $model->resetEmail()) {
        		return Message::display(Language::get('edit_email_successed'), ['user/index']);
        	}
			return Message::warning($model->errors);
		}
	}
	
	/* 修改手机 */
	public function actionPhone()
	{
		if(!Yii::$app->request->isPost) 
		{
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.form.js');
			
			$this->params['page'] = Page::seo(['title' => Language::get('edit_phone')]);
			return $this->render('../user.phone.html', $this->params);
		}
		else
		{
			$model = new \frontend\models\UserPhoneForm();
        	if ($model->load(Yii::$app->request->post(), '') && $model->validate() && $model->resetPhone()) {
        		return Message::display(Language::get('edit_phone_successed'), ['user/index']);
        	} 
			return Message::warning($model->errors);
		}
	}
	
    public function actionLogout()
    {
        Yii::$app->user->logout();
        return $this->goHome();
    }
	
	/* 三级菜单（移动端只有该控制器需要这个方法） */
    public function getUserSubmenu()
    {
		return array();
    }
}