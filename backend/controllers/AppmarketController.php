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
use yii\helpers\Url;

use common\models\AppmarketModel;
use common\models\UploadedFileModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;
use common\library\Def;
use common\library\Plugin;

/**
 * @Id AppmarketController.php 2018.8.24 $
 * @author mosir
 */

class AppmarketController extends \common\controllers\BaseAdminController
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
			$this->params['page'] = Page::seo(['title' => Language::get('appmarket_list')]);
			return $this->render('../appmarket.index.html', $this->params);
		}
		else
		{
			$periodList = AppmarketModel::getPeriodList();
			$query = AppmarketModel::find()->indexBy('aid');
			
			$orderFields = ['logo','title','category','sales','status'];
			if(in_array($post->sortname, $orderFields) && in_array(strtolower($post->sortorder), ['asc', 'desc'])) {
				$query->orderBy([$post->sortname => strtolower($post->sortorder) == 'asc' ? SORT_ASC : SORT_DESC]);
			} else $query->orderBy(['aid' => SORT_ASC]);
			
			$page = Page::getPage($query->count(), $post->rp ? $post->rp : 10);
			
			$result = ['page' => $post->page, 'total' => $query->count()];
			foreach ($query->offset($page->offset)->limit($page->limit)->asArray()->each() as $key => $val)
			{
				$list = array();
				$config = unserialize($val['config']);
				$period = '';
				foreach($periodList as $k => $v) {
					if(in_array($v['key'], $config['period'])) {
						$period .= "<label><input type='checkbox' disabled='disabled' value='{$v}' checked='checked' />".$v['value']."</label>&nbsp;&nbsp";
					}
				} 
				$list['operation'] = "<a class='btn red' onclick=\"fg_delete({$key},'appmarket')\"><i class='fa fa-trash-o'></i>删除</a><a class='btn blue' href='".Url::toRoute(['appmarket/edit', 'id' => $key])."'><i class='fa fa-pencil-square-o'></i>编辑</a>";
				$list['name'] = Language::get($val['appid']);
				$list['logo'] = '<img src="'.Page::urlFormat($val['logo']).'" height="25" />';
				$list['title'] = $val['title'];
				$list['category'] = $val['category'] == 1 ? Language::get('promotool') : '';
				$list['charge'] = $config['charge'].'元/月';
				$list['period'] = $period;
				$list['sales'] = $val['sales'];
				$list['purchase'] = $val['purchase'] == 0 ? '<em class="no" ectype="inline_edit" controller="appmarket" fieldname="purchase" fieldid="'.$key.'" fieldvalue="0" title="'.Language::get('editable').'"><i class="fa fa-ban"></i>否</em>' : '<em class="yes" ectype="inline_edit" controller="appmarket" fieldname="purchase" fieldid="'.$key.'" fieldvalue="1" title="'.Language::get('editable').'"><i class="fa fa-check-circle"></i>是</em>';
				$list['status'] = $val['status'] == 0 ? '<em class="no" ectype="inline_edit" controller="appmarket" fieldname="status" fieldid="'.$key.'" fieldvalue="0" title="'.Language::get('editable').'"><i class="fa fa-ban"></i>否</em>' : '<em class="yes" ectype="inline_edit" controller="appmarket" fieldname="status" fieldid="'.$key.'" fieldvalue="1" title="'.Language::get('editable').'"><i class="fa fa-check-circle"></i>是</em>';
				$result['list'][$key]= $list;
			}
			return Page::flexigridXML($result);
		}
	}
	
	public function actionAdd()
	{
		if(!Yii::$app->request->isPost)
		{
			$this->params['applist'] = AppmarketModel::getAppList();
			$this->params['period'] = AppmarketModel::getPeriodList();
			
			// 属于应用的附件（游离图）
			$appmarket['desc_images'] = UploadedFileModel::find()->select('file_id,file_path,file_name')->where(['store_id' => 0, 'item_id' => 0, 'belong' => Def::BELONG_APPMARKET])->orderBy(['file_id' => SORT_ASC])->asArray()->all();
			$this->params['appmarket'] = array_merge($appmarket, ['status' => 1]);
			
			// 编辑器图片批量上传器
			$this->params['build_upload'] = Plugin::getInstance('uploader')->autoBuild(true)->create([
                'belong' 		=> Def::BELONG_APPMARKET,
                'item_id' 		=> 0,
                'upload_url' 	=> Url::toRoute(['upload/add']),
                'multiple' 		=> true
			]);
			
			// 所见即所得编辑器
			$this->params['build_editor'] = Plugin::getInstance('editor')->autoBuild(true)->create(['name' => 'description']);
			
			$this->params['page'] = Page::seo(['title' => Language::get('appmarket_add')]);
			return $this->render('../appmarket.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['purchase', 'status', 'category']);
			
			$model = new \backend\models\AppmarketForm();
			if(!($appmarket = $model->save($post, true))) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('add_ok'), ['appmarket/index']);
		}
	}
	
	public function actionEdit()
	{
		$id = intval(Yii::$app->request->get('id', 0));
		if(!$id || !($appmarket = AppmarketModel::find()->where(['aid' => $id])->asArray()->one())) {
			return Message::warning(Language::get('no_such_appmarket'));
		}
		
		if(!Yii::$app->request->isPost)
		{
			$this->params['applist'] = AppmarketModel::getAppList();
			$this->params['period'] = AppmarketModel::getPeriodList();
			
			// 属于应用的附件
			$appmarket['desc_images'] = UploadedFileModel::find()->select('file_id,file_path,file_name')->where(['store_id' => 0, 'item_id' => $id, 'belong' => Def::BELONG_APPMARKET])->orderBy(['file_id' => SORT_ASC])->asArray()->all();
			$this->params['appmarket'] = array_merge($appmarket, ['config' => unserialize($appmarket['config'])]);
			
			// 编辑器图片批量上传器
			$this->params['build_upload'] = Plugin::getInstance('uploader')->autoBuild(true)->create([
                'belong' 		=> Def::BELONG_APPMARKET,
                'item_id' 		=> $id,
                'upload_url' 	=> Url::toRoute(['upload/add']),
                'multiple' 		=> true
			]);
			
			// 所见即所得编辑器
			$this->params['build_editor'] = Plugin::getInstance('editor')->autoBuild(true)->create(['name' => 'description']);
			
			$this->params['page'] = Page::seo(['title' => Language::get('appmarket_edit')]);
			return $this->render('../appmarket.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['purchase', 'status', 'category']);
			
			$model = new \backend\models\AppmarketForm(['aid' => $id]);
			if(!($appmarket = $model->save($post, true))) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_ok'), ['appmarket/index']);
		}
	}
	
	public function actionDelete()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		if(!$post->id) {
			return Message::warning(Language::get('no_such_app'));
		}
		$model = new \backend\models\AppmarketDeleteForm(['aid' => $post->id]);
		if(!$model->delete($post, true)) {
			return Message::warning($model->errors);
		}
		return Message::display(Language::get('drop_ok'), ['appmarket/index']);
	}
	
	/* 异步删除编辑期上传的图片 */
	public function actionDeleteimage()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		foreach(explode(',', $post->id) as $id) {
			UploadedFileModel::deleteFileByQuery(UploadedFileModel::find()->where(['belong' => Def::BELONG_APPMARKET, 'file_id' => $id])->asArray()->all());
		}
		return Message::display(Language::get('drop_ok'));
	}
	
	/* 异步修改数据 */
    public function actionEditcol()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id', 'purchase', 'status']);
		if(in_array($post->column, ['purchase','status'])) {
			$model = new \backend\models\AppmarketForm(['aid' => $post->id]);
			$query = AppmarketModel::findOne($post->id);
			$query->config = Basewind::trimAll(unserialize($query->config), true);
			$query->{$post->column} = $post->value;
			if(!($appmarket = $model->save($query, true))) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_ok'));
		}
    }
}
