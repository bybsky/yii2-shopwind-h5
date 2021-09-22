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

use common\models\DistributeModel;
use common\models\DistributeOrderModel;
use common\models\DistributeItemsModel;
use common\models\DistributeMerchantModel;
use common\models\AppmarketModel;
use common\models\GoodsModel;
use common\models\DepositTradeModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;
use common\library\Def;
use common\library\Timezone;
use common\library\Promotool;

/**
 * @Id DistributeController.php 2018.10.16 $
 * @author mosir
 */

class DistributeController extends \common\controllers\BaseUserController
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
		$distribute = [
			'amount'=> DistributeModel::find()->select('amount')->where(['userid' => Yii::$app->user->id])->scalar(),
			'goods' => DistributeItemsModel::find()->select('diid')->where(['userid' => Yii::$app->user->id, 'type' => 'goods'])->count(),
			//'store' => DistributeItemsModel::find()->select('diid')->where(['userid' => Yii::$app->user->id, 'type' => 'store'])->count(),
			'orders'=> count(DistributeOrderModel::find()->select('order_sn')->where(['userid' => Yii::$app->user->id])->indexBy('order_sn')->all()),
			'teams' => DistributeMerchantModel::getTeams(Yii::$app->user->id)
		];
		$this->params['distribute'] = $distribute;
		
		$this->params['page'] = Page::seo(['title' => Language::get('distribute_index')]);
        return $this->render('../distribute.index.html', $this->params);
	}
	
	public function actionGoods()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['page', 'pageper']);
		
		if(Yii::$app->request->isAjax)
		{
			$model = new \frontend\models\DistributeGoodsForm();
			list($list, $page) = $model->formData($post, $post->pageper);
			
			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			return array('result' => array_values($list), 'totalPage' => $page->getPageCount());
		}
		else
		{
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.infinite.js');
			$this->params['infiniteParams'] = json_encode(ArrayHelper::toArray($post));
			
			$this->params['page'] = Page::seo(['title' => Language::get('distribute_goods')]);
			return $this->render('../distribute.goods.html', $this->params);
		}
	}
	
	/* 收益明细 */
	public function actionTrade()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['page', 'pageper']);
		
		if(Yii::$app->request->isAjax)
		{
			$query = DepositTradeModel::find()->select('tradeNo,title,flow,amount')->where(['bizIdentity' => Def::TRADE_FX, 'flow' => 'income', 'buyer_id' => Yii::$app->user->id])->orderBy(['trade_id' => SORT_DESC]);
			if($post->tradeNo) {
				$query->andWhere(['in', 'tradeNo', explode(',', $post->tradeNo)]);
			}
			
			$page = Page::getPage($query->count(), $post->pageper);
			$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
			
			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			return array('result' => array_values($list), 'totalPage' => $page->getPageCount());
		}
		else
		{
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.infinite.js');
			$this->params['infiniteParams'] = json_encode(ArrayHelper::toArray($post));
			
			$this->params['page'] = Page::seo(['title' => Language::get('distribute_trade')]);
			return $this->render('../distribute.trade.html', $this->params);
		}
	}
	
	/* 分销订单 */
	public function actionOrder()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['page', 'pageper']);
		
		if(Yii::$app->request->isAjax)
		{
			$model = new \frontend\models\DistributeOrderForm();
			
			$post->queryitem = true;
			list($list, $page) = $model->formData($post, $post->pageper);
			
			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			return array('result' => array_values($list), 'totalPage' => $page->getPageCount());
		}
		else
		{
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.infinite.js');
			$this->params['infiniteParams'] = json_encode(ArrayHelper::toArray($post));
			
			$this->params['page'] = Page::seo(['title' => Language::get('distribute_order')]);
			return $this->render('../distribute.order.html', $this->params);
		}
	}	
	
	public function actionQrcode()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), ['id']);
		
		if($post->id && $goods = GoodsModel::find()->select('goods_name,price,default_image')->where(['goods_id' => $post->id, 'if_show' => 1, 'closed' => 0])->one()) {
			empty($goods->default_image) && $goods->default_image = Yii::$app->params['default_goods_image'];
		
			list($qrcodeUrl, $qrcodePath) = Page::generateQRCode('distgoods', ['type' => 'goods', 'id' => $post->id, 'uid' => Yii::$app->user->id]);
			if(!$qrcodeUrl) {
				return Message::warning(Language::get('getQrcode_fail'));
			}

			$config = array(
			  'text'=>array(
				array(
				  'text' => substr($goods->goods_name, 0, 36),
				  'left' => 20,
				  'top'  => 700,
				  'fontSize' => 28,      
				  'fontColor' => '0,0,0',
				),
				array(
				  'text' => $goods['price'],
				  'left' => 50,
				  'top'  => 820,
				  'fontSize' => 32,      
				  'fontColor' => '255,0,0',
				),
			  ),
			  'image'=>array(
				array(
				  'url'  	=> $qrcodePath,
				  'left'	=> 450,
				  'top'		=> 720,
				  'width'	=> 175,
				  'height'	=> 175,
				  'opacity'	=> 100
				),
				array(  
				  'url'		=> Page::urlFormat($goods->default_image),
				  'left'	=> 0,
				  'top'		=> 0,
				  'width'	=> 660,
				  'height'	=> 640,
				  'opacity'	=> 100
				),
			  ),
			  'background' => Yii::getAlias('@frontend'). '/web/static/images/goodsQrcode.png',
			);
			$qrcode = Page::createPoster($config, $qrcodePath);
		}
		else
		{
			list($qrcodeUrl, $qrcodePath) = Page::generateQRCode('distapply', ['type' => 'register', 'id' => 0, 'uid' => Yii::$app->user->id]);
			if(!$qrcodeUrl) {
				return Message::warning(Language::get('getQrcode_fail'));
			}

			$config = array(
			  'image'=>array(
				array(
				  'url'  	=> $qrcodePath,
				  'left'	=> 56,
				  'top'		=> 656,
				  'width'	=> 190,
				  'height'	=> 190,
				  'opacity'	=> 100
				),
			  ),
			  'background' => Yii::getAlias('@frontend'). '/web/static/images/applyQrcode.png',
			);
			$qrcode = Page::createPoster($config, $qrcodePath);
		}
		
		$this->params['qrcode'] = str_replace(Yii::getAlias('@frontend'). '/web', Basewind::homeUrl(), $qrcode);
		
		$this->params['page'] = Page::seo(['title' => Language::get('distribute_qrcode')]);
        return $this->render('../distribute.qrcode.html', $this->params);
	}
	
	public function actionTeam()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id', 'page', 'pageper']);
		
		if(Yii::$app->request->isAjax)
		{
			$model = new \frontend\models\DistributeTeamForm();
			list($list, $page) = $model->formData($post, $post->pageper);
			
			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			return array('result' => array_values($list), 'totalPage' => $page->getPageCount());
		}
		else
		{
			$this->params['distribute'] = ['teams' => DistributeMerchantModel::getTeams(Yii::$app->user->id)];
		
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.infinite.js');
			$this->params['infiniteParams'] = json_encode(ArrayHelper::toArray($post));
			
			$this->params['page'] = Page::seo(['title' => Language::get('distribute_team')]);
			return $this->render('../distribute.team.html', $this->params);
		}
	}
	
	public function actionRank()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['page', 'pageper']);
		
		if(Yii::$app->request->isAjax)
		{
			$model = new \frontend\models\DistributeRankForm();
			list($list, $page) = $model->formData($post, $post->pageper);
			
			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			return array('result' => array_values($list), 'totalPage' => $page->getPageCount());
		}
		else
		{
			$model = new \frontend\models\DistributeRankForm();
			$this->params['distribute'] = ['rank' => $model->myRank()];
			
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.infinite.js');
			$this->params['infiniteParams'] = json_encode(ArrayHelper::toArray($post));
			
			$this->params['page'] = Page::seo(['title' => Language::get('distribute_rank')]);
			return $this->render('../distribute.rank.html', $this->params);
		}
	}
	
	/* 加入分销 */
	public function actionChoice()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['goods_id']);
		
		$model = new \frontend\models\DistributeGoodsForm();
		if($model->choice($post) === false) {
			return Message::warning($model->errors);
		}
		
		return Message::display(Language::get('choice_ok'));
	}
	
	/* 申请成为分销商 */
	public function actionApply()
	{
		if(!AppmarketModel::find()->where(['appid' => 'distribute', 'status' => 1])->exists()) {
			return Message::warning(Language::get('apply_disabled'));
		}
		if(!Yii::$app->request->isPost)
		{
			if(DistributeMerchantModel::find()->where(['userid' => Yii::$app->user->id])->exists()) {
				return $this->redirect(['distribute/index']);
			}
		
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.form.js');
			
			$this->params['page'] = Page::seo(['title' => Language::get('distribute_apply')]);
        	return $this->render('../distribute.apply.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			$model = new \frontend\models\DistributeApplyForm();
        	if (!$model->save($post)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('apply_ok'), ['distribute/index']);
		}
	}
}