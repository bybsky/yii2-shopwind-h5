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

use common\models\SgradeModel;
use common\models\StoreModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;

/**
 * @Id SgradeController.php 2018.8.9 $
 * @author mosir
 */

class SgradeController extends \common\controllers\BaseAdminController
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
			
			$this->params['page'] = Page::seo(['title' => Language::get('sgrade_list')]);
			return $this->render('../sgrade.index.html', $this->params);
		}
		else
		{
			$query = SgradeModel::find()->indexBy('grade_id');
			$query = $this->getConditions($post, $query);
			
			$orderFields = ['grade_name','goods_limit','space_limit','need_confirm', 'sort_order'];
			if(in_array($post->sortname, $orderFields) && in_array(strtolower($post->sortorder), ['asc', 'desc'])) {
				$query->orderBy([$post->sortname => strtolower($post->sortorder) == 'asc' ? SORT_ASC : SORT_DESC]);
			} else $query->orderBy(['sort_order' => SORT_ASC, 'grade_id' => SORT_DESC]);
			
			$page = Page::getPage($query->count(), $post->rp ? $post->rp : 10);
			
			$result = ['page' => $post->page, 'total' => $query->count()];
			foreach ($query->offset($page->offset)->limit($page->limit)->asArray()->each() as $key => $val)
			{
				$list = array();
				$operation = "<a class='btn red' onclick=\"fg_delete({$key},'sgrade')\"><i class='fa fa-trash-o'></i>删除</a>";
				$operation .= "<span class='btn'><em><i class='fa fa-cog'></i>设置 <i class='arrow'></i></em><ul>";
				$operation .= "<li><a href='".Url::toRoute(['sgrade/edit', 'id' => $key])."'>编辑</a></li>";
				$operation .= "<li><a href='".Url::toRoute(['sgrade/theme', 'id' => $key])."'>手机模板</a></li>";
				$operation .= "</ul>";
				$list['operation'] = $operation;
				$list['grade_name'] = $val['grade_name'];
				$list['goods_limit'] = $val['goods_limit'] ? $val['goods_limit'] : Language::get('no_limit');
				$list['space_limit'] = $val['space_limit'] ? $val['space_limit'] : Language::get('no_limit');
				$list['skins_limit'] = count(explode(',', $val['skins']));
				$list['charge'] = $val['charge'];
				$list['need_confirm']	= ($val['need_confirm'] == 0) ? '<em class="no" ectype="inline_edit" controller="sgrade" fieldname="need_confirm" fieldid="'.$key.'" fieldvalue="0" title="'.Language::get('editable').'"><i class="fa fa-ban"></i>否</em>' : '<em class="yes" ectype="inline_edit" controller="sgrade" fieldname="need_confirm" fieldid="'.$key.'" fieldvalue="1" title="'.Language::get('editable').'"><i class="fa fa-check-circle"></i>是</em>';
				$list['sort_order'] 	= '<span ectype="inline_edit" controller="sgrade" fieldname="sort_order" fieldid="'.$key.'" datatype="pint" class="editable" title="'.Language::get('editable').'">'.$val['sort_order'].'</span>';
				$result['list'][$key] = $list;
			}
			return Page::flexigridXML($result);
		}
	}
	
	public function actionAdd()
	{
		if(!Yii::$app->request->isPost)
		{
			$this->params['sgrade'] = ['need_confirm' => 1, 'sort_order' => 255];
			$this->params['page'] = Page::seo(['title' => Language::get('sgrade_add')]);
			return $this->render('../sgrade.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['need_confirm', 'sort_order', 'goods_limit', 'space_limit']);
			
			$model = new \backend\models\SgradeForm();
			if(!($sgrade = $model->save($post, true))) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('add_ok'), ['sgrade/index']);		
		}
	}
	
	public function actionEdit()
	{
		$id = intval(Yii::$app->request->get('id'));
		if(!$id || !($sgrade = SgradeModel::find()->where(['grade_id' => $id])->one())) {
			return Message::warning(Language::get('no_such_sgrade'));
		}
		
		if(!Yii::$app->request->isPost)
		{
			$this->params['sgrade'] = ArrayHelper::toArray($sgrade);
			
			$this->params['page'] = Page::seo(['title' => Language::get('sgrade_edit')]);
			return $this->render('../sgrade.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['need_confirm', 'sort_order', 'goods_limit', 'space_limit']);
			
			$model = new \backend\models\SgradeForm(['grade_id' => $id]);
			if(!($sgrade = $model->save($post, true))) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_ok'), ['sgrade/index']);		
		}
	}
	
	public function actionDelete()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		
		// 默认等级不能删除
		$post->id = array_diff(explode(',', $post->id), [1]);
		foreach($post->id as $id) {
			if(StoreModel::find()->where(['sgrade' => $id])->exists()) {
				return Message::warning(sprintf(Language::get('donot_drop_by_store'), $id));
			} elseif(($model = SgradeModel::findOne($id)) && !$model->delete()) {
				return Message::warning($model->errors);
			}
		}
		return Message::display(Language::get('drop_ok'));
	}
	
	public function actionTheme()
    {
		$get = Basewind::trimAll(Yii::$app->request->get(), true, ['id'], ['client']);
		
		if(!$get->id || !($sgrade = SgradeModel::find()->select('grade_id,skins')->where(['grade_id' => $get->id])->one())) {
			return Message::warning(Language::get('sgrade_empty'));
		}
	
		if(!Yii::$app->request->isPost) 
		{
			$themes = Page::listTemplate('store');
			$this->params['themes'] = $themes;
			$this->params['baseUrl'] = Basewind::homeUrl();
			$this->params['skins'] = explode(',', $sgrade->skins);
			
			$this->params['page'] = Page::seo(['title' => Language::get('sgrade_theme')]);
			return $this->render('../sgrade.theme.html', $this->params);
		}
		
		else
		{
			$post = Yii::$app->request->post();
			$sgrade->skins = (isset($post['skins']) && !empty($post['skins'])) ? implode(',', $post['skins']) : 'default|default';
			
			if(!$sgrade->save()) {
				return Message::warning($sgrade->errors);
			}
			return Message::display(Language::get('set_skins_ok'));
		}
    }
	
	public function actionExport()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		if($post->id) $post->id = explode(',', $post->id);
		
		$query = SgradeModel::find()->indexBy('grade_id')->orderBy(['sort_order' => SORT_ASC, 'grade_id' => SORT_DESC]);
		if(!empty($post->id)) {
			$query->andWhere(['in', 'grade_id', $post->id]);
		}
		else {
			$query = $this->getConditions($post, $query);
		}
		if($query->count() == 0) {
			return Message::warning(Language::get('no_such_sgrade'));
		}
		return \backend\models\SgradeExportForm::download($query->asArray()->all());		
	}
	
	/* 异步修改数据 */
    public function actionEditcol()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id', 'need_confirm', 'sort_order']);
		if(in_array($post->column, ['need_confirm', 'sort_order'])) {
			
			$model = new \backend\models\SgradeForm(['grade_id' => $post->id]);
			$query = SgradeModel::findOne($post->id);
			$query->{$post->column} = $post->value;
			if(!$model->save($query, true)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_ok'));	
		}
    }
	
	private function getConditions($post, $query = null)
	{
		if($query === null) {
			foreach(array_keys(ArrayHelper::toArray($post)) as $field) {
				if(in_array($field, ['grade_name'])) {
					return true;
				}
			}
			return false;
		}
		
		if($post->grade_name) {
			$query->andWhere(['like', 'grade_name', $post->grade_name]);
		}
		return $query;
	}
}
