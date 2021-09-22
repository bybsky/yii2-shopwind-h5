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

use common\models\BankModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;

/**
 * @Id BankController.php 2018.4.16 $
 * @author mosir
 */

class BankController extends \common\controllers\BaseUserController
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
			$query = BankModel::find()->where(['userid' => Yii::$app->user->id])->orderBy(['bid' => SORT_DESC]);			
			$page = Page::getPage($query->count(), $post->pageper);
			$banklist = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
			
			if(!empty($banklist)){
				foreach($banklist as $key => $val) {
					$banklist[$key]['type_label'] = Language::get($val['type']);
				}
			}
			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			return array('result' => array_values($banklist), 'totalPage' => $page->getPageCount());
		}
		else
		{
			$this->params['_foot_tags'] = Resource::import([
				'script' => 'jquery.plugins/jquery.form.js,weui/js/jquery-weui.min.js,jquery.plugins/jquery.infinite.js',
				'style'  => 'weui/lib/weui.min.css,weui/css/jquery-weui.min.css'
			]);
			$this->params['infiniteParams'] = json_encode(ArrayHelper::toArray($post));
			
			$this->params['page'] = Page::seo(['title' => Language::get('bank_list')]);
        	return $this->render('../bank.index.html', $this->params);
		}
	}
	
	public function actionAdd()
	{
		if(!Yii::$app->request->isPost)
		{
			$model = new \frontend\models\BankForm();
			$this->params['bankList'] = $model->getBankList();
			
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.form.js');
			
			$this->params['page'] = Page::seo(['title' => Language::get('bank_add')]);
        	return $this->render('../bank.form.html', $this->params);
		}
		else
		{
			$model = new \frontend\models\BankForm(); //$model->setScenario('add');
        	if ($model->load(Yii::$app->request->post(), '') && $model->save()) {
				return Message::display(Language::get('add_ok'), ['bank/index']);
			}
			return Message::warning($model->errors);
		}
	}
	
	public function actionEdit()
	{
		if(!Yii::$app->request->isPost)
		{
			$model = new \frontend\models\BankForm();
			$this->params['bankList'] = $model->getBankList();
			$this->params['card'] = BankModel::find()->where(['bid' => intval(Yii::$app->request->get('bid'))])->asArray()->one();
			
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.form.js');
			
			$this->params['page'] = Page::seo(['title' => Language::get('bank_edit')]);
        	return $this->render('../bank.form.html', $this->params);
		}
		else
		{
			$model = new \frontend\models\BankForm(); //$model->setScenario('update');
        	if ($model->load(Yii::$app->request->post(), '') && $model->save()) {
				return Message::display(Language::get('edit_ok'), ['bank/index']);
			}
			return Message::warning($model->errors);
		}
	}
	
	public function actionDelete()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['bid']);
		if(!$post->bid || !($bank = BankModel::find()->where(['userid' => Yii::$app->user->id, 'bid' => $post->bid])->one())) {
			return Message::warning(Language::get('no_such_bank'));
		}
		if($bank->delete() == false) {
			return Message::warning(Language::get('drop_failed'));	
		}
		return Message::display(Language::get('drop_ok'), ['bank/index']);
	}
}