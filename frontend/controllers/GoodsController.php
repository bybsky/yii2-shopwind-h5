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
use yii\captcha\CaptchaValidator;

use common\models\UserModel;
use common\models\GoodsModel;
use common\models\GoodsQaModel;
use common\models\GoodsPropModel;
use common\models\GoodsPvsModel;
use common\models\GoodsPropValueModel;
use common\models\GoodsStatisticsModel;
use common\models\OrderGoodsModel;
use common\models\GcategoryModel;
use common\models\StoreModel;
use common\models\IntegralSettingModel;
use common\models\MealGoodsModel;
use common\models\CollectModel;
use common\models\CouponModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Timezone;
use common\library\Page;
use common\library\Def;
use common\library\Promotool;
use common\library\Weixin;

/**
 * @Id GoodsController.php 2018.6.6 $
 * @author mosir
 */

class GoodsController extends \common\controllers\BaseMallController
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
		$post = Basewind::trimAll(Yii::$app->request->get(), ['id']);
		if($this->setGoodsCommonInfo($post->id) === false) {
			return Message::warning($this->errors);
		}
		
		// 更新浏览量
		GoodsStatisticsModel::updateStatistics($post->id, 'views');
		
		// 搭配套餐
		$this->params['goods']['meals'] = MealGoodsModel::getMealGoods($post->id);
		
		// 商品属性
		$this->params['goods']['props'] = $this->getGoodsProps($post->id);
		
		// 最近的商品评价
		$this->params['gcomments'] = array_merge($this->getComments($post->id, 2, true), GoodsStatisticsModel::getCommectStatistics($post->id));
		
		// 最近的问答
		$this->params['gqas'] = $this->getGoodsQas($post->id, 2, true);
		
		// 浏览历史
		$this->params['goods']['historys'] = GoodsModel::history($post->id, 0);
		
		if(Basewind::isWeixin()) {
			$this->params['signPackage'] = Weixin::getInstance()->GetSignPackage();
		}
		
		$this->params['_head_tags'] = Resource::import('goodsinfo.js');
		$this->params['_foot_tags'] = Resource::import([
			'script' => 'jquery.plugins/jquery.form.js,cart.js,jquery.plugins/raty/jquery.raty.js,jquery.ui/jquery.ui.js,dialog/dialog.js,weixin/jweixin-1.0.0.js,weixin/share.js,jquery.plugins/jquery.lazyload.js',
            'style' =>  'dialog/dialog.css,jquery.plugins/raty/jquery.raty.css'
		]);
	
		$this->params['page'] = Page::seo([
			'title' => $this->params['goods']['goods_name'], 
			'keywords' => $this->params['goods']['brand'] . ',' . implode(',', (array)$this->params['goods']['tags']) . ',' . GcategoryModel::formatCateName($this->params['goods']['cate_name'], false),
			'description' => $this->params['goods']['goods_name']
		]);
        return $this->render('../goods.index.html', $this->params);
    }
	
	/* 商品评价 */
    public function actionComment()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), ['id', 'page', 'pageper']);
		if($this->setGoodsCommonInfo($post->id) === false) {
			return Message::warning($this->errors);
		}
		
		if(Yii::$app->request->isAjax)
		{
			// 商品评价
			list($list, $page, $count) = array_values($this->getComments($post->id, $post->pageper));
			foreach($list as $key => $val) {
				$list[$key]['evaluation_time'] = Timezone::localDate('Y-m-d', $val['evaluation_time']);
			}
		
			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			return array('result' => array_values($list), 'totalPage' => $page->getPageCount());
		}
		else
		{
			$this->params['gcomments'] = GoodsStatisticsModel::getCommectStatistics($post->id);
			
			$this->params['_foot_tags'] = Resource::import([
				'script' => 'jquery.plugins/jquery.infinite.js,jquery.plugins/raty/jquery.raty.js,jquery.plugins/jquery.lazyload.js',
				'style' => 'jquery.plugins/raty/jquery.raty.css'
			]);

			$this->params['infiniteParams'] = json_encode(ArrayHelper::toArray($post));
		
			$this->params['page'] = Page::seo(['title' => $this->params['goods']['goods_name']]);
			return $this->render('../goods.comments.html', $this->params);
		}
    }
	
	/* 销售记录 */
    public function actionSalelog()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), ['id', 'page', 'pageper']);
		if($this->setGoodsCommonInfo($post->id) === false) {
			return Message::warning($this->errors);
		}
		
		if(Yii::$app->request->isAjax)
		{
			// 商品销量
			list($list, $page, $count) = array_values($this->getSaleLogs($post->id, $post->pageper));
			foreach($list as $key => $val) {
				$list[$key]['add_time'] = Timezone::localDate('Y-m-d', $val['add_time']);
			}
			
			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			return array('result' => array_values($list), 'totalPage' => $page->getPageCount());
		}
		else
		{
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.infinite.js,jquery.plugins/jquery.lazyload.js');
			$this->params['infiniteParams'] = json_encode(ArrayHelper::toArray($post));
		
			$this->params['page'] = Page::seo(['title' => $this->params['goods']['goods_name']]);
			return $this->render('../goods.salelog.html', $this->params);
		}
    }
	
	/* 商品咨询 */
	public function actionQa()
    {
		$get = Basewind::trimAll(Yii::$app->request->get(), ['id', 'page', 'pageper']);

		if(!$get->id || !($goods = GoodsModel::find()->select('goods_name,store_id')->where(['goods_id' => $get->id])->one())) {
			return Message::warning('no_such_goods');
		}

        if(!Yii::$app->request->isPost)
        {
			if(Yii::$app->request->isAjax)
			{
				// 商品咨询
				list($list, $page, $count) = array_values($this->getGoodsQas($get->id, $get->pageper));
				foreach($list  as $key => $val) {
					$list[$key]['time_post'] = Timezone::localDate('Y-m-d H:i:s', $val['time_post']);
					$list[$key]['time_reply'] = Timezone::localDate('Y-m-d H:i:s', $val['time_reply']);
				}
				
				Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
				return array('result' => array_values($list), 'totalPage' => $page->getPageCount());
			}
			else
			{
				if($this->setGoodsCommonInfo($get->id) === false) {
					return Message::warning($this->errors);
				}
			
				$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.infinite.js,jquery.plugins/jquery.lazyload.js,jquery.plugins/jquery.form.js');
				$this->params['infiniteParams'] = json_encode(ArrayHelper::toArray($get));
				
				$this->params['page'] = Page::seo(['title' => $this->params['goods']['goods_name']]);
				return $this->render('../goods.qa.html', $this->params); 
			}
        }
        else
        {
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			
			if(Yii::$app->user->isGuest) {
				return Message::warning(sprintf(Language::get('login_to_ask'), Url::toRoute('user/login')));
			}
			
			if(Yii::$app->user->id == $this->visitor['store_id']) {
				return Message::warning(Language::get('not_question_self'));
			}
			
			if(empty($post->content)) {
				return Message::warning(Language::get('content_not_null'));
			}
			if(!empty($post->email) && !Basewind::isEmail($post->email)) {
				return Message::warning(Language::get('email_not_correct'));
			}
			if(Yii::$app->params['captcha_status']['goodsqa']) {
				$captchaValidator = new CaptchaValidator(['captchaAction' => 'default/captcha']);
				if(!$captchaValidator->validate($post->captcha)) {
					return Message::warning(Language::get('captcha_failed'));
				}
			}
			
			$model = new GoodsQaModel();
			$model->question_content = $post->content;
			$model->type = 'goods';
			$model->item_id = $get->id;
			$model->item_name = addslashes($goods->goods_name);
			$model->store_id = $goods->store_id;
			$model->email = $post->email ? $post->email : '';
			$model->userid = $post->hide_name ? 0 : Yii::$app->user->id;
			$model->time_post = Timezone::gmtime();
			if(!$model->save()) {
				return Message::warning(Language::get('post_qa_fail'));
			}
			return Message::display(Language::get('post_qa_ok'), ['goods/qa', 'id' => $get->id]);
        }
    }

	/* [异步JS请求]在此获取该商品的促销策略，包括促销商品，会员价格，手机专享优惠价格等 */
	public function actionPromoinfo()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['goods_id', 'spec_id']);
	
		$promotool = Promotool::getInstance()->build();
		if(($result = $promotool->getItemProInfo($post->goods_id, $post->spec_id)) === false) {
			return Message::warning($result);
		}
		return Message::result($result);
	}
	
	/* 获取商品页，评价页，销售页等公共数据 */
	private function setGoodsCommonInfo($id = 0)
	{
		$goods = GoodsModel::find()->alias('g')->select('g.*,gst.sales,gst.comments,gi.max_exchange')->joinWith('goodsStatistics gst', false)->joinWith('goodsIntegral gi', false)->with(['goodsImage'=>function($query){$query->orderBy('sort_order');}])->with('goodsSpec')->where(['g.goods_id' => $id])->asArray()->one();
		
		if(!$id || !$goods) {
			$this->errors = Language::get('no_such_goods');
			return false;
		}
		if (($goods['if_show'] == 0) || ($goods['closed'] == 1)) {
			$this->errors = Language::get('goods_not_exist');
			return false;
    	}
	
		// 判断店铺是否开启
		$store = StoreModel::getStoreAssign($goods['store_id']);
		if (!$store || ($store['state'] == Def::STORE_CLOSED)) {
 			$this->errors = Language::get('the_store_is_closed');
			return false;
   		}
		$goods['tags'] = $goods['tags'] ? Basewind::trimAll(explode(',', str_replace('，', ',',$goods['tags']))) : array();
		
		// 读取商品促销价格
		$promotool = Promotool::getInstance()->build();
		$goods['promotion'] = $promotool->getItemProInfo($goods['goods_id'], $goods['default_spec']);
		
		// 商品积分
		if(IntegralSettingModel::getSysSetting('enabled')) {
			$goods['integral_enabled'] = true;
			$goods['exchange_price'] = IntegralSettingModel::getSysSetting('rate') * $goods['max_exchange'];
			
			// 购买商品可获得多少积分
			$integralRadio = IntegralSettingModel::getSysSetting(array('buygoods', $store['sgrade']));
			if($integralRadio > 0 && $integralRadio <= 1) {
				$this->params['buyIntegral'] = ['radio' => $integralRadio, 'price' => round($goods['price'] * $integralRadio, 2)];
			}
		}
		
		// 获取商品详情页显示所有该商品具有的营销工具信息
		$promotool = Promotool::getInstance()->build(['store_id' => $goods['store_id']]);
   		$this->params['promotool'] = $promotool->getGoodsAllPromotoolInfo($id);
		
		// 商品是否收藏过 - for WAP Only
		$goods['collected'] = CollectModel::find()->where(['userid' => Yii::$app->user->id, 'type' => 'goods', 'item_id' => $id])->exists() ? 1 : 0;
		$this->params['goods'] = $goods;
		
		// 是否显示领取优惠券按钮 - for WAP Only
		if(CouponModel::find()->where(['clickreceive' => 1, 'available' => 1, 'store_id' => $store['store_id']])->andWhere(['>', 'end_time', Timezone::gmtime()])->andWhere(['or', ['total' => 0], ['and', ['>', 'total', 0], ['>', 'surplus', 0]]])->exists()) {
			$store['couponReceive'] = true;
		}
		
		// 店铺是否被收藏过
		$store['collected'] = CollectModel::find()->where(['userid' => Yii::$app->user->id, 'type' => 'store', 'item_id' => $id])->exists() ? 1 : 0;
		
		// 页面公共参数
		$this->params = array_merge($this->params, Page::getAssign('store', $goods['store_id']));
		$this->params['store'] = array_merge($store, (array)$this->params['store']);
		$this->params['default_image'] = Yii::$app->params['default_goods_image'];
	}
	
	/* 取得商品评价 */
    private function getComments($goods_id = 0, $pageper = 10, $commented = false)
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['level']);
	
		$query = OrderGoodsModel::find()->alias('og')->select('og.rec_id,og.evaluation,og.comment,og.reply_comment,og.reply_time,o.buyer_id,o.buyer_name,o.evaluation_status,o.evaluation_time')->joinWith('order o', false)->where(['goods_id' => $goods_id, 'o.evaluation_status' => 1])->orderBy(['o.evaluation_time' => SORT_DESC]);
		if($commented) {
			$query->andWhere(['>', 'comment', '']);
		}
		
		// 数据库字段记录的是5分制，3分为中评
		if(isset($post->level)) {
			if($post->level == 1) $query->andWhere(['<', 'evaluation', 3]);
			if($post->level == 2) $query->andWhere(['=', 'evaluation', 3]);
			if($post->level == 3) $query->andWhere(['>', 'evaluation', 3]);
		}
		
		$page = Page::getPage($query->count(), $pageper);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach($list as $key => $val)
		{
			if(!($portrait = UserModel::find()->select('portrait')->where(['userid' => $val['buyer_id']])->scalar())) {
				$portrait = Yii::$app->params['default_user_portrait'];
			}
			$list[$key]['portrait'] = $portrait; 
		}
		$result = array('list' => $list, 'page' => $page, 'count' => $query->count());

		return $result;
    }

	/* 取得销售记录 */
    private function getSaleLogs($goods_id = 0, $pageper = 10)
    {
		$query = OrderGoodsModel::find()->alias('og')->select('buyer_id,buyer_name,add_time,anonymous,goods_id,specification, price, quantity, evaluation')->joinWith('order o', false)->where(['goods_id' => $goods_id, 'o.status' => Def::ORDER_FINISHED])->orderBy(['add_time' => SORT_DESC]);
		$page = Page::getPage($query->count(), $pageper);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		$result = array('list' => $list, 'page' => $page, 'count' => $query->count());
	
        return $result;
    }
	
	/* 取得商品咨询 */
    private function getGoodsQas($goods_id = 0, $pageper = 10, $replied = false)
    {
		$query = GoodsQaModel::find()->alias('ga')->select('u.username,question_content,reply_content,time_post,time_reply')->joinWith('user u', false)->where(['item_id' => $goods_id, 'type' => 'goods'])->orderBy(['time_post' => SORT_DESC]);
		if($replied) {
			$query->andWhere(['>', 'reply_content', '']);
		}
		
		$page = Page::getPage($query->count(), $pageper);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		$result = array('list' => $list, 'page' => $page, 'count' => $query->count());
	
        return $result;
    }
	
	/* 获取商品属性 */
	private function getGoodsProps($goods_id = 0, $cached = true)
	{
		$cache = Yii::$app->cache;
		$cachekey = md5((__METHOD__).var_export(func_get_args(), true));
		$data = $cache->get($cachekey);
		if($data === false || !$cached)
		{
			$result = array();
			if(($pvs = GoodsPvsModel::find()->select('pvs')->where(['goods_id' => $goods_id])->scalar())) 
			{
				$pvs = explode(';', $pvs);
				foreach($pvs as $pv)
				{
					if(!$pv) continue;
					$pv = explode(':', $pv);
					if(($prop = GoodsPropModel::find()->where(['pid' => $pv[0], 'status' => 1])->one())) {
						if(($value = GoodsPropValueModel::find()->where(['pid' => $prop->pid, 'vid' => $pv[1], 'status' => 1])->one())) {
							if(isset($result[$prop->pid]['value'])) $result[$prop->pid]['value'] .= '，' . $value['pvalue'];
							else $result[$prop->pid] = array('name' => $prop->name, 'value' => $value['pvalue']);
						}
					}
				}
			}
			$data = $result;
			$cache->set($cachekey, $data, 3600);
		}
		return $data;
	}
}
