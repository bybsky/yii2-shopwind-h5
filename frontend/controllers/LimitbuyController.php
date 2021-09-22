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
use common\models\GoodsStatisticsModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Resource;
use common\library\Page;
use common\library\Timezone;
use common\library\Promotool;

/**
 * @Id LimitbuyController.php 2018.10.16 $
 * @author mosir
 */

class LimitbuyController extends \common\controllers\BaseMallController
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
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['cate_id']);
		
		if(Yii::$app->request->isAjax)
		{
			$query = LimitbuyModel::find()->alias('lb')->select('lb.id,lb.goods_id,lb.title,lb.start_time,lb.end_time,lb.store_id,lb.image,g.default_image,g.price,g.goods_name,g.default_spec,g.cate_id,s.store_name')->joinWith('goods g', false, 'INNER JOIN')->joinWith('store s', false)->where(['and', ['s.state' => 1, 'g.if_show' => 1, 'g.closed' => 0], ['<=', 'lb.start_time', Timezone::gmtime()], ['>=', 'lb.end_time', Timezone::gmtime()]]);
			
			if($post->orderby) {
				$orderBy = Basewind::trimAll(explode('|', $post->orderby));
				if(in_array($orderBy[0], array_keys($this->getOrders())) && in_array(strtolower($orderBy[1]), ['desc', 'asc'])) {
					$query->orderBy([$orderBy[0] => strtolower($orderBy[1]) == 'asc' ? SORT_ASC : SORT_DESC]);
				} else $query->orderBy(['id' => SORT_DESC]);
			} else $query->orderBy(['id' => SORT_DESC]);
			
			$page = Page::getPage($query->count(), $post->pageper);
			$goodslist = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
			
			$promotool = Promotool::getInstance()->build();
			foreach($goodslist as $key => $goods)
			{
				$result = $promotool->getItemProInfo($goods['goods_id'], $goods['default_spec']);
				$goodslist[$key]['pro_price'] = ($result !== false) ? $result['price'] : $goods['price'];
				empty($goods['default_image']) && $goodslist[$key]['default_image'] = Yii::$app->params['default_goods_image'];
				$goodslist[$key]['lefttime'] = Timezone::lefttime($goods['end_time']);
				$goodslist[$key]['sales'] = GoodsStatisticsModel::find()->select('sales')->where(['goods_id' => $goods['goods_id']])->scalar();
			} 

			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			return array('result' => array_values($goodslist), 'totalPage' => $page->getPageCount());
		}
		else
		{
			// 商品展示方式
			$display_mode = isset($_COOKIE['limitbuyDisplayMode']) ? $_COOKIE['limitbuyDisplayMode'] : '';
			if (empty($display_mode) || !in_array($display_mode, array('list', 'squares'))) {
				$display_mode = 'squares';
			}
			$this->params['display_mode'] = $display_mode;
		
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.infinite.js,search_goods.js');
			$this->params['infiniteParams'] = json_encode(ArrayHelper::toArray($post));
		
			$this->params['page'] = Page::seo(['title' => Language::get('limitbuy')]);
			return $this->render('../limitbuy.index.html', $this->params);
		}
	}
	
	private function getOrders()
    {
        return array(
            'price'    		=> Language::get('price'),
            'end_time'      => Language::get('end_time'),
        );
    }
}