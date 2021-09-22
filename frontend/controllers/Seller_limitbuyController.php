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

use common\models\LimitbuyModel;
use common\models\UploadedFileModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;
use common\library\Timezone;
use common\library\Promotool;

/**
 * @Id Seller_limitbuyController.php 2018.10.7 $
 * @author mosir
 */

class Seller_limitbuyController extends \common\controllers\BaseSellerController
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
			$page = array('pageSize' => $post->pageper);
			$limitbuys = Promotool::getInstance('limitbuy')->build(['store_id' => $this->visitor['store_id']])->getList(false, $page);
			
			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			return array('result' => array_values($limitbuys), 'totalPage' => $page->getPageCount());
		}
		else
		{
			$this->params['appAvailable'] = Promotool::getInstance('limitbuy')->build(['store_id' => $this->visitor['store_id']])->checkAvailable(true, true);
			
			$this->params['_foot_tags'] = Resource::import([
				'script' => 'jquery.plugins/jquery.form.js,weui/js/jquery-weui.min.js,jquery.plugins/jquery.infinite.js',
				'style'  => 'weui/lib/weui.min.css,weui/css/jquery-weui.min.css'
			]);
			$this->params['infiniteParams'] = json_encode(ArrayHelper::toArray($post));
				

			$this->params['page'] = Page::seo(['title' => Language::get('limitbuy_list')]);
			return $this->render('../seller_limitbuy.index.html', $this->params);
		}
	}
	
	public function actionAdd()
    {
        if(!Yii::$app->request->isPost)
		{
			$this->params['now'] = Timezone::gmtime();
			$this->params['appAvailable'] = Promotool::getInstance('limitbuy')->build(['store_id' => $this->visitor['store_id']])->checkAvailable(true, true);

			$this->params['_foot_tags'] = Resource::import([
				'script' => 'jquery.plugins/jquery.form.js,weui/js/jquery-weui.min.js,dialog/dialog.js,jquery.ui/jquery.ui.js,jquery.ui/i18n/' . Yii::$app->language . '.js,webuploader/webuploader.js,webuploader/webuploader.compressupload.js,jquery.plugins/jquery.infinite.js',
				'style' =>  'weui/lib/weui.min.css,weui/css/jquery-weui.min.css,jquery.ui/themes/smoothness/jquery.ui.css,dialog/dialog.css'
			]);
		
			$this->params['page'] = Page::seo(['title' => Language::get('limitbuy_add')]);
        	return $this->render('../seller_limitbuy.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			
			$model = new \frontend\models\Seller_limitbuyForm(['store_id' => $this->visitor['store_id']]);
			if(!$model->save($post, true)) {
				return Message::warning($model->errors);
			}
			
			return Message::display(Language::get('add_limitbuy_ok'), ['seller_limitbuy/index']);
        }
    }
	
	public function actionEdit()
    {
        $get = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);
		
		if(!$get->id || !($limitbuy = LimitbuyModel::find()->where(['store_id' => $this->visitor['store_id'], 'id' => $get->id])->asArray()->one())) {
			return Message::warning(Language::get('no_such_limitbuy'));
		}
		
        if(!Yii::$app->request->isPost)
		{
			$this->params['limitbuy'] = $limitbuy;
			$this->params['appAvailable'] = Promotool::getInstance('limitbuy')->build(['store_id' => $this->visitor['store_id']])->checkAvailable(true, true);
			
			$this->params['_foot_tags'] = Resource::import([
				'script' => 'jquery.ui/jquery.ui.js,jquery.ui/i18n/' . Yii::$app->language . '.js,dialog/dialog.js,jquery.plugins/jquery.form.js,weui/js/jquery-weui.min.js,webuploader/webuploader.js,webuploader/webuploader.compressupload.js,jquery.plugins/jquery.infinite.js',
				'style' =>  'weui/lib/weui.min.css,weui/css/jquery-weui.min.css,jquery.ui/themes/smoothness/jquery.ui.css,dialog/dialog.css'
			]);

			$this->params['page'] = Page::seo(['title' => Language::get('limitbuy_edit')]);
        	return $this->render('../seller_limitbuy.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			
			$model = new \frontend\models\Seller_limitbuyForm(['store_id' => $this->visitor['store_id'], 'id' => $get->id]);
			if(!$model->save($post, true)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_limitbuy_ok'), ['seller_limitbuy/index']);
        }
    }
	
	public function actionDelete()
    {
        $post = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);

		if(!$post->id) {
			return Message::warning(Language::get('no_such_limitbuy'));
		}
		
		$uploadedfile = LimitbuyModel::find()->select('image')->where(['id' => $post->id, 'store_id' => $this->visitor['store_id']])->andWhere(['!=', 'image', ''])->column();
		
		if(!LimitbuyModel::deleteAll(['id' => $post->id, 'store_id' => $this->visitor['store_id']])) {
			return Message::warning(Language::get('drop_fail'));
		}
		UploadedFileModel::deleteFileByName($uploadedfile);
		
        return Message::display(Language::get('drop_ok'));
    }
	
	public function actionDeleteimage()
    {
        $post = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);

		if(!$post->id) {
			return Message::warning(Language::get('no_such_limitbuy'));
		}
		
		$uploadedfile = LimitbuyModel::find()->select('image')->where(['id' => $post->id, 'store_id' => $this->visitor['store_id']])->andWhere(['!=', 'image', ''])->column();
		
		if(!LimitbuyModel::updateAll(['image' => ''], ['id' => $post->id, 'store_id' => $this->visitor['store_id']])) {
			return Message::warning(Language::get('drop_fail'));
		}
		UploadedFileModel::deleteFileByName($uploadedfile);
		
        return Message::display(Language::get('drop_ok'));
    }
	
	public function actionQuery() 
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id', 'toolId']);
		
		if($post->toolId && ($limitbuy = LimitbuyModel::find()->where(['store_id' => $this->visitor['store_id'], 'id' => $post->toolId])->asArray()->one())) {
			$limitbuy['rules'] = unserialize($limitbuy['rules']);
		}
		
		$model = new \frontend\models\Seller_limitbuyForm(['store_id' => $this->visitor['store_id']]);
		return Message::result($model->queryInfo($post->id, $limitbuy));
	}
}