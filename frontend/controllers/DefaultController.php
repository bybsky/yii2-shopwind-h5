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
use yii\web\Response;
use yii\helpers\ArrayHelper;

use common\models\GoodsModel;
use common\models\GcategoryModel;

use common\library\Basewind;
use common\library\Resource;
use common\library\Page;
use common\library\Weixin;

/**
 * @Id DefaultController.php 2018.9.13 $
 * @author mosir
 */

class DefaultController extends \common\controllers\BaseMallController
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
		$this->params['index'] = true;
		if(Basewind::isWeixin()) {
			$this->params['signPackage'] = Weixin::getInstance()->GetSignPackage();
		}
		
		$this->params['_foot_tags'] = Resource::import(['remote' => 'http://res2.wx.qq.com/open/js/jweixin-1.6.0.js', 'script' =>'weixin/share.js,jquery.plugins/jquery.infinite.js']);
		
		$this->params['page'] = Page::seo();
        return $this->render('../index.html', $this->params);
    }

	public function actionNewindex()
	{
		// 首页标识
		$this->params['index'] = true;

		$this->params['page'] = Page::seo();
        return $this->render('../newindex.html', $this->params);
	}
	
	// 首页猜你喜欢
	public function actionInfinite()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['cate_id', 'recom_id', 'page', 'pageper']);
		
		$query = GoodsModel::find()->alias('g')->select('g.goods_id, g.goods_name, g.default_image, gs.price, gs.stock,gs.spec_id,gst.sales,s.store_id,s.store_name')->joinWith('store s', false)->joinWith('goodsStatistics gst', false)->joinWith('goodsDefaultSpec gs', false)->where(['g.if_show' => 1, 'g.closed' => 0, 's.state' => 1]);
		
		// 分类最新商品
		if ($post->recom_id == -100)
		{
			if ($post->cate_id > 0){
				$query->andWhere(['in', 'g.cate_id', GcategoryModel::getDescendantIds($post->cate_id)]);
			}	
		}
		// 推荐类型商品
		else {
			$query->andWhere(['recom_id' => $post->recom_id]);
			$query->joinWith('recommendGoods rg', false);
		}	
		// 排序
		if(isset($post->orderby))
		{
			$orderBy = explode('|', $post->orderby);
			if(empty($post->orderby)){
				$query->orderBy(['g.add_time' => SORT_DESC]);
			} elseif($orderBy[0] == 'add_time') {
				$query->orderBy(['g.add_time' => ($orderBy[1] == 'desc') ? SORT_DESC : SORT_ASC]);
			} elseif(in_array($orderBy[0], array('views','collects','comments','sales'))) { 
				$query->orderBy(['gst.'.$orderBy[0] => ($orderBy[1] == 'desc') ? SORT_DESC : SORT_ASC]);
			}
		}
		$page = Page::getPage($query->count(), 20);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		
		foreach($list as $key => $val){
            empty($val['default_image']) && $list[$key]['default_image'] = Yii::$app->params['default_goods_image'];
        }
		
		Yii::$app->response->format = Response::FORMAT_JSON;
		return array('result' => $list, 'totalPage' => $page->getPageCount());
	}
}