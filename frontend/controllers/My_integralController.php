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

use common\models\IntegralSettingModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;

/**
 * @Id My_integralController.php 2018.4.17 $
 * @author mosir
 */

class My_integralController extends \common\controllers\BaseUserController
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
		$model = new \frontend\models\My_integralForm();
		if(($integral = $model->formData()) === false) {
			return Message::warning($model->errors);
		}
		$this->params['integral'] = $integral;
		
		$this->params['page'] = Page::seo(['title' => Language::get('integral_log')]);
        return $this->render('../my_integral.index.html', $this->params);
	}
	
	public function actionLogs()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['page', 'pageper']);
		$curmenu = empty($post->type) ? 'integral_log' : 'integral_'.$post->type;
		
		if(Yii::$app->request->isAjax)
		{
			$model = new \frontend\models\My_integralLogsForm();
			list($integralLogs, $page) = $model->formData($post, $post->pageper);
			
			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			return array('result' => array_values($integralLogs), 'totalPage' => $page->getPageCount());
		}
		else
		{
			if(!IntegralSettingModel::getSysSetting('enabled')) {
				return Message::warning(Language::get('integral_disabled'));
			}
		
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.infinite.js');
			$this->params['infiniteParams'] = json_encode(ArrayHelper::toArray($post));
			
			$this->params['page'] = Page::seo(['title' => Language::get($curmenu)]);
			return $this->render('../my_integral.logs.html', $this->params);
		}
	}
	
	/* 签到送积分 */
	public function actionSign()
	{
		$model = new \frontend\models\My_integralSignForm();
		if(!($result = $model->submit())) {
			return Message::warning($model->errors);
		}
		list($amount, $signAmount) = $result;
		return Message::result(['amount' => $amount], sprintf(Language::get('signin_integral_successed'), $signAmount));
	}
}