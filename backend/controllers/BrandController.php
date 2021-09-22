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

use common\models\BrandModel;
use common\models\GcategoryModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;

/**
 * @Id BrandController.php 2018.8.9 $
 * @author mosir
 */

class BrandController extends \common\controllers\BaseAdminController
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
			
			$this->params['page'] = Page::seo(['title' => Language::get('brand_list')]);
			return $this->render('../brand.index.html', $this->params);
		}
		else
		{
			$query = BrandModel::find()->indexBy('brand_id');
			$query = $this->getConditions($post, $query);
			
			$orderFields = ['brand_id','brand_name','tag', 'letter','sort_order','recommended','if_show'];
			if(in_array($post->sortname, $orderFields) && in_array(strtolower($post->sortorder), ['asc', 'desc'])) {
				$query->orderBy([$post->sortname => strtolower($post->sortorder) == 'asc' ? SORT_ASC : SORT_DESC]);
			} else $query->orderBy(['sort_order' => SORT_ASC, 'brand_id' => SORT_DESC]);
			
			$page = Page::getPage($query->count(), $post->rp ? $post->rp : 10);
			
			$result = ['page' => $post->page, 'total' => $query->count()];
			foreach ($query->offset($page->offset)->limit($page->limit)->asArray()->each() as $key => $val)
			{
				$list = array();
				$list['operation'] = "<a class='btn red' onclick=\"fg_delete({$key},'brand')\"><i class='fa fa-trash-o'></i>删除</a><a class='btn blue' href='".Url::toRoute(['brand/edit', 'id' => $key])."'><i class='fa fa-pencil-square-o'></i>编辑</a>";
				
				$list['brand_id'] = $key;
				$list['brand_name'] = '<span ectype="inline_edit" controller="brand" required="1" fieldname="brand_name" fieldid="'.$key.'" class="editable" title="'.Language::get('editable').'">'.$val['brand_name'].'</span>';
				$list['tag'] = '<span ectype="inline_edit" controller="brand" fieldname="tag" fieldid="'.$key.'" required="1" class="editable" title="'.Language::get('editable').'">'.$val['tag'].'</span>';
				$list['letter'] = '<span ectype="inline_edit" controller="brand" fieldname="letter" fieldid="'.$key.'" required="1" class="editable" title="'.Language::get('editable').'">'.$val['letter'].'</span>';
				
				$list['cate_name'] = GcategoryModel::find()->select('cate_name')->where(['cate_id' => $val['cate_id']])->scalar();
				
				$list['brand_logo'] = $val['brand_logo'] ? '<img src="'.Page::urlFormat($val['brand_logo']).'" height="25" />' : '';
				$list['sort_order'] = '<span ectype="inline_edit" controller="brand" fieldname="sort_order" fieldid="'.$key.'" datatype="pint" maxvalue="255" class="editable" title="'.Language::get('editable').'">'.$val['sort_order'].'</span>';
				$list['recommended'] = $val['recommended'] == 0 ? '<em class="no" ectype="inline_edit" controller="brand" fieldname="recommended" fieldid="'.$key.'" fieldvalue="0" title="'.Language::get('editable').'"><i class="fa fa-ban"></i>否</em>' : '<em class="yes" ectype="inline_edit" controller="brand" fieldname="recommended" fieldid="'.$key.'" fieldvalue="1" title="'.Language::get('editable').'"><i class="fa fa-check-circle"></i>是</em>';
				$list['if_show'] = $val['if_show'] == 0 ? '<em class="no" ectype="inline_edit" controller="brand" fieldname="if_show" fieldid="'.$key.'" fieldvalue="0" title="'.Language::get('editable').'"><i class="fa fa-ban"></i>否</em>' : '<em class="yes" ectype="inline_edit" controller="brand" fieldname="if_show" fieldid="'.$key.'" fieldvalue="1" title="'.Language::get('editable').'"><i class="fa fa-check-circle"></i>是</em>';
				$result['list'][$key] = $list;
			}
			return Page::flexigridXML($result);
		}
	}
	
	public function actionAdd()
	{
		if(!Yii::$app->request->isPost)
		{
			$this->params['brand'] = ['recommended' => 0, 'sort_order' => 255];
			
			// 取得一级商品分类
			$this->params['gcategories'] = GcategoryModel::getOptions(0, 0);
			
			$this->params['page'] = Page::seo(['title' => Language::get('brand_add')]);
			return $this->render('../brand.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['if_show', 'sort_order', 'recommended', 'cate_id']);
			
			$model = new \backend\models\BrandForm();
			if(!($brand = $model->save($post, true))) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('add_ok'), ['brand/index']);		
		}
	}
	
	public function actionEdit()
	{
		$id = intval(Yii::$app->request->get('id'));
		if(!$id || !($brand = BrandModel::find()->where(['brand_id' => $id])->one())) {
			return Message::warning(Language::get('no_such_brand'));
		}
		
		if(!Yii::$app->request->isPost)
		{
			$this->params['brand'] = ArrayHelper::toArray($brand);
			
			// 取得一级商品分类
			$this->params['gcategories'] = GcategoryModel::getOptions(0, 0);
			
			$this->params['page'] = Page::seo(['title' => Language::get('brand_edit')]);
			return $this->render('../brand.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['if_show', 'sort_order', 'recommended', 'cate_id']);

			$model = new \backend\models\BrandForm(['brand_id' => $id]);
			if(!($brand = $model->save($post, true))) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_ok'), ['brand/index']);		
		}
	}
	
	public function actionDelete()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		$post->id = explode(',', $post->id);
		if(is_array($post->id) && !empty($post->id)) {
			BrandModel::deleteAll(['in', 'brand_id', $post->id]);
		}
		return Message::display(Language::get('drop_ok'));
	}
	
	/* 异步修改数据 */
    public function actionEditcol()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id', 'if_show', 'sort_order', 'recommended']);
		if(in_array($post->column, ['brand_name', 'if_show', 'sort_order', 'recommended', 'tag', 'letter'])) {
			$model = new \backend\models\BrandForm(['brand_id' => $post->id]);
			$query = BrandModel::findOne($post->id);
			$query->{$post->column} = $post->column == 'letter' ? strtoupper($post->value) : $post->value;
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
		
		$query = BrandModel::find()->indexBy('brand_id')->orderBy(['sort_order' => SORT_ASC, 'brand_id' => SORT_DESC]);
		if(!empty($post->id)) {
			$query->andWhere(['in', 'brand_id', $post->id]);
		}
		else {
			$query = $this->getConditions($post, $query);
		}
		if($query->count() == 0) {
			return Message::warning(Language::get('no_such_brand'));
		}
		return \backend\models\BrandExportForm::download($query->asArray()->all());		
	}
	
	private function getConditions($post, $query = null)
	{
		if($query === null) {
			foreach(array_keys(ArrayHelper::toArray($post)) as $field) {
				if(in_array($field, ['brand_name', 'tag', 'letter'])) {
					return true;
				}
			}
			return false;
		}
		
		if($post->brand_name) {
			$query->andWhere(['like', 'brand_name', $post->brand_name]);
		}
		if($post->tag) {
			$query->andWhere(['like', 'tag', $post->tag]);
		}
		if($post->letter) {
			$query->andWhere(['like', 'letter', $post->letter]);
		}

		return $query;
	}
}
