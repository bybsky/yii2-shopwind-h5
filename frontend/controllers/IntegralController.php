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

use common\models\GoodsModel;
use common\models\IntegralSettingModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;

/**
 * @Id IntegralController.php 2018.10.16 $
 * @author mosir
 */

class IntegralController extends \common\controllers\BaseMallController
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
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		
		if(!IntegralSettingModel::getSysSetting('enabled')) {
			return Message::warning(Language::get('integral_disabled'));
		}
		
		if(Yii::$app->request->isAjax)
		{
			$query = GoodsModel::find()->alias('g')->select('g.goods_id,g.default_image,g.goods_name,g.price,g.add_time,gi.max_exchange,s.store_id,s.store_name,gst.sales')->joinWith('goodsIntegral gi', false)->joinWith('store s', false)->joinWith('goodsStatistics gst', false)->where(['and', ['s.state' => 1, 'g.if_show' => 1, 'g.closed' => 0], ['>', 'gi.max_exchange', 0]]);

			if($post->orderby) {
				$orderBy = Basewind::trimAll(explode('|', $post->orderby));
				if(in_array($orderBy[0], array_keys($this->getOrders())) && in_array(strtolower($orderBy[1]), ['desc', 'asc'])) {
					$query->orderBy([$orderBy[0] => strtolower($orderBy[1]) == 'asc' ? SORT_ASC : SORT_DESC]);
				} else $query->orderBy(['g.add_time' => SORT_DESC]);
			} else $query->orderBy(['g.add_time' => SORT_DESC]);
			
			$page = Page::getPage($query->count(), $post->pageper);
			$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
			
			$rate = IntegralSettingModel::getSysSetting('rate');
			foreach($list as $key => $value)
			{
				$list[$key]['exchange_rate'] = floatval($rate);
				$list[$key]['exchange_integral'] = floatval($value['max_exchange']);
				$list[$key]['exchange_money'] = round($value['max_exchange'] * $rate, 2);
				$exchange_price = $value['price'] - $list[$key]['exchange_money'];
				if($exchange_price < 0) {
					$list[$key]['exchange_integral'] = round($value['price'] / $rate, 2);
					$list[$key]['exchange_money'] = floatval($value['price']);
					$exchange_price = 0;
				}
				$list[$key]['exchange_price'] = $exchange_price;
				empty($value['default_image']) && $list[$key]['default_image'] = Yii::$app->params['default_goods_image'];
			}  
			 
			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			return array('result' => array_values($list), 'totalPage' => $page->getPageCount());
		}
		else
		{
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.infinite.js,search_goods.js');
			$this->params['infiniteParams'] = json_encode(ArrayHelper::toArray($post));
			
			$this->params['page'] = Page::seo(['title' => Language::get('integral_list')]);
			return $this->render('../integral.index.html', $this->params);
		}
	}
	
	/**
	 * 支持的排序规则
	 */
	private function getOrders()
    {
        return array(
            'add_time'    => Language::get('add_time'),
            'sales'       => Language::get('sales'),
			'price'       => Language::get('price'),
        );
    }
}