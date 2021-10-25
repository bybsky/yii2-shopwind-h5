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

use common\models\UserModel;
use common\models\CashcardModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Timezone;
use common\library\Page;

/**
 * @Id CashcardController.php 2018.8.20 $
 * @author mosir
 */

class CashcardController extends \common\controllers\BaseAdminController
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
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['rp', 'page', 'printed']);
		
		if(!Yii::$app->request->isAjax) 
		{
			$this->params['filtered'] = $this->getConditions($post);
			
			$this->params['_foot_tags'] = Resource::import([
				'script' => 'jquery.plugins/flexigrid.js,jquery.ui/jquery.ui.js,jquery.ui/i18n/' . Yii::$app->language . '.js',
            	'style'=> 'jquery.ui/themes/smoothness/jquery.ui.css'
			]);
			
			$this->params['page'] = Page::seo(['title' => Language::get('cashcard_list')]);
			return $this->render('../cashcard.index.html', $this->params);
		}
		else
		{
			$query = CashcardModel::find()->alias('c')->select('c.*,u.username')->joinWith('user u')->indexBy('id');
			$query = $this->getConditions($post, $query);
			
			$orderFields = ['cardNo','money','username','add_time','printed','active_time','expire_time'];
			if(in_array($post->sortname, $orderFields) && in_array(strtolower($post->sortorder), ['asc', 'desc'])) {
				$query->orderBy([$post->sortname => strtolower($post->sortorder) == 'asc' ? SORT_ASC : SORT_DESC]);
			} else $query->orderBy(['id' => SORT_DESC]);
			
			$page = Page::getPage($query->count(), $post->rp ? $post->rp : 10);
			
			$result = ['page' => $post->page, 'total' => $query->count()];
			foreach ($query->offset($page->offset)->limit($page->limit)->asArray()->each() as $key => $val)
			{
				$list = array();
				$operation = "<a class='btn red' onclick=\"fg_delete({$key},'cashcard')\"><i class='fa fa-trash-o'></i>删除</a>";
				$operation .= "<span class='btn'><em><i class='fa fa-cog'></i>设置 <i class='arrow'></i></em><ul>";
				$operation .= "<li><a href=\"javascript:;\" onclick=\"fg_print({$key}, 1)\">制卡</a></li>";
				$operation .= "<li><a href=\"javascript:;\" onclick=\"fg_print({$key}, 0)\">取消制卡</a></li>";
				$operation .= "</ul>";
				$list['operation'] = $operation;
				$list['name'] = $val['name'];
				$list['cardNo'] = $val['cardNo'];
				$list['password'] = $val['password'];
				$list['money'] = $val['money'];
				$list['add_time'] = Timezone::localDate('Y-m-d H:i:s', $val['add_time']);
				$list['expire_time'] = Timezone::localDate('Y-m-d H:i:s', $val['expire_time']);
				$list['printed'] = $val['printed'] == 0 ? '<em class="no"><i class="fa fa-ban"></i>未制卡</em>' : '<em class="yes"><i class="fa fa-check-circle"></i>已制卡</em>';
				$list['username'] = $val['username'];
				$list['active_time'] = Timezone::localDate('Y-m-d H:i:s', $val['active_time']);
				$result['list'][$key] = $list;
			}
			return Page::flexigridXML($result);
		}
	}
	
	public function actionAdd()
	{
		if(!Yii::$app->request->isPost)
		{
			$this->params['_foot_tags'] = Resource::import([
				'script' => 'jquery.ui/jquery.ui.js,jquery.ui/i18n/' . Yii::$app->language . '.js',
            	'style'=> 'jquery.ui/themes/smoothness/jquery.ui.css'
			]);
			
			$this->params['page'] = Page::seo(['title' => Language::get('cashcard_add')]);
			return $this->render('../cashcard.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['quantity']);
			
			$model = new \backend\models\CashcardForm();
			if(!($cashcard = $model->create($post, true))) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('add_ok'), ['cashcard/index']);		
		}
	}
	
	public function actionEdit()
	{
		$id = intval(Yii::$app->request->get('id'));
		if(!$id || !($cashcard = CashcardModel::find()->where(['id' => $id])->one())) {
			return Message::warning(Language::get('no_such_cashcard'));
		}
		
		if(!Yii::$app->request->isPost)
		{
			$this->params['cashcard'] = ArrayHelper::toArray($cashcard);
			
			$this->params['page'] = Page::seo(['title' => Language::get('cashcard_edit')]);
			return $this->render('../cashcard.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['if_show', 'sort_order', 'recommended']);
			
			$model = new \backend\models\CashcardForm(['id' => $id]);
			if(!($cashcard = $model->save($post, true))) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_ok'), ['cashcard/index']);		
		}
	}
	
	// 目前只有用户不存在了，或充值卡未分配给用户，才允许删除
	public function actionDelete()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		foreach(explode(',', $post->id) as $id) {
			if($id && ($model = CashcardModel::findOne($id)) && (!$model->useId || !UserModel::findOne($model->useId))) {
				if(!$model->delete()) {
					return Message::warning($model->errors);
				}
			}
		}
		return Message::display(Language::get('drop_ok'));
	}

	public function actionPrinted()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['value']);
		CashcardModel::updateAll(['printed' => $post->value], ['in', 'id', explode(',', $post->id)]);
		return Message::display(Language::get('set_ok'));
	}
	
	/* 异步修改数据 */
    public function actionEditcol()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id', 'if_show', 'sort_order', 'recommended']);
		if(in_array($post->column, ['name', 'if_show', 'sort_order', 'recommended', 'tag'])) {
			$model = new \backend\models\CashcardForm(['id' => $post->id]);
			$query = CashcardModel::findOne($post->id);
			$query->{$post->column} = $post->value;
			if(!($cashcard = $model->save($query, true))) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_ok'));
		}
    }
	
	public function actionExport()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		if($post->id) $post->id = explode(',', $post->id);
		
		$query = CashcardModel::find()->indexBy('id')->orderBy(['id' => SORT_DESC]);
		if(!empty($post->id)) {
			$query->andWhere(['in', 'id', $post->id]);
		}
		else {
			$query = $this->getConditions($post, $query);
		}
		if($query->count() == 0) {
			return Message::warning(Language::get('no_such_cashcard'));
		}
		return \backend\models\CashcardExportForm::download($query->asArray()->all());		
	}
	
	private function getConditions($post, $query = null)
	{
		if($query === null) {
			foreach(array_keys(ArrayHelper::toArray($post)) as $field) {
				if(in_array($field, ['cardNo', 'name', 'add_time_from', 'add_time_to', 'active_time', 'printed'])) {
					return true;
				}
			}
			return false;
		}
		if($post->cardNo) {
			$query->andWhere(['cardNo' => $post->cardNo]);
		}
		if($post->name) {
			$query->andWhere(['like', 'name', $post->name]);
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
		if($post->active_time == 1) {
			$query->andWhere(['active_time' => 0]);
		}
		if($post->active_time == 2) {
			$query->andWhere(['>', 'active_time', 0]);
		}
		if($post->printed == 1) {
			$query->andWhere(['printed' => 0]);
		}
		if($post->printed == 2) {
			$query->andWhere(['>', 'printed', 0]);
		}
		
		return $query;
	}
}
