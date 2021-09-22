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

use common\models\NavigationModel;
use common\models\GcategoryModel;
use common\models\AcategoryModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;

/**
 * @Id NavigationController.php 2018.8.22 $
 * @author mosir
 */

class NavigationController extends \common\controllers\BaseAdminController
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
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['rp', 'page', 'sort_order','if_show', 'open_new']);
		
		if(!Yii::$app->request->isAjax) 
		{
			$this->params['filtered'] = $this->getConditions($post);
			$this->params['positions'] = $this->getPosition();

			$this->params['_foot_tags'] = Resource::import('jquery.plugins/flexigrid.js,inline_edit.js');
			
			$this->params['page'] = Page::seo(['title' => Language::get('navigation_list')]);
			return $this->render('../navigation.index.html', $this->params);
		}
		else
		{
			$query = NavigationModel::find()->indexBy('nav_id');
			$query = $this->getConditions($post, $query);
			
			$orderFields = ['title', 'type', 'sort_order','if_show', 'link', 'open_new'];
			if(in_array($post->sortname, $orderFields) && in_array(strtolower($post->sortorder), ['asc', 'desc'])) {
				$query->orderBy([$post->sortname => strtolower($post->sortorder) == 'asc' ? SORT_ASC : SORT_DESC]);
			} else $query->orderBy(['sort_order' => SORT_ASC, 'nav_id' => SORT_DESC]);
			
			$page = Page::getPage($query->count(), $post->rp ? $post->rp : 10);
			
			$result = ['page' => $post->page, 'total' => $query->count()];
			foreach ($query->offset($page->offset)->limit($page->limit)->asArray()->each() as $key => $val)
			{
				$list = array();
				$list['operation'] = "<a class='btn red' onclick=\"fg_delete({$key},'navigation')\"><i class='fa fa-trash-o'></i>删除</a><a class='btn blue' href='".Url::toRoute(['navigation/edit', 'id' => $key])."'><i class='fa fa-pencil-square-o'></i>编辑</a>";
				$list['title'] = '<span ectype="inline_edit" controller="navigation" fieldname="title" fieldid="'.$key.'" required="1" class="editable" title="'.Language::get('editable').'">'.$val['title'].'</span>';
				$list['type'] = $this->getPosition($val['type']);
				$list['link'] = '<span ectype="inline_edit" controller="navigation" fieldname="link" fieldid="'.$key.'" maxvalue="255" class="editable" title="'.Language::get('editable').'">'.$val['link'].'</span>';
				$list['sort_order'] = '<span ectype="inline_edit" controller="navigation" fieldname="sort_order" fieldid="'.$key.'" datatype="pint" maxvalue="255" class="editable" title="'.Language::get('editable').'">'.$val['sort_order'].'</span>';
				$list['if_show'] = $val['if_show'] == 0 ? '<em class="no" ectype="inline_edit" controller="navigation" fieldname="if_show" fieldid="'.$key.'" fieldvalue="0" title="'.Language::get('editable').'"><i class="fa fa-ban"></i>否</em>' : '<em class="yes" ectype="inline_edit" controller="navigation" fieldname="if_show" fieldid="'.$key.'" fieldvalue="1" title="'.Language::get('editable').'"><i class="fa fa-check-circle"></i>是</em>';
				$list['open_new'] = $val['open_new'] == 0 ? '<em class="no" ectype="inline_edit" controller="navigation" fieldname="open_new" fieldid="'.$key.'" fieldvalue="0" title="'.Language::get('editable').'"><i class="fa fa-ban"></i>否</em>' : '<em class="yes" ectype="inline_edit" controller="navigation" fieldname="open_new" fieldid="'.$key.'" fieldvalue="1" title="'.Language::get('editable').'"><i class="fa fa-check-circle"></i>是</em>';
				$result['list'][$key] = $list;
			}
			return Page::flexigridXML($result);
		}
	}
	
	public function actionAdd()
	{
		if(!Yii::$app->request->isPost)
		{
			$this->params['navigation'] = ['type' => 'middle', 'sort_order' => 255, 'link' => 'http://', 'if_show' => 1];
			$this->params['positions'] = $this->getPosition();
			$this->params['gcategories'] = GcategoryModel::getOptions(0,0);
			$this->params['acategories'] = AcategoryModel::getOptions();
			
			$this->params['_foot_tags'] = Resource::import('mlselection.js');
			
			$this->params['page'] = Page::seo(['title' => Language::get('navigation_add')]);
			return $this->render('../navigation.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['if_show', 'sort_order', 'open_new']);
			
			$model = new \backend\models\NavigationForm();
			if(!($navigation = $model->save($post, true))) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('add_ok'), ['navigation/index']);		
		}
	}
	
	public function actionEdit()
	{
		$id = intval(Yii::$app->request->get('id'));
		if(!$id || !($navigation = NavigationModel::find()->where(['nav_id' => $id])->asArray()->one())) {
			return Message::warning(Language::get('no_such_navigation'));
		}
		
		if(!Yii::$app->request->isPost)
		{
			$this->params['navigation'] = $navigation;
			$this->params['positions'] = $this->getPosition();
			$this->params['gcategories'] = GcategoryModel::getOptions(0,0);
			$this->params['acategories'] = AcategoryModel::getOptions();
			
			$this->params['_foot_tags'] = Resource::import('mlselection.js');
			
			$this->params['page'] = Page::seo(['title' => Language::get('navigation_edit')]);
			return $this->render('../navigation.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['if_show', 'sort_order', 'open_new']);
			
			$model = new \backend\models\NavigationForm(['nav_id' => $id]);
			if(!($navigation = $model->save($post, true))) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_ok'), ['navigation/index']);		
		}
	}
	// 允许删除店铺的文章
	public function actionDelete()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		foreach(explode(',', $post->id) as $id) {
			if(($model = NavigationModel::findOne($id)) && !$model->delete()) {
				return Message::warning($model->errors);
			}
		}
		return Message::display(Language::get('drop_ok'));
	}
	
	/* 异步修改数据 */
    public function actionEditcol()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id', 'if_show', 'sort_order', 'open_new']);
		if(in_array($post->column, ['title', 'if_show', 'sort_order', 'open_new', 'link'])) {
			$model = new \backend\models\NavigationForm(['nav_id' => $post->id]);
			$query = NavigationModel::findOne($post->id);
			$query->{$post->column} = $post->value;
			if(!($navigation = $model->save($query, true))) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_ok'));
		}
    }
	
	private function getPosition($code = null)
	{
		$positions = array(
            'header' => Language::get('header'),
            'middle' => Language::get('middle'),
            'footer' => Language::get('footer'),
        );
		if($code !== null) {
			return isset($positions[$code]) ? $positions[$code] : '';
		}
		return $positions;
	}

	private function getConditions($post, $query = null)
	{
		if($query === null) {
			foreach(array_keys(ArrayHelper::toArray($post)) as $field) {
				if(in_array($field, ['title', 'type'])) {
					return true;
				}
			}
			return false;
		}
		
		if($post->title) {
			$query->andWhere(['like', 'title', $post->title]);
		}
		if($post->type) {
			$query->andWhere(['type' => $post->type]);
		}

		return $query;
	}
}
