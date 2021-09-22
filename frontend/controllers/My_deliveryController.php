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

use common\models\DeliveryTemplateModel;
use common\models\RegionModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;
use common\library\Timezone;

/**
 * @Id My_deliveryController.php 2018.9.30 $
 * @author luckey
 */

class My_deliveryController extends \common\controllers\BaseSellerController
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
			$query = DeliveryTemplateModel::find()->where(['store_id' => $this->visitor['store_id']])->orderBy(['template_id' => SORT_DESC]);
			$page = Page::getPage($query->count(), $post->pageper);
			$deliverys = DeliveryTemplateModel::formatTemplate($query->offset($page->offset)->limit($page->limit)->asArray()->all());
			foreach($deliverys as $key=>$val){
				$deliverys[$key]['created'] = Timezone::localDate('Y-m-d',$val['created']);
				foreach($val['area_fee'] as $k=>$v){
					$deliverys[$key]['area_fee'][$k]['type'] = Language::get($v['type']);
				}
			}

			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			return array('result' => array_values($deliverys), 'totalPage' => $page->getPageCount());
		}
		else
		{			
			$this->params['_foot_tags'] = Resource::import([
				'script' => 'weui/js/jquery-weui.min.js,jquery.plugins/jquery.infinite.js,jquery.plugins/jquery.form.js',
				'style'  => 'weui/lib/weui.min.css,weui/css/jquery-weui.min.css'
			]);
			$this->params['infiniteParams'] = json_encode(ArrayHelper::toArray($post));

			$this->params['page'] = Page::seo(['title' => Language::get('delivery_list')]);
        	return $this->render('../my_delivery.index.html', $this->params);
		}
	}
	
	public function actionAdd()
	{
		if(!Yii::$app->request->isPost)
		{
			$this->params['delivery'] = ['plus_type' => DeliveryTemplateModel::getPlusType()];
			$this->params['regions'] = RegionModel::find()->select('region_name')->where(['parent_id' => 0])->column();
			
			$this->params['_foot_tags'] = Resource::import([
				'script' => 'jquery.plugins/jquery.form.js,jquery.ui/jquery.ui.js,jquery.ui/i18n/' . Yii::$app->language . '.js,jquery.plugins/jquery.validate.js, dialog/dialog.js,mlselection.js,delivery.js',
            	'style' =>  'jquery.ui/themes/smoothness/jquery.ui.css,dialog/dialog.css'
			]);

			$this->params['page'] = Page::seo(['title' => Language::get('delivery_add')]);
        	return $this->render('../my_delivery.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post());
			
			$model = new \frontend\models\My_deliveryForm(['store_id' => $this->visitor['store_id']]);
			if(!$model->save($post, true)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('add_ok'), ['my_delivery/index']);
		}
	}
	
	public function actionEdit()
	{
		$template_id = intval(Yii::$app->request->get('id'));
		if(!($model = DeliveryTemplateModel::find()->where(['store_id' => $this->visitor['store_id'], 'template_id' => $template_id])->one())) {
			return Message::warning(Language::get('no_such_template'));
		}
		
		if(!Yii::$app->request->isPost)
		{
			$this->params['delivery'] = DeliveryTemplateModel::formatTemplateForEdit(ArrayHelper::toArray($model));
			$this->params['regions'] = RegionModel::find()->select('region_name')->where(['parent_id' => 0])->column();
			
			$this->params['_foot_tags'] = Resource::import([
				'script' => 'jquery.plugins/jquery.form.js,jquery.ui/jquery.ui.js,jquery.ui/i18n/' . Yii::$app->language . '.js,jquery.plugins/jquery.validate.js, dialog/dialog.js,mlselection.js,delivery.js',
            	'style' =>  'jquery.ui/themes/smoothness/jquery.ui.css,dialog/dialog.css'
			]);

			$this->params['page'] = Page::seo(['title' => Language::get('delivery_edit')]);
        	return $this->render('../my_delivery.form.html', $this->params);
		}
		else 
		{
			$post = Basewind::trimAll(Yii::$app->request->post());

			$model = new \frontend\models\My_deliveryForm(['store_id' => $this->visitor['store_id'], 'id' => $template_id]);
			if(!$model->save($post, true)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_ok'), ['my_delivery/index']);
		}
	}
	
	public function actionCopytpl()
	{
		$template_id = intval(Yii::$app->request->get('id'));
		if(!($template = DeliveryTemplateModel::find()->where(['store_id' => $this->visitor['store_id'], 'template_id' => $template_id])->asArray()->one())) {
			return Message::warning(Language::get('no_such_template'));
		}
			
		$template['name'] = $template['name'] . Language::get('copy_word');
		unset($template['template_id']);
		
		$model = new DeliveryTemplateModel();
		foreach($template as $key => $val) {
			$model->$key = $val;
		}
		if($model->save() === false) {
			return Message::warning($model->errors);
		}
		return Message::display(Language::get('copy_ok'));		
	}
	
	public function actionDelete()
	{
		$template_id = intval(Yii::$app->request->get('id'));
		
		if(!DeliveryTemplateModel::find()->where(['store_id' => $this->visitor['store_id']])->andWhere(['<>', 'template_id', $template_id])->exists()) {
			return Message::warning(Language::get('no_allow_drop_last_delivery_template'));
		}
		if(!DeliveryTemplateModel::deleteAll(['store_id' => $this->visitor['store_id'], 'template_id' => $template_id])) {
			return Message::warning(Language::get('drop_fail'));
		}
		return Message::display(Language::get('drop_ok'), ['my_delivery/index']);
	}
	
	
}