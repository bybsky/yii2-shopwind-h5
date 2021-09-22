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
use common\models\GoodsModel;
use common\models\GcategoryModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;
use common\library\Def;
use common\library\Weixin;

/**
 * @Id StoreController.php 2018.6.11 $
 * @author mosir
 */

class StoreController extends \common\controllers\BaseMallController
{
	/**
	 * 初始化
	 * @var array $view 当前视图
	 * @var array $params 传递给视图的公共参数
	 */
	public function init()
	{
		parent::init();
		$this->view  = Page::setView('store');
	}
	
    public function actionIndex()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);
		
		$model = new \frontend\models\StoreForm();
		if(($store = $model->formData($post, 4)) === false) {
			return Message::warning($model->errors);
		}

		// 页面公共参数
		$this->params = array_merge($this->params, ['store' => $store], Page::getAssign('store', $post->id));

		if(Basewind::isWeixin()){
			$this->params['signPackage'] = Weixin::getInstance()->GetSignPackage();
			$this->params['_foot_tags'] = Resource::import('weixin/jweixin-1.0.0.js,weixin/share.js');
		}
		
		$this->params['page'] = Page::seo(['title' => $store['store_name']]);
        return $this->render('../store.index.html', $this->params);
    }
	
	public function actionSearch()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id', 'cate_id', 'page', 'pageper']);
		
		if(!$post->id || !($store = StoreModel::getStoreAssign($post->id))) {
			return Message::warning(Language::get('the_store_not_exist'));
		}
		if ($store['state'] == Def::STORE_CLOSED) {
			return Message::warning(Language::get('the_store_is_closed'));
    	}
		
		if(Yii::$app->request->isAjax)
		{
			$query = GoodsModel::find()->alias('g')->select('g.goods_id,g.goods_name,g.price,g.default_image,gst.comments,gst.views,gst.sales')->joinWith('goodsStatistics gst', false)->where(['store_id' => $post->id, 'if_show' => 1, 'closed' => 0]);
			if($post->keyword) {
				$query->andWhere(['like', 'goods_name', $post->keyword]);
			}
			if($post->cate_id > 0) {
				$cateIds = GcategoryModel::getDescendantIds($post->cate_id, $post->id);
				$goodsIds = \common\models\CategoryGoodsModel::find()->select('goods_id')->where(['in', 'cate_id', $cateIds])->column();
				$query->andWhere(['in', 'g.goods_id', $goodsIds]);
			}
			
			$orders = $this->getOrders();
			if($post->orderby && isset($orders[$post->orderby])) {
				$orderBy = explode('|', $post->orderby);
				$query->orderBy([$orderBy[0] => ($orderBy[1] == 'desc' ? SORT_DESC : SORT_ASC)]);
			}
			$page = Page::getPage($query->count(), $post->pageper);
			$goodslist = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
			foreach($goodslist as $key => $goods) {
				empty($goods['default_image']) && $goodslist[$key]['default_image'] = Yii::$app->params['default_goods_image'];
			}
			
			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			return array('result' => array_values($goodslist), 'totalPage' => $page->getPageCount());
		}
		else
		{
			// 根据分类筛选
			$store['gcategories'] = GcategoryModel::getTree($post->id);
			
			// 页面公共参数
			$this->params = array_merge($this->params, ['store' => $store], Page::getAssign('store', $post->id));
			
			// 商品展示方式
			$display_mode = isset($_COOKIE['storeGoodsDisplayMode']) ? $_COOKIE['storeGoodsDisplayMode'] : '';
       	 	if (empty($display_mode) || !in_array($display_mode, array('list', 'squares'))) {
            	$display_mode = 'squares';
        	}
			$this->params['display_mode'] = $display_mode;
			
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.infinite.js,jquery.plugins/jquery.lazyload.js,search_goods.js');
			$this->params['infiniteParams'] = json_encode(ArrayHelper::toArray($post));
		
			$this->params['page'] = Page::seo(['title' => Language::get('all_goods') . ' - ' . $store['store_name']]);
       	 	return $this->render('../store.search.html', $this->params);
		}
	}
	
	public function actionCategory()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);
		
		if(!$post->id || !($store = StoreModel::find()->select('store_id,store_name,state')->where(['store_id' => $post->id])->asArray()->one())) {
			return Message::warning(Language::get('the_store_not_exist'));
		}
		if ($store['state'] == Def::STORE_CLOSED) {
			return Message::warning(Language::get('the_store_is_closed'));
    	}
		
		$this->params['store'] = $store;
		$this->params['gcategories'] = GcategoryModel::getTree($post->id);
		
		$this->params['page'] = Page::seo(['title' => Language::get('gcategory') . ' - ' . $store['store_name']]);
        return $this->render('../store.category.html', $this->params);
    }
	
	public function actionLimitbuy()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id', 'cate_id', 'page', 'pageper']);
		
		if(!$post->id || !($store = StoreModel::getStoreAssign($post->id))) {
			return Message::warning(Language::get('the_store_not_exist'));
		}
		if ($store['state'] == Def::STORE_CLOSED) {
			return Message::warning(Language::get('the_store_is_closed'));
    	}
		
		if(Yii::$app->request->isAjax)
		{
			$model = new \mobile\models\StoreLimitbuyForm(['store_id' => $post->id]);
			list($goodslist, $page) = $model->formData($post, $post->pageper);
			
			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			return array('result' => array_values($goodslist), 'totalPage' => $page->getPageCount());
		}
		else
		{
			// 页面公共参数
			$this->params = array_merge($this->params, ['store' => $store], Page::getAssign('store', $post->id));

			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.infinite.js,jquery.plugins/jquery.lazyload.js');
			$this->params['infiniteParams'] = json_encode(ArrayHelper::toArray($post));
		
			$this->params['page'] = Page::seo(['title' => Language::get('limitbuy') . ' - ' . $store['store_name']]);
       	 	return $this->render('../store.limitbuy.html', $this->params);
		}
	}
	
	private function getOrders()
	{
		$orders = array(
            'add_time|desc' => Language::get('add_time_desc'),
            'price|asc' 	=> Language::get('price_asc'),
            'price|desc' 	=> Language::get('price_desc'),
			'sales|desc' 	=> Language::get('sales_desc'),
			'views|desc' 	=> Language::get('views_desc'),
        );
		return $orders;
	}
}