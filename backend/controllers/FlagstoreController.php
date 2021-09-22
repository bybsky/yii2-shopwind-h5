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

use common\models\FlagstoreModel;
use common\models\BrandModel;
use common\models\GcategoryModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;

/**
 * @Id FlagstoreController.php 2018.8.17 $
 * @author mosir
 */

class FlagstoreController extends \common\controllers\BaseAdminController
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
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/flexigrid.js,inline_edit.js');
			
			$this->params['page'] = Page::seo(['title' => Language::get('flagstore_list')]);
			return $this->render('../flagstore.index.html', $this->params);
		}
		else
		{
			$query = FlagstoreModel::find()->alias('fs')->select('fs.*,b.brand_name,s.store_name,gc.cate_name')->joinWith('brand b', false)->joinWith('gcategory gc', false)->joinWith('store s', false)->indexBy('fid');
			
			$orderFields = ['keyword', 'status', 'sort_order'];
			if(in_array($post->sortname, $orderFields) && in_array(strtolower($post->sortorder), ['asc', 'desc'])) {
				$query->orderBy([$post->sortname => strtolower($post->sortorder) == 'asc' ? SORT_ASC : SORT_DESC]);
			} else $query->orderBy(['sort_order' => SORT_ASC, 'fid' => SORT_DESC]);
			
			$page = Page::getPage($query->count(), $post->rp ? $post->rp : 10);
			
			$result = ['page' => $post->page, 'total' => $query->count()];
			foreach ($query->offset($page->offset)->limit($page->limit)->asArray()->each() as $key => $val)
			{
				$list = array();
				$list['operation'] = "<a class='btn red' onclick=\"fg_delete({$key},'flagstore')\"><i class='fa fa-trash-o'></i>删除</a><a class='btn blue' href='".Url::toRoute(['flagstore/edit', 'id' => $key])."'><i class='fa fa-pencil-square-o'></i>编辑</a>";
				$list['store_name'] = $val['store_name'];
				$list['brand_name'] = $val['brand_name'];
				$list['cate_name']  = $val['cate_name'];
				$list['keyword']    = $val['keyword'];
				$list['sort_order'] 	= '<span ectype="inline_edit" controller="flagstore" fieldname="sort_order" fieldid="'.$key.'" datatype="pint" class="editable" title="'.Language::get('editable').'">'.$val['sort_order'].'</span>';
				$list['status']	= ($val['status'] == 0) ? '<em class="no" ectype="inline_edit" controller="flagstore" fieldname="status" fieldid="'.$key.'" fieldvalue="0" title="'.Language::get('editable').'"><i class="fa fa-ban"></i>否</em>' : '<em class="yes" ectype="inline_edit" controller="flagstore" fieldname="status" fieldid="'.$key.'" fieldvalue="1" title="'.Language::get('editable').'"><i class="fa fa-check-circle"></i>是</em>';
				$result['list'][$key] = $list;
			}
			return Page::flexigridXML($result);
		}
	}
	
	public function actionAdd()
	{
		if(!Yii::$app->request->isPost)
		{
			$this->params['status'] = [Language::get('open'), Language::get('close')];
			$this->params['brands'] = BrandModel::find()->select('brand_name')->where(['if_show' => 1])->indexBy('brand_id')->orderBy(['sort_order' => SORT_ASC, 'brand_id' => SORT_DESC])->column();
			$this->params['gcategories'] = GcategoryModel::getOptions(0);
			
			$this->params['page'] = Page::seo(['title' => Language::get('flagstore_add')]);
			return $this->render('../flagstore.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['brand_id', 'cate_id', 'status', 'sort_order']);
			
			$model = new \backend\models\FlagstoreForm();
			if(!($flagstore = $model->save($post, true))) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('add_ok'), ['flagstore/index']);		
		}
	}
	
	public function actionEdit()
	{
		$id = intval(Yii::$app->request->get('id'));
		if(!$id || !($flagstore = FlagstoreModel::find()->alias('fs')->select('fs.*,s.store_name')->joinWith('store s', false)->where(['fid' => $id])->asArray()->one())) {
			return Message::warning(Language::get('no_such_flagstore'));
		}
		
		if(!Yii::$app->request->isPost)
		{
			$this->params['status'] = [Language::get('open'), Language::get('close')];
			$this->params['brands'] = BrandModel::find()->select('brand_name')->where(['if_show' => 1])->indexBy('brand_id')->orderBy(['sort_order' => SORT_ASC, 'brand_id' => SORT_DESC])->column();
			$this->params['gcategories'] = GcategoryModel::getOptions(0);
			$this->params['flagstore'] = $flagstore;
			
			$this->params['page'] = Page::seo(['title' => Language::get('flagstore_edit')]);
			return $this->render('../flagstore.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['brand_id', 'cate_id', 'status', 'sort_order']);
			
			$model = new \backend\models\FlagstoreForm(['fid' => $id]);
			if(!($flagstore = $model->save($post, true))) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_ok'), ['flagstore/index']);		
		}
	}
	
	public function actionDelete()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		foreach(explode(',', $post->id) as $id) {
			if(($model = FlagstoreModel::findOne($id)) && !$model->delete()) {
				return Message::warning($model->errors);
			}
		}
		return Message::display(Language::get('drop_ok'));
	}
	
	/* 异步修改数据 */
    public function actionEditcol()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id', 'status', 'sort_order']);
		if(in_array($post->column, ['status', 'sort_order'])) {
			$model = new \backend\models\FlagstoreForm(['fid' => $post->id]);
			$query = FlagstoreModel::findOne($post->id);
			$query->{$post->column} = $post->value;
			if(!($flagstore = $model->save($query, true))) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_ok'));
		}
    }
	
	public function actionExport()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		if($post->id) $post->id = explode(',', $post->id);
		
		$query = FlagstoreModel::find()->alias('fs')->select('fs.*,b.brand_name,s.store_name,gc.cate_name')->joinWith('brand b', false)->joinWith('gcategory gc', false)->joinWith('store s', false)->indexBy('fid')->orderBy(['sort_order' => SORT_ASC, 'fid' => SORT_DESC]);
		if(!empty($post->id)) {
			$query->andWhere(['in', 'fid', $post->id]);
		}
		if($query->count() == 0) {
			return Message::warning(Language::get('no_such_flagstore'));
		}
		return \backend\models\flagstoreExportForm::download($query->asArray()->all());		
	}
}
