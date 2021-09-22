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

use common\models\MealModel;
use common\models\MealGoodsModel;
use common\models\GoodsModel;
use common\models\GoodsSpecModel;
use common\models\UploadedFileModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;
use common\library\Def;
use common\library\Promotool;

/**
 * @Id Seller_mealController.php 2018.11.23 $
 * @author luckey
 */

class Seller_mealController extends \common\controllers\BaseSellerController
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
			$meal_list = Promotool::getInstance('meal')->build(['store_id' => $this->visitor['store_id']])->getList(['getMealGoods' => true], $page);
			foreach($meal_list as $key => $val)
			{
				if(empty($val['mealGoods'])) {
					unset($meal_list[$key]);
					continue;
				}
				$meal_list[$key]['status_label'] = $val['status'] ? Language::get('available') : Language::get('unavailable');
				$meal_list[$key]['quantity'] = count($val['mealGoods']);
				
				$total = array('min' => 0, 'max' => 0);
				foreach($val['mealGoods'] as $key1 => $mg) 
				{
					$goods = GoodsModel::find()->select('price,default_image')->where(['goods_id' => $mg['goods_id']])->asArray()->one();
					if($goods)
					{
						if($goods['default_image']) {
							$meal_list[$key]['default_image'] = $goods['default_image'];
						} 
						$price_data =  GoodsSpecModel::find()->select('min(price) as priceMin,max(price) as priceMax')->where(['goods_id' => $mg['goods_id']])->asArray()->one();
						if($price_data) {
							$total['min'] += $price_data['priceMin'];
							$total['max'] += $price_data['priceMax'];
						}
					}
				}
				empty($meal_list[$key]['default_image']) && $meal_list[$key]['default_image'] = Yii::$app->params['default_goods_image'];
				
				$meal_list[$key]['total'] = ($total['min'] != $total['max']) ?  Def::priceFormat($total['min']) . '~' . Def::priceFormat($total['max']) : Def::priceFormat($total['min']);
			}
			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			return array('result' => array_values($meal_list), 'totalPage' => $page->getPageCount());
		}
		else
		{
			$this->params['appAvailable'] = Promotool::getInstance('meal')->build(['store_id' => $this->visitor['store_id']])->checkAvailable(true, true);
			
			$this->params['_foot_tags'] = Resource::import([
				'script' => 'jquery.plugins/jquery.form.js,weui/js/jquery-weui.min.js,jquery.plugins/jquery.infinite.js',
				'style'  => 'weui/lib/weui.min.css,weui/css/jquery-weui.min.css'
			]);

			$this->params['infiniteParams'] = json_encode(ArrayHelper::toArray($post));

			$this->params['page'] = Page::seo(['title' => Language::get('meal_list')]);
			return $this->render('../seller_meal.index.html', $this->params);
		}
	}
	
	public function actionAdd()
    {
        if(!Yii::$app->request->isPost)
		{
			$this->params['store_id'] = $this->visitor['store_id'];
			$this->params['appAvailable'] = Promotool::getInstance('meal')->build(['store_id' => $this->visitor['store_id']])->checkAvailable(true, true);

			$this->params['_foot_tags'] = Resource::import([
				'script' => 'jquery.plugins/jquery.form.js,weui/js/jquery-weui.min.js,dialog/dialog.js,jquery.ui/jquery.ui.js,jquery.ui/i18n/' . Yii::$app->language . '.js,jquery.plugins/jquery.infinite.js',
				'style' =>  'weui/lib/weui.min.css,weui/css/jquery-weui.min.css,jquery.ui/themes/smoothness/jquery.ui.css,dialog/dialog.css'
			]);
		
			$this->params['page'] = Page::seo(['title' => Language::get('meal_add')]);
        	return $this->render('../seller_meal.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			$model = new \frontend\models\Seller_mealForm(['store_id' => $this->visitor['store_id']]);
			if(!$model->save($post, true)) {
				return Message::warning($model->errors);
			}
			
			return Message::display(Language::get('add_ok'), ['seller_meal/index']);
        }
    }
	
	public function actionEdit()
    {
        $id = intval(Yii::$app->request->get('id'));
		if(!$id || !($meal = MealModel::find()->where(['store_id' => $this->visitor['store_id'], 'meal_id' => $id])->asArray()->one())) {
			return Message::warning('no_such_meal');
		}
		
        if(!Yii::$app->request->isPost)
		{
			$this->params['store_id'] = $this->visitor['store_id'];
			$this->params['appAvailable'] = Promotool::getInstance('meal')->build(['store_id' => $this->visitor['store_id']])->checkAvailable(true, true);
			$this->params['meal'] = $meal;

			$this->params['_foot_tags'] = Resource::import([
				'script' => 'jquery.plugins/jquery.form.js,weui/js/jquery-weui.min.js,dialog/dialog.js,jquery.ui/jquery.ui.js,jquery.ui/i18n/' . Yii::$app->language . '.js,jquery.plugins/jquery.infinite.js',
				'style' =>  'weui/lib/weui.min.css,weui/css/jquery-weui.min.css,jquery.ui/themes/smoothness/jquery.ui.css,dialog/dialog.css'
			]);
		
			$this->params['page'] = Page::seo(['title' => Language::get('meal_edit')]);
        	return $this->render('../seller_meal.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			
			$model = new \frontend\models\Seller_mealForm(['store_id' => $this->visitor['store_id'], 'meal_id' => $id]);
			if(!$model->save($post, true)) {
				return Message::warning($model->errors);
			}
			
			return Message::display(Language::get('edit_ok'), ['seller_meal/index']);
        }
    }
	
	public function actionDelete()
    {
        $post = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);
		
		if(!$post->id) {
			return Message::warning(Language::get('no_such_meal'));
		}
		
		if(!MealModel::deleteAll(['meal_id' => $post->id, 'store_id' => $this->visitor['store_id']])) {
			return Message::warning(Language::get('drop_fail'));
		}
		MealGoodsModel::deleteAll(['meal_id' => $post->id]);
		
		$uploadedfile = UploadedFileModel::find()->select('file_id, file_path')->where(['store_id' => $this->visitor['store_id'], 'belong' => Def::BELONG_MEAL, 'item_id' => $post->id])->asArray()->all();
		UploadedFileModel::deleteFileByQuery($uploadedfile);
		
        return Message::display(Language::get('drop_ok'));
    }
	
	public function actionQuery() 
	{
		if(($id = Yii::$app->request->get('toolId', 0))) {
			$meal = MealModel::find()->where(['store_id' => $this->visitor['store_id'], 'meal_id' => $id])->asArray()->one();
		}
		$model = new \frontend\models\Seller_mealForm(['store_id' => $this->visitor['store_id']]);
		$goodsList = $model->queryInfo(Yii::$app->request->get('id'), $meal);
		return Message::result(['goodsList' => $goodsList]);
	}
	
	public function actionDeleteimage()
	{
		$id = intval(Yii::$app->request->get('id', 0));

		$uploadedfile = UploadedFileModel::find()->select('file_id, file_path')->where(['file_id' => $id, 'store_id' => $this->visitor['store_id']])->asArray()->one();
		if(UploadedFileModel::deleteFileByQuery(array($uploadedfile))) {
			return Message::display($id);
		}
        return Message::warning(Language::get('no_image_droped'));
	}	
	
	
}