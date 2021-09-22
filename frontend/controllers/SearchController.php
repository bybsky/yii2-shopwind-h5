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
use yii\helpers\Url;

use common\models\GoodsModel;
use common\models\StoreModel;
use common\models\SgradeModel;
use common\models\GcategoryModel;
use common\models\ScategoryModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Resource;
use common\library\Page;
use common\library\Def;

/**
 * @Id SearchController.php 2018.10.15 $
 * @author mosir
 */

class SearchController extends \common\controllers\BaseMallController
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
		$this->params = ArrayHelper::merge($this->params, Page::getAssign('mall'));
	}

    public function actionIndex()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['cate_id', 'page', 'pageper']);
		
		if(Yii::$app->request->isAjax)
		{
			$query = GoodsModel::find()->alias('g')->select('g.goods_id,g.goods_name,g.default_image,g.price, s.store_id,s.store_name,gst.sales,gst.comments')->where(['g.if_show' => 1, 'g.closed' => 0, 's.state' => 1])->with('goodsImage')->joinWith('goodsStatistics gst', false)->orderBy(['g.goods_id' => SORT_DESC]);
			
			$model = new \frontend\models\SearchForm();
			$query = $model->getConditions($post, $query);
			
			$page = Page::getPage($query->count(), $post->pageper);
			$goodsList = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
			foreach ($goodsList as $key => $goods) {
				empty($goods['default_image']) && $goodsList[$key]['default_image'] = Yii::$app->params['default_goods_image'];
			}
			
			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			return array('result' => array_values($goodsList), 'totalPage' => $page->getPageCount());
		}
		else
		{
			// 商品展示方式
			$display_mode = isset($_COOKIE['goodsDisplayMode']) ? $_COOKIE['goodsDisplayMode'] : '';
			if (empty($display_mode) || !in_array($display_mode, array('list', 'squares'))) {
				$display_mode = 'squares';
			}
			$this->params['display_mode'] = $display_mode;
			
			// 按分类/品牌/价格/属性/地区统计
			$model = new \frontend\models\SearchForm();
		    $this->params = array_merge($this->params, $model->getSelectors($post));
			
			// 取得选中条件
			$this->params['filters'] = $model->getFilters($post);
			
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.infinite.js,jquery.plugins/jquery.lazyload.js,search_goods.js,cart.js');
			$this->params['infiniteParams'] = json_encode(ArrayHelper::toArray($post));
			
			$this->params['page'] = Page::seo($this->getPageTitle($post));
			return $this->render('../search.goods.html', $this->params);
		}
    }
	
	public function actionStore()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['cate_id', 'recommended', 'sgrade', 'credit_value', 'page', 'pageper']);
		
		if(Yii::$app->request->isAjax)
		{
			$query = StoreModel::find()->alias('s')->select('s.store_id,s.store_name,s.address,s.sgrade,s.credit_value,s.praise_rate,s.state,s.add_time,s.recommended,s.store_logo')->joinWith('categoryStore cs', false)->where(['state' => Def::STORE_OPEN]);
			
			$model = new \frontend\models\SearchForm();
			$query = $model->getStoreConditions($post, $query);

			// 开启百度地图后，通过坐标检索店铺，按距离远近倒序显示数据
			// 如果效率差，请关闭该功能
			if(!isset($post->orderby) && ($fields = $this->getNearbyConditions()) !== false) {
				$query->addSelect($fields);
				$query->orderBy(['distance' => SORT_ASC]);
			}

			$page = Page::getPage($query->count(), $post->pageper);
			$storelist = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
			foreach($storelist as $key => $store)
			{
				$storelist[$key]['goodslist'] = GoodsModel::find()->select('goods_id,goods_name,default_image,price')->where(['store_id' => $store['store_id'], 'if_show' => 1, 'closed' => 0])->orderBy(['add_time' => SORT_DESC])->limit(10)->asArray()->all();
				$storelist[$key]['credit_image'] = Resource::getThemeAssetsUrl('images/credit/' . StoreModel::computeCredit($store['credit_value']));
				empty($store['store_logo']) && $storelist[$key]['store_logo'] = Yii::$app->params['default_store_logo'];
			}
			
			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			return array('result' => array_values($storelist), 'totalPage' => $page->getPageCount());
		}
		else
		{
			// 店铺分类
			$this->params['scategories'] = ScategoryModel::getTree(true, 1);
			
			// 店铺等级
			$this->params['sgrades'] = SgradeModel::find()->select('grade_name')->indexBy('grade_id')->orderBy(['grade_id' => SORT_ASC])->column();
			
			// 地区
			$this->params['regions'] = StoreModel::find()->select('region_name')->where(['>', 'region_id', 0])->indexBy('region_id')->column();
			
			// 百度地图AK，获取附近店铺用到
			$this->params['baidukey'] = Yii::$app->params['baidukey'];

			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.infinite.js,jquery.plugins/jquery.lazyload.js,search_store.js');
			$this->params['infiniteParams'] = json_encode(ArrayHelper::toArray($post));
		
			$this->params['page'] = Page::seo($this->getPageTitle($post, 'store'));
			return $this->render('../search.store.html', $this->params);
		}
	}
	
	/* 公共搜索框 */
	public function actionForm()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['store_id']);
		if(!in_array($post->searchType, ['index', 'goods', 'store'])) $post->searchType = 'index';

		// 搜索商城
		if(!$post->store_id) 
		{
			$searchUrl = Url::toRoute(['search/'.$post->searchType]);
			$params = array('r' => 'search/'.$post->searchType);
		
			// 商品
			if(!in_array($post->searchType, array('store'))) {
				$keywords = array('小米', '苹果', '联想', 'iPad', 'MAC');
			}
			// 店铺
			else {
				$keywords = array('海澜之家', '劲霸男装', 'GUCCI', '梦芭莎', '珀莱雅');
			}
		}
		// 搜索店铺商品
		else
		{
			$searchUrl = Url::toRoute(['store/search', 'id' => $post->store_id]);
			$params = array('r' => 'store/search', 'id' => $post->store_id);
			$keywords = array('小米', '苹果', '联想', '华为', '大疆无人机', 'iPhone X');
		}
		$this->params['formSearchBoxParams'] = $params;
		
		if($keywords)
		{
			$formKeyword = array();
			foreach($keywords as $v) {
				$formKeyword[] = array('text' => $v, 'url' => $searchUrl . (stripos($searchUrl, '?') === false ? '?' : '&') . 'keyword='.$v);
			}
			$this->params['formSearchBoxKeyword'] = $formKeyword;
		}
		
		$this->params['page'] = Page::seo(['title' => Language::get('searched_'.$post->searchType)]);
		return $this->render('../search.form.html', $this->params);
	}
	
	private function getPageTitle($post = null, $sType = 'goods')
	{
		$title = null;
		if($post->keyword) {
			$title = $post->keyword;
		}
		elseif($post->brand) {
			$title = $post->brand;
		}
		elseif($post->cate_id) {
			if($sType == 'goods') {
				$title = GcategoryModel::find()->select('cate_name')->where(['cate_id' => $post->cate_id])->scalar();
			} else $title = ScategoryModel::find()->select('cate_name')->where(['cate_id' => $post->cate_id])->scalar();
		}
		else {
			$title = ($sType == 'store') ?  Language::get('searched_store') : Language::get('searched_goods');
		}
		return ($title === null) ? array() : ['title' => $title];
	}

	/**
	 * 根据坐标计算店铺距离，然后根据距离倒序显示数据
	 * @param int $distance  要检索的距离半径（公里）
	 */
	private function getNearbyConditions($distance = 10)
	{
		// JS设置COOKIE后，Yii获取
		Yii::$app->request->enableCookieValidation = false;
		$latitude = Yii::$app->request->cookies->get('latitude') ? Yii::$app->request->cookies->get('latitude') : $_COOKIE['latitude'];
		$longitude = Yii::$app->request->cookies->get('longitude') ? Yii::$app->request->cookies->get('longitude')  : $_COOKIE['longitude'];

		if(!$latitude || $latitude < 0 || !$longitude || $longitude < 0) {
			return false;
		}

		// 距离半径判断
		$fields = "sqrt( ( ((".$longitude."-longitude)*PI()*12656*cos(((".$latitude."+latitude)/2)*PI()/180)/180) * ((".$longitude."-longitude)*PI()*12656*cos (((".$latitude."+latitude)/2)*PI()/180)/180) ) + ( ((".$latitude."-latitude)*PI()*12656/180) * ((".$latitude."-latitude)*PI()*12656/180) ) )/2 as distance";

		// 完整查询语句
		/*$sql = "select *, sqrt( ( ((".$longitude."-longitude)*PI()*12656*cos(((".$latitude."+latitude)/2)*PI()/180)/180) * ((".$longitude."-longitude)*PI()*12656*cos (((".$latitude."+latitude)/2)*PI()/180)/180) ) + ( ((".$latitude."-latitude)*PI()*12656/180) * ((".$latitude."-latitude)*PI()*12656/180) ) )/2 as distance 
			from ".StoreModel::tableName()." 
			order by distance ".SORT_ASC." having distance <".$distance; */
		
		return $fields;
	}
}