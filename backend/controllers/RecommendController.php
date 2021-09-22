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

use common\models\RecommendModel;
use common\models\GoodsModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;

/**
 * @Id RecommendController.php 2018.8.14 $
 * @author mosir
 */

class RecommendController extends \common\controllers\BaseAdminController
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
			
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/flexigrid.js,inline_edit.js');
			
			$this->params['page'] = Page::seo(['title' => Language::get('recommend_list')]);
			return $this->render('../recommend.index.html', $this->params);
		}
		else
		{
			$query = RecommendModel::find()->with('recommendGoods')->indexBy('recom_id');
			$query = $this->getConditions($post, $query);
			
			$orderFields = ['recom_id','recom_name'];
			if(in_array($post->sortname, $orderFields) && in_array(strtolower($post->sortorder), ['asc', 'desc'])) {
				$query->orderBy([$post->sortname => strtolower($post->sortorder) == 'asc' ? SORT_ASC : SORT_DESC]);
			} else $query->orderBy(['recom_id' => SORT_DESC]);
			
			$page = Page::getPage($query->count(), $post->rp ? $post->rp : 10);
			
			$result = ['page' => $post->page, 'total' => $query->count()];
			foreach ($query->offset($page->offset)->limit($page->limit)->asArray()->each() as $key => $val)
			{
				$list = array();
				$list['operation'] = "<a class='btn red' onclick=\"fg_delete({$key},'recommend')\"><i class='fa fa-trash-o'></i>删除</a><a class='btn blue' href='".Url::toRoute(['recommend/edit', 'id' => $key])."'><i class='fa fa-pencil-square-o'></i>编辑</a><a class='btn orange' href='".Url::toRoute(['recommend/goods', 'id' => $key])."'><i class='fa fa-search'></i>查看商品</a>";
				$list['recom_id'] = $val['recom_id'];
				$list['recom_name'] = '<span ectype="inline_edit" controller="recommend" fieldname="recom_name" fieldid="'.$key.'"  required="1" class="editable" title="'.Language::get('editable').'">'.$val['recom_name'].'</span>';
				$list['goods_count'] = count($val['recommendGoods']);
				$result['list'][$key]	= $list;
			}
			return Page::flexigridXML($result);
		}
	}
	
	public function actionAdd()
	{
		if(!Yii::$app->request->isPost)
		{
			$this->params['page'] = Page::seo(['title' => Language::get('recmmend_add')]);
			return $this->render('../recommend.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			$model = new \backend\models\RecommendForm();
			if(!$model->save($post, true)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('add_ok'), ['recommend/index']);
		}
	}

	public function actionEdit()
	{
		$id = intval(Yii::$app->request->get('id'));
		if(!$id || !($recommend = RecommendModel::find()->where(['recom_id' => $id])->asArray()->one())) {
			return Message::warning(Language::get('recommend_empty'));
		}
		
		if(!Yii::$app->request->isPost)
		{
			$this->params['recommend'] = $recommend;
			$this->params['page'] = Page::seo(['title' => Language::get('recmmend_edit')]);
			return $this->render('../recommend.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			$model = new \backend\models\RecommendForm(['recom_id' => $id]);
			if(!$model->save($post, true)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_ok'), ['recommend/index']);
		}
	}
	
	public function actionDelete()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		if(!$post->id) {
			return Message::warning(Language::get('no_such_recommend'));
		}
		$model = new \backend\models\RecommendDeleteForm(['recom_id' => $post->id]);
		if(!$model->delete($post, true)) {
			return Message::warning($model->errors);
		}
		return Message::display(Language::get('drop_ok'), ['recommend/index']);
	}
	
	public function actionGoods()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['rp', 'page', 'id']);
		if(!$post->id || !($recommend = RecommendModel::find()->where(['recom_id' => $post->id])->with('recommendGoods')->asArray()->one())) {
			return Message::warning(Language::get('no_such_recommend'));
		}
		
		if(!Yii::$app->request->isAjax) 
		{
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/flexigrid.js,inline_edit.js');
			
			$this->params['page'] = Page::seo(['title' => Language::get('recommend_goods')]);
			return $this->render('../recommend.goods.html', $this->params);
		}
		else
		{
			$allId = array();
			foreach($recommend['recommendGoods'] as $goods) {
				$allId[] = $goods['goods_id'];
			}
			
			$query = GoodsModel::find()->alias('g')->select('g.goods_id,g.goods_name,g.price,s.store_name')->where(['in', 'g.goods_id', $allId])->joinWith('store s', false)->indexBy('goods_id')->orderBy(['goods_id' => SORT_DESC]);
			$page = Page::getPage($query->count(), $post->rp ? $post->rp : 10);

			$result = ['page' => $post->page, 'total' => $query->count()];
			foreach ($query->offset($page->offset)->limit($page->limit)->asArray()->each() as $key => $val)
			{
				$list = array();
				$operation = "<a class='btn red' onclick=\"fg_cancel({$key}, 'recommend')\"><i class='fa fa-ban'></i>取消推荐</a>";
				$operation .= "<a class='btn orange' href='".Url::toRoute(['goods/index', 'id' => $key], $this->params['homeUrl'])."' target=\"_blank\"><i class='fa fa-search'></i>查看商品</a>";
				$list['operation'] = $operation;
				$list['recom_name'] = $recommend['recom_name'];
				$list['goods_name'] = $val['goods_name'];
				$list['price'] = $val['price'];
				$list['store_name'] = $val['store_name'];
				$result['list'][$key]	= $list;
			}
			return Page::flexigridXML($result);
		}
	}
	
	/* 取消推荐 */
    public function actionCancel()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		if(!$post->id) {
			return Message::warning(Language::get('no_such_goods'));
		}
		$model = new \backend\models\RecommendCancelForm(['goods_id' => $post->id]);
		if(!$model->cancel($post, true)) {
			return Message::warning($model->errors);
		}
		return Message::display(Language::get('cancel_ok'), ['recommend/index']);
    }

	
	/* 异步修改数据 */
    public function actionEditcol()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);
		if(in_array($post->column, ['recom_name'])) {
			$model = new \backend\models\RecommendForm(['recom_id' => $post->id]);
			$query = RecommendModel::findOne($post->id);
			$query->{$post->column} = $post->value;
			if(!($brand = $model->save($query, true))) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_ok'));
		}
    }
	
	public function actionExport()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		if($post->id) $post->id = explode(',', $post->id);
		
		$query = RecommendModel::find()->with('recommendGoods')->indexBy('recom_id')->orderBy(['recom_id' => SORT_DESC]);
		if(!empty($post->id)) {
			$query->andWhere(['in', 'recom_id', $post->id]);
		}
		else {
			$query = $this->getConditions($post, $query);
		}
		if($query->count() == 0) {
			return Message::warning(Language::get('no_such_recommend'));
		}
		return \backend\models\RecommendExportForm::download($query->asArray()->all());		
	}
	
	private function getConditions($post, $query = null)
	{
		if($query === null) {
			foreach(array_keys(ArrayHelper::toArray($post)) as $field) {
				if(in_array($field, ['recom_name'])) {
					return true;
				}
			}
			return false;
		}
		
		if($post->recom_name) {
			$query->andWhere(['like', 'recom_name', $post->recom_name]);
		}
		return $query;
	}
}
