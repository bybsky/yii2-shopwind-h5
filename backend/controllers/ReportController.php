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

use common\models\ReportModel;
use common\models\UserModel;
use common\models\GoodsModel;
use common\models\StoreModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Timezone;
use common\library\Page;

/**
 * @Id ReportController.php 2018.9.12 $
 * @author mosir
 */

class ReportController extends \common\controllers\BaseAdminController
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
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['rp', 'page']);
		
		if(!Yii::$app->request->isAjax) 
		{
			$this->params['filtered'] = $this->getConditions($post);
			$this->params['search_options'] = $this->getSearchOption();
			
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/flexigrid.js,inline_edit.js');
			
			$this->params['page'] = Page::seo(['title' => Language::get('report_list')]);
			return $this->render('../report.index.html', $this->params);
		}
		else
		{
			$query = ReportModel::find()->alias('r')->select('r.*,u.username,s.store_id,s.store_name,g.goods_id,g.goods_name,g.default_image')->joinWith('user u', false)->joinWith('store s', false)->joinWith('goods g', false)->indexBy('id');
			$query = $this->getConditions($post, $query);
			
			$orderFields = ['add_time', 'status'];
			if(in_array($post->sortname, $orderFields) && in_array(strtolower($post->sortorder), ['asc', 'desc'])) {
				$query->orderBy([$post->sortname => strtolower($post->sortorder) == 'asc' ? SORT_ASC : SORT_DESC]);
			} else $query->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_DESC]);
			
			$page = Page::getPage($query->count(), $post->rp ? $post->rp : 10);
			
			$result = ['page' => $post->page, 'total' => $query->count()];
			foreach ($query->offset($page->offset)->limit($page->limit)->asArray()->each() as $key => $val)
			{
				$list = array();
				$operation = "<a class='btn red' onclick=\"fg_delete({$key},'report')\"><i class='fa fa-trash-o'></i>删除</a>";
				$operation .= "<span class='btn'><em><i class='fa fa-cog'></i>设置 <i class='arrow'></i></em><ul>";
				if($val['status'] == 0){
					$operation .= "<li><a href='javascript:;' onclick='javascrip:fg_verify({$key});'>审核</a></li>";
				}
				$operation .= "<li><a href='".Url::toRoute(['report/sendmsg', 'id' => $val['userid']])."'>通知举报人</a></li>";
				$operation .= "<li><a href='".Url::toRoute(['report/sendmsg', 'id' => $val['store_id']])."'>通知被举报的店铺</a></li>";
				$operation .= "<li><a href='".Url::toRoute(['goods/index', 'goods_name' => $val['goods_name']])."'>管理举报商品</a></li>";
				$operation .= "</ul>";
				$list['operation'] = $operation;
				
				$list['add_time'] = Timezone::localDate('Y-m-d H:i:s', $val['add_time']);
				$list['username'] = $val['username'];
				$list['goods_name'] = '<a target="_blank" href="'.Url::toRoute(['goods/index', 'id' => $val['goods_id']], $this->params['homeUrl']).'">'.$val['goods_name'].'</a>';
				$list['store_name'] = '<a target="_blank" href="'.Url::toRoute(['store/index', 'id' => $val['store_id']], $this->params['homeUrl']).'">'.$val['store_name'].'</a>';
				$list['content'] = $val['content'];
				$list['status'] = $val['status'] ? '<span class="yes"><i class="fa fa-check-circle"></i>已审核</span>' : '<span class="no"><i class="fa fa-ban"></i>未审核</span>';
				$result['list'][$key] = $list;
			}
			return Page::flexigridXML($result);
		}
	}
	
	public function actionVerify()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);

		if(!$post->id || !($model = ReportModel::find()->select('status,id')->where(['and', ['id' => $post->id], ['status' => 0]])->one())){
			return Message::warning(Language::get('no_such_item'));
		}

		$model->status = 1;
		$model->examine = $this->visitor['username'];
		$model->verify = $post->verify;
		if(!$model->save()) {
			return Message::warning($model->errors);
		}
		
		return Message::display(Language::get('verify_ok'));
	}	
	
	public function actionDelete()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		
		if(!$post->id){
			return Message::warning(Language::get('no_such_item'));
		}
		
		if(!ReportModel::deleteAll(['and', ['id' => $post->id], ['in', 'id', explode(',', $post->id)]])) {
			return Message::warning(Language::get('drop_fail'));	
		}
		return Message::display(Language::get('drop_ok'), ['report/index']);
	}
	
	public function actionSendmsg()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		
		if (!$post->id){
			return Message::warning(Language::get('Hacking Attempt'));
		}
		
		$report = ReportModel::find()->alias('r')->select('r.*, g.goods_name')->joinWith('goods g', false)->where(['userid' => $post->id])->asArray()->one();
		
		if(!($user = UserModel::find()->select('username')->where(['userid' => $post->id])->asArray()->one())){
			return Message::warning(Language::get('no_such_user'));
		}
		
		if(!Yii::$app->request->isPost)
		{
			$this->params['user'] = $user;
			return $this->render('../report.notice.html', $this->params);
		}
		else 
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			
			if(empty($post->content)){
				return Message::warning($model->errors);
			}
			
			$report['content'] = $post->content;
			$report['username'] = $user['username'];

			$pmer = Basewind::getPmer('touser_report', ['report' => $report]);
			if($pmer) {
				$pmer->sendFrom(0)->sendTo($report['userid'])->send();
			}
			
			return Message::display(Language::get('send_success'), ['report/index']);	
		}
	}
	
	private function getSearchOption()
	{
		return array(
            'username'		=> Language::get('username'),
            'goods_name' 	=> Language::get('report_goods'),
            'store_name' 	=> Language::get('report_store'),
		);
	}
	
	private function getConditions($post, $query = null)
	{
		if($query === null) {
			foreach(array_keys(ArrayHelper::toArray($post)) as $field) {
				if(in_array($field, ['username', 'goods_name', 'store_name', 'status'])) {
					return true;
				}
			}
			return false;
		}
		
		if($post->field && $post->search_name && in_array($post->field, ['username', 'goods_name', 'store_name']))
		{
			if($post->field == 'username'){
				$user = UserModel::find()->select('userid')->where(['username' => $post->search_name])->one();
				$query->andWhere(['u.userid' => $user->userid]);
			}
			
			if($post->field == 'goods_name'){
				$allId = GoodsModel::find()->select('goods_id')->where(['like', 'goods_name', $post->search_name])->column();
				$query->andWhere(['in', 'g.goods_id', $allId]);
			}
			
			if($post->field == 'store_name'){
				$store = StoreModel::find()->select('store_id')->where(['store_name' => $post->search_name])->one();;
				$query->andWhere(['s.store_id' => $store->store_id]);
			}
		}
		
		if(isset($post->status) && in_array($post->status, [0,1])) {
			$query->andWhere(['status' => $post->status]);
		}

		return $query;
	}
}
