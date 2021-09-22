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

use common\models\GoodsModel;
use common\models\GcategoryModel;
use common\models\RecommendModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;

/**
 * @Id GoodsController.php 2018.8.8 $
 * @author mosir
 */

class GoodsController extends \common\controllers\BaseAdminController
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
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['rp', 'page', 'cate_id', 'closed']);
		
		if(!Yii::$app->request->isAjax) 
		{
			$this->params['gcategories'] = GcategoryModel::getOptions(0, -1, null, 2);
			$this->params['filtered'] = $this->getConditions($post);
			
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/flexigrid.js,inline_edit.js');
			
			$this->params['page'] = Page::seo(['title' => Language::get('goods_list')]);
			return $this->render('../goods.index.html', $this->params);
		}
		else
		{
			$query = GoodsModel::find()->alias('g')->select('g.goods_id,g.goods_name,g.cate_id,g.price,g.brand,g.if_show,g.closed,g.cate_name,s.store_name,gst.views')->joinWith('store s', false)->joinWith('goodsStatistics gst', false)->indexBy('goods_id');
			$query = $this->getConditions($post, $query);
			
			$orderFields = ['goods_name','price','store_name','brand','cate_name','if_show','closed','views'];
			if(in_array($post->sortname, $orderFields) && in_array(strtolower($post->sortorder), ['asc', 'desc'])) {
				$query->orderBy([$post->sortname => strtolower($post->sortorder) == 'asc' ? SORT_ASC : SORT_DESC]);
			} else $query->orderBy(['g.goods_id' => SORT_DESC]);
			
			$page = Page::getPage($query->count(), $post->rp ? $post->rp : 10);
			
			$result = ['page' => $post->page, 'total' => $query->count()];
			foreach ($query->offset($page->offset)->limit($page->limit)->asArray()->each() as $key => $val)
			{
				$list = array();
				$operation = "<a class='btn red' onclick=\"fg_delete({$key},'goods','',true)\"><i class='fa fa-trash-o'></i>删除</a>";
				$operation .= "<span class='btn'><em><i class='fa fa-cog'></i>设置 <i class='arrow'></i></em><ul>";
				$operation .= "<li><a href='".Url::toRoute(['goods/index', 'id' => $key], $this->params['homeUrl'])."' target=\"_blank\">查看</a></li>";
				$operation .= "<li><a href='".Url::toRoute(['goods/edit', 'id' => $key])."'>编辑</a></li>";
				$operation .= "<li><a href='".Url::toRoute(['goods/recommend', 'id' => $key])."'>推荐</a></li>";
				$operation .= "</ul>";
				$list['operation'] = $operation;
				$list['goods_name'] = '<span ectype="inline_edit" controller="goods" fieldname="goods_name" fieldid="'.$key.'"  required="1" class="editable" title="'.Language::get('editable').'">'.$val['goods_name'].'</span>';
				$list['price'] = $val['price'];
				$list['store_name'] = $val['store_name'];
				$list['brand'] = '<span ectype="inline_edit" controller="goods" fieldname="brand" fieldid="'.$key.'"  required="1" class="editable" title="'.Language::get('editable').'">'.$val['brand'].'</span>';
				$list['cate_name'] = GcategoryModel::formatCateName($val['cate_name'],false, '/');
				$list['if_show'] = $val['if_show'] == 0 ? '<em class="no" ectype="inline_edit" controller="goods" fieldname="if_show" fieldid="'.$key.'" fieldvalue="0" title="'.Language::get('editable').'"><i class="fa fa-ban"></i>否</em>' : '<em class="yes" ectype="inline_edit" controller="goods" fieldname="if_show" fieldid="'.$key.'" fieldvalue="1" title="'.Language::get('editable').'"><i class="fa fa-check-circle"></i>是</em>';
				$list['closed'] = $val['closed'] == 0 ? '<em class="no" ectype="inline_edit" controller="goods" fieldname="closed" fieldid="'.$key.'" fieldvalue="0" title="'.Language::get('editable').'"><i class="fa fa-ban"></i>否</em>' : '<em class="yes" ectype="inline_edit" controller="goods" fieldname="closed" fieldid="'.$key.'" fieldvalue="1" title="'.Language::get('editable').'"><i class="fa fa-check-circle"></i>是</em>';
				$list['views'] = $val['views'];
				$result['list'][$key]	= $list;
			}
			return Page::flexigridXML($result);
		}
	}
	
	public function actionEdit()
	{
		if(!Yii::$app->request->isPost)
		{
			$this->params['gcategories'] = GcategoryModel::find()->select('cate_name')->where(['parent_id' => 0, 'store_id' => 0])->indexBy('cate_id')->orderBy(['sort_order' => SORT_ASC, 'cate_id' => SORT_ASC])->column();
			
			$this->params['_foot_tags'] = Resource::import('mlselection.js');
			
			$this->params['page'] = Page::seo(['title' => Language::get('goods_edit')]);
			return $this->render('../goods.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['cate_id', 'closed']);
			if(!$post->id) {
				return Message::warning(Language::get('no_such_goods'));
			}
			$model = new \backend\models\GoodsForm(['goods_id' => Yii::$app->request->get('id')]);
			if(!$model->save($post, true)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_ok'), ['goods/index']);
		}
	}
	
	public function actionDelete()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		if(!$post->id) {
			return Message::warning(Language::get('no_such_goods'));
		}
		$model = new \backend\models\GoodsDeleteForm(['goods_id' => $post->id]);
		if(!$model->delete($post, true)) {
			return Message::warning($model->errors);
		}
		return Message::display(Language::get('drop_ok'), ['goods/index']);
	}
	
	public function actionRecommend()
	{
		if(!Yii::$app->request->isPost)
		{
			if(!($recommends = RecommendModel::find()->select('recom_name')->where(['store_id' => 0])->indexBy('recom_id')->column())) {
				return Message::warning(Language::get('set_recommend'), ['recommend/index']);
			}
			$this->params['recommends'] = $recommends;

			$this->params['page'] = Page::seo(['title' => Language::get('goods_recommend')]);
			return $this->render('../goods.recommend.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['id', 'recom_id']);
			if(!$post->id) {
				return Message::warning(Language::get('no_such_goods'));
			}
			$model = new \backend\models\GoodsRecommendForm(['goods_id' => Yii::$app->request->get('id')]);
			if(!$model->save($post, true)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_ok'), ['goods/index']);
		}
	}

	/* 异步修改数据 */
    public function actionEditcol()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id', 'if_show', 'closed']);
		if(in_array($post->column, ['goods_name','brand', 'if_show', 'closed'])) {
			$model = new \backend\models\GoodsForm(['goods_id' => $post->id]);
			$query = GoodsModel::findOne($post->id);
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
		
		$query = GoodsModel::find()->alias('g')->select('g.goods_id,g.goods_name,g.cate_id,g.price,g.brand,g.if_show,g.closed,g.cate_name,s.store_name,gst.views')->joinWith('store s', false)->joinWith('goodsStatistics gst', false)->indexBy('goods_id')->orderBy(['goods_id' => SORT_DESC]);
		if(!empty($post->id)) {
			$query->andWhere(['in', 'g.goods_id', $post->id]);
		}
		else {
			$query = $this->getConditions($post, $query);
		}
		if($query->count() == 0) {
			return Message::warning(Language::get('no_such_goods'));
		}
		return \backend\models\GoodsExportForm::download($query->asArray()->all());		
	}
	
	private function getConditions($post, $query = null)
	{
		if($query === null) {
			foreach(array_keys(ArrayHelper::toArray($post)) as $field) {
				if(in_array($field, ['goods_name', 'store_name', 'brand', 'cate_id'])) {
					return true;
				}
			}
			return false;
		}
		
		if($post->goods_name) {
			$query->andWhere(['like', 'goods_name', $post->goods_name]);
		}
		if($post->store_name) {
			$query->andWhere(['like', 'store_name', $post->store_name]);
		}
		if($post->brand) {
			$query->andWhere(['or', ['like', 'brand', $post->brand], ['like', 'goods_name', $post->brand]]);
		}
	
		if($post->cate_id) {
			$childIds = GcategoryModel::getDescendantIds($post->cate_id, 0);
			$query->andWhere(['in', 'g.cate_id', $childIds]);
		}
		if($post->closed) {
			$query->andWhere(['closed' => $post->closed]);
		}
		return $query;
	}
}