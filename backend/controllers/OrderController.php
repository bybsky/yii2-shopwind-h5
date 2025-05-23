<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace backend\controllers;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

use common\models\OrderModel;
use common\models\DepositTradeModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Timezone;
use common\library\Page;
use common\library\Def;

/**
 * @Id OrderController.php 2018.8.2 $
 * @author mosir
 */

class OrderController extends \common\controllers\BaseAdminController
{
	/**
	 * 初始化
	 */
	public function init()
	{
		parent::init();
	}
	
	public function actionIndex()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['rp', 'page', 'status']);
		
		if(!Yii::$app->request->isAjax) 
		{
			$this->params['filtered'] = $this->getConditions($post);
			$this->params['search_options'] = $this->getSearchOption();
			$this->params['order_status_list'] = $this->getStatus();
			
			$this->params['_foot_tags'] = Resource::import([
				'script' => 'jquery.plugins/flexigrid.js,jquery.ui/jquery.ui.js,jquery.ui/i18n/' . Yii::$app->language . '.js',
            	'style'=> 'jquery.ui/themes/smoothness/jquery.ui.css'
			]);
			$this->params['page'] = Page::seo(['title' => Language::get('order_list')]);
			return $this->render('../order.index.html', $this->params);
		}
		else
		{
			$query = OrderModel::find()->select('order_id,order_sn,buyer_name,seller_name,order_amount,payment_name,status,add_time')->indexBy('order_id');
			$query = $this->getConditions($post, $query);
			
			$orderFields = ['order_sn','seller_name','add_time','buyer_name','order_amount','payment_name','status'];
			if(in_array($post->sortname, $orderFields) && in_array(strtolower($post->sortorder), ['asc', 'desc'])) {
				$query->orderBy([$post->sortname => strtolower($post->sortorder) == 'asc' ? SORT_ASC : SORT_DESC]);
			} else $query->orderBy(['order_id' => SORT_DESC]);
			
			$page = Page::getPage($query->count(), $post->rp ? $post->rp : 10);
			
			$result = ['page' => $post->page, 'total' => $query->count()];
			foreach ($query->offset($page->offset)->limit($page->limit)->asArray()->each() as $key => $val)
			{
				$list = array();
				$operation = "<a class='btn green' href='".Url::toRoute(['order/view', 'id' => $key])."'><i class='fa fa-search-plus'></i>查看</a>";
				$list['operation'] 		= $operation;
				$list['order_sn'] 		= $val['order_sn'];
				$list['seller_name'] 	= $val['seller_name'];
				$list['add_time'] 		= Timezone::localDate('Y-m-d H:i:s', $val['add_time']);
				$list['buyer_name'] 	= $val['buyer_name'];
				$list['order_amount']	= $val['order_amount'];
				$list['payment_name'] 	= $val['payment_name'];
				$list['status'] 		= Def::getOrderStatus($val['status']);
				$result['list'][$key]	= $list;
			}
			return Page::flexigridXML($result);
		}
	}
	
	public function actionView()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);
		if(!$post->id) {
			return Message::warning(Language::get('no_such_order'));
		}
		if(!($order = OrderModel::find()->alias('o')->select('o.*,oe.consignee,oe.region_name,oe.zipcode,oe.phone_tel,oe.phone_mob,oe.address,oe.shipping_name')->where(['o.order_id' => $post->id])->joinWith('orderExtm oe', false)->with('orderGoods')->asArray()->one())) {
			return Message::warning(Language::get('no_such_order'));
		}
		$this->params['order'] = $order;
		return $this->render('../order.view.html', $this->params);
	}
	
	/* 订单走势（图表）本月和上月的数据统计 */
	public function actionTrend()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		
		list($curMonthAmount, $curMonthQuantity, $curDays, $beginMonth, $endMonth) = $this->getMonthTrend(Timezone::gmtime());
		list($preMonthAmount, $preMonthQuantity, $preDays) = $this->getMonthTrend($beginMonth - 1);
		
		$series = array($curMonthAmount, $preMonthAmount);
		$legend = array('本月销售额','上月销售额');
		if($post->type != 'amount')  {
			$series = array($curMonthQuantity, $preMonthQuantity);
			$legend = array('本月订单量','上月订单量');
		}
		
		$days = $curDays > $preDays ? $curDays : $preDays;
		
		// 获取日期列表
		$xaxis = array();
		for($day = 1; $day <= $days; $day++) {
			$xaxis[] = $day.'日';
		}

		$this->params['echart'] = array(
			'id'		=>  mt_rand(),
			'theme' 	=> 'macarons',
			'width'		=> 890,
			'height'    => 288,
			'option'  	=> json_encode([
				'grid' => ['left' => '10', 'right' => '0', 'top' => '50', 'bottom' => '30', 'containLabel' => true],
				'tooltip' 	=> ['trigger' => 'axis'],
				'legend'	=> [
					'data' => $legend
				],
				'calculable' => true,
   				'xAxis' => [
        			[
						'type' => 'category', 
						'data' => $xaxis
        			]
    			],
				'yAxis' => [
        			[
            			'type' => 'value'
        			]
   				 ],
				 'series' => [
					[
						'name' => $legend[0],
						'type' => 'bar',
						'data' => $series[0],
					],
					[
						'name' => $legend[1],
						'type' => 'bar',
						'data' => $series[1],
					]
				]
			])
		);
		
		$this->params['page'] = Page::seo(['title' => Language::get('order_trend')]);
		return $this->render('../echarts.html', $this->params);
	}
	
	/* 月数据统计 */
	private function getMonthTrend($month = 0)
	{
		// 本月
		if(!$month) $month = Timezone::gmtime();
		
		// 获取当月的开始时间戳和结束那天的时间戳
		list($beginMonth, $endMonth) = Timezone::getMonthDay(Timezone::localDate('Y-m', $month));
		
		$list = DepositTradeModel::find()->select('amount,end_time')->where(['bizIdentity' => Def::TRADE_ORDER, 'status' => 'SUCCESS'])->andWhere(['>=', 'end_time', $beginMonth])->andWhere(['<=', 'end_time', $endMonth])->asArray()->all();
		
		// 该月有多少天
		$days = round(($endMonth-$beginMonth) / (24 * 3600));
		
		// 按天算归类
		$amount = $quantity = array();
		foreach($list as $key => $val)
		{
			$day = Timezone::localDate('d', $val['end_time']);
	
			if(isset($amount[$day-1])) {
				$amount[$day-1] += $val['amount'];
				$quantity[$day-1]++;
			}
			else {
				$amount[$day-1] = $val['amount'];
				$quantity[$day-1] = 1;
			}
		}
		
		// 给天数补全
		for($day = 1; $day <= $days; $day++)
		{
			if(!isset($amount[$day-1])) {
				$amount[$day-1] = 0;
				$quantity[$day-1] = 0;
			}
		}
		// 按日期顺序排序
		ksort($amount);
		ksort($quantity);

		return array($amount, $quantity, $days, $beginMonth, $endMonth);
	}
	
	public function actionExport()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		if($post->id) $post->id = explode(',', $post->id);
		
		$query = OrderModel::find()->alias('o')->select('o.order_id,o.order_sn,o.buyer_name,o.seller_name,o.order_amount,o.status,o.add_time,o.payment_name,o.express_no,o.postscript,o.pay_message,oe.consignee,oe.region_name,oe.address,oe.phone_mob')->joinWith('orderExtm oe', false)->indexBy('order_id')->orderBy(['o.order_id' => SORT_DESC]);
		if(!empty($post->id)) {
			$query->andWhere(['in', 'o.order_id', $post->id]);
		}
		else {
			$query = $this->getConditions($post, $query);
		}
		if($query->count() == 0) {
			return Message::warning(Language::get('no_such_order'));
		}
		return \backend\models\OrderExportForm::download($query->asArray()->all());		
	}
	
	private function getSearchOption()
	{
		return array(
            'seller_name'	=> Language::get('store_name'),
            'buyer_name' 	=> Language::get('buyer_name'),
            'payment_name' 	=> Language::get('payment_name'),
            'order_sn' 		=> Language::get('order_sn'),
		);
	}
	
	private function getStatus($status = null)
	{
		$result = array(
            Def::ORDER_PENDING		=> Language::get('order_pending'),
            Def::ORDER_SUBMITTED	=> Language::get('order_submitted'),
            Def::ORDER_ACCEPTED		=> Language::get('order_accepted'),
            Def::ORDER_SHIPPED		=> Language::get('order_shipped'),
            Def::ORDER_FINISHED		=> Language::get('order_finished'),
            Def::ORDER_CANCELED		=> Language::get('order_canceled'),
        );
		if($status !== null) {
			return isset($result[$status]) ? $result[$status] : '';
		}
		return $result;
	}
	
	private function getConditions($post, $query = null)
	{
		if($query === null) {
			foreach(array_keys(ArrayHelper::toArray($post)) as $field) {
				if(in_array($field, ['search_name', 'status', 'add_time_from', 'add_time_to', 'order_amount_from', 'order_amount_to'])) {
					return true;
				}
			}
			return false;
		}
		
		if($post->field && $post->search_name && in_array($post->field, array_keys($this->getSearchOption()))) {
			$query->andWhere([$post->field => $post->search_name]);
		}
		if($post->status) {
			$query->andWhere(['status' => $post->status]);
		}
		if($post->add_time_from) $post->add_time_from = Timezone::gmstr2time($post->add_time_from);
		if($post->add_time_to) $post->add_time_to = Timezone::gmstr2time_end($post->add_time_to);
		if($post->add_time_from && $post->add_time_to) {
			$query->andWhere(['and', ['>=', 'add_time', $post->add_time_from], ['<=', 'add_time', $post->add_time_to]]);
		}
		if($post->add_time_from && (!$post->add_time_to || ($post->add_time_to <= $post->add_time_from))) {
			$query->andWhere(['>=', 'add_time', $post->add_time_from]);
		}
		if(!$post->add_time_from && ($post->add_time_to && ($post->add_time_to > Timezone::gmtime()))) {
			$query->andWhere(['<=', 'add_time', $post->add_time_to]);
		}
		
		if($post->order_amount_from) $post->order_amount_from = floatval($post->order_amount_from);
		if($post->order_amount_to) $post->order_amount_to = floatval($post->order_amount_to);
		if($post->order_amount_from && $post->order_amount_to) {
			$query->andWhere(['and', ['>=', 'order_amount', $post->order_amount_from], ['<=', 'order_amount', $post->order_amount_to]]);
		}
		if($post->order_amount_from && (!$post->order_amount_to || ($post->order_amount_to < 0))) {
			$query->andWhere(['>=', 'order_amount', $post->order_amount_from]);
		}
		if(!$post->order_amount_from && ($post->order_amount_to > 0)) {
			$query->andWhere(['<=', 'order_amount', $post->order_amount_to]);
		}
		return $query;
	}
}
