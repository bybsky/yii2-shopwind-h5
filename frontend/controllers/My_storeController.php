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

use common\models\StoreModel;
use common\models\UploadedFileModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;

/**
 * @Id My_storeController.php 2018.5.17 $
 * @author mosir
 */

class My_storeController extends \common\controllers\BaseSellerController
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
		if(!Yii::$app->request->isPost)
		{
			$store = StoreModel::find()->where(['store_id' => $this->visitor['store_id']])->asArray()->one();
			$this->params['store'] = $store;

			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.form.js,webuploader/webuploader.js,webuploader/webuploader.compressupload.js');
			
			$this->params['page'] = Page::seo(['title' => Language::get('my_store')]);
			return $this->render('../my_store.index.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['region_id']);
			
			$model = new \frontend\models\My_storeForm(['store_id' => $this->visitor['store_id']]);
			if(!$model->save($post, true)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_ok'));
		}		
    }
	
	/* for WAP Only */
	public function actionBanner()
	{
		if(!Yii::$app->request->isPost)
		{
			$store = StoreModel::find()->select('store_banner')->where(['store_id' => $this->visitor['store_id']])->asArray()->one();
			$this->params['store'] = $store;
			
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.form.js,webuploader/webuploader.js,webuploader/webuploader.compressupload.js');
			
			$this->params['page'] = Page::seo(['title' => Language::get('store_banner')]);
			return $this->render('../my_store.banner.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			
			if(!StoreModel::updateAll(['store_banner' => $post->store_banner], ['store_id' => $this->visitor['store_id']])) {
				return Message::warning(Language::get('upload_fail'));
			}
			return Message::display(Language::get('upload_ok'));
		}
	}
	
	public function actionMap()
	{
		if(!Yii::$app->request->isPost)
		{
			$store = StoreModel::find()->select('longitude,latitude,zoom')->where(['store_id' => $this->visitor['store_id']])->asArray()->one();
			$this->params['store'] = $store;
			
			$this->params['_foot_tags'] = Resource::import(['script' => 'jquery.plugins/jquery.form.js', 'remote' => '//api.map.baidu.com/api?v=2.0&ak='.Yii::$app->params['baidukey']['browser']]);
			$this->params['page'] = Page::seo(['title' => Language::get('store_map')]);
			return $this->render('../my_store.map.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['zoom']);
			
			if(!StoreModel::updateAll(['latitude' => $post->latitude, 'longitude' => $post->longitude, 'zoom' => $post->zoom], ['store_id' => $this->visitor['store_id']])) {
				return Message::warning(Language::get('handle_fail'));
			}
			return Message::display(Language::get('handle_ok'), false);
		}
	}
	
	public function actionSlides()
	{
		$get = Basewind::trimAll(Yii::$app->request->get(), true);
		
		if(!($store = StoreModel::find()->select('store_slides')->where(['store_id' => $this->visitor['store_id']])->asArray()->one())) {
			return Message::warning(Language::get('no_such_store'));
		}
		$store['store_slides'] = json_decode($store['store_slides'], true);
		
		if(!Yii::$app->request->isPost)
		{
			$this->params['store'] = $store;
			
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.form.js,webuploader/webuploader.js,webuploader/webuploader.compressupload.js');
			
			$this->params['page'] = Page::seo(['title' => Language::get('store_slides')]);
			return $this->render('../my_store.slides.html', $this->params);
		}
		else 
		{
			$post = Yii::$app->request->post();
			$langpre = in_array($get->from, ['upload', 'modify']) ? $get->from : 'save';
			
			$all = array();
			if($post['store_slides_url'])  {
				foreach($post['store_slides_url'] as $key => $val) {
					if(!empty($val)) {
						$all[$key] = ['url' => $val, 'link' => $post['store_slides_link'][$key]];
					}
				}
			}
			// 物理删除原图
			foreach($all as $key => $val) {
				if($val['url'] != $store['store_slides'][$key]['url']) {
					UploadedFileModel::deleteFileByName($store['store_slides'][$key]['url']);
				}
			}
			StoreModel::updateAll(['store_slides' => json_encode($all)], ['store_id' => $this->visitor['store_id']]);
		
			return Message::display(Language::get($langpre.'_ok'), false);
		}
	}
	
	public function actionDeleteimage()
	{
		$id = intval(Yii::$app->request->get('id', 0));

		$uploadedfile = UploadedFileModel::find()->alias('f')->select('f.file_id, f.file_path')->where(['f.file_id' => $id, 'store_id' => $this->visitor['store_id']])->asArray()->one();
		if(UploadedFileModel::deleteFileByQuery(array($uploadedfile))) {
			return Message::display($id);
		}
        return Message::warning(Language::get('no_image_droped'));
	}
	
	/* 异步删除附件 */
    public function actionDeleteslides()
    {
        $id = intval(Yii::$app->request->get('id', 0));
     
		if(!($store = StoreModel::find()->select('store_slides')->where(['store_id' => $this->visitor['store_id']])->asArray()->one())) {
			return Message::warning(Language::get('drop_fail'));
		}
		
		$store['store_slides'] = json_decode($store['store_slides'], true);
		foreach($store['store_slides'] as $key => $val) {
			if($key == $id) {
				UploadedFileModel::deleteFileByName($val['url']);
				unset($store['store_slides'][$key]);
			}
		}
		StoreModel::updateAll(['store_slides' => json_encode($store['store_slides'])], ['store_id' => $this->visitor['store_id']]);
		return Message::display(Language::get('drop_ok'), false);
    }
}