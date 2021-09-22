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

use common\models\ArticleModel;
use common\models\AcategoryModel;
use common\models\UploadedFileModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Timezone;
use common\library\Page;
use common\library\Def;
use common\library\Plugin;

/**
 * @Id ArticleController.php 2018.8.22 $
 * @author mosir
 */

class ArticleController extends \common\controllers\BaseAdminController
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
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['rp', 'page', 'cate_id', 'articletype']);
		
		if(!Yii::$app->request->isAjax) 
		{
			$this->params['filtered'] = $this->getConditions($post);
			$this->params['acategories'] = AcategoryModel::getOptions();
			
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/flexigrid.js,inline_edit.js');
			
			$this->params['page'] = Page::seo(['title' => Language::get('article_list')]);
			return $this->render('../article.index.html', $this->params);
		}
		else
		{
			$query = ArticleModel::find()->alias('a')->select('a.article_id,a.title,a.cate_id,a.store_id,a.link,a.sort_order,a.if_show,a.add_time,ac.cate_name')->joinWith('acategory ac', false)->indexBy('article_id');
			$query = $this->getConditions($post, $query);
			
			$orderFields = ['title', 'cate_name', 'sort_order','if_show', 'add_time'];
			if(in_array($post->sortname, $orderFields) && in_array(strtolower($post->sortorder), ['asc', 'desc'])) {
				$query->orderBy([$post->sortname => strtolower($post->sortorder) == 'asc' ? SORT_ASC : SORT_DESC]);
			} else $query->orderBy(['sort_order' => SORT_ASC, 'article_id' => SORT_DESC]);
			
			$page = Page::getPage($query->count(), $post->rp ? $post->rp : 10);
			
			$result = ['page' => $post->page, 'total' => $query->count()];
			foreach ($query->offset($page->offset)->limit($page->limit)->asArray()->each() as $key => $val)
			{
				$list = array();
				$list['operation'] = "<a class='btn red' onclick=\"fg_delete({$key}, 'article')\"><i class='fa fa-trash-o'></i>删除</a><a class='btn blue' href='".Url::toRoute(['article/edit', 'id' => $key])."'><i class='fa fa-pencil-square-o'></i>编辑</a><a target='_blank' class='btn blue' href='".Url::toRoute(['article/view', 'id' => $key], $this->params['homeUrl'])."'><i class='fa fa-eye'></i>查看</a>";
				$list['title'] = '<span ectype="inline_edit" controller="article" fieldname="title" fieldid="'.$key.'"  required="1" class="editable" title="'.Language::get('editable').'">'.$val['title'].'</span>';
				$list['cate_name'] = $val['cate_name'];
				$list['articletype'] = $val['store_id'] == 0 ? Language::get('backend_article') : Language::get('store_article');
				$list['sort_order'] = '<span ectype="inline_edit" controller="article" fieldname="sort_order" fieldid="'.$key.'" datatype="pint" maxvalue="255" class="editable" title="'.Language::get('editable').'">'.$val['sort_order'].'</span>';
				$list['if_show'] = $val['if_show'] == 0 ? '<em class="no" ectype="inline_edit" controller="article" fieldname="if_show" fieldid="'.$key.'" fieldvalue="0" title="'.Language::get('editable').'"><i class="fa fa-ban"></i>否</em>' : '<em class="yes" ectype="inline_edit" controller="article" fieldname="if_show" fieldid="'.$key.'" fieldvalue="1" title="'.Language::get('editable').'"><i class="fa fa-check-circle"></i>是</em>';
				$list['add_time'] = Timezone::localDate('Y-m-d H:i:s',$val['add_time']);
				$result['list'][$key] = $list;
			}
			return Page::flexigridXML($result);
		}
	}
	
	public function actionAdd()
	{
		if(!Yii::$app->request->isPost)
		{
			$article = ['cate_id' => 0, 'sort_order' => 255, 'link' => '', 'if_show' => 1, 'store_id' => 0];
			
			// 属于文章的图片
			$article['desc_images'] = UploadedFileModel::find()->select('file_id,file_path,file_name,file_type')
				->where(['item_id' => 0, 'belong' => Def::BELONG_ARTICLE, 'store_id' => $article['store_id']])
				->andWhere(['in', 'file_type', explode(',', Def::IMAGE_FILE_TYPE)])
				->orderBy(['file_id' => SORT_ASC])->asArray()->all();
			
			// 属于文章的附件（文件）
			$article['desc_files'] = UploadedFileModel::find()->select('file_id,file_path,file_name,file_type')
				->where(['item_id' => 0, 'belong' => Def::BELONG_ARTICLE, 'store_id' => $article['store_id']])
				->andWhere(['not in', 'file_type', explode(',', Def::IMAGE_FILE_TYPE)])
				->orderBy(['file_id' => SORT_DESC])->asArray()->all();
			
			$this->params['article'] = $article;
			$this->params['parents'] = AcategoryModel::getOptions();
			
			// 编辑器图片批量上传器
			$this->params['build_upload'] = Plugin::getInstance('uploader')->autoBuild(true)->create([
                'belong' 		=> Def::BELONG_ARTICLE,
                'item_id' 		=> 0,
                'upload_url' 	=> Url::toRoute(['upload/add']),
                'multiple' 		=> true,
				'archived' 		=> true
			]);
			
			// 所见即所得编辑器
			$this->params['build_editor'] = Plugin::getInstance('editor')->autoBuild(true)->create(['name' => 'description']);
			$this->params['imageJsonArray'] = json_encode(explode(',', Def::IMAGE_FILE_TYPE));
			
			$this->params['page'] = Page::seo(['title' => Language::get('article_add')]);
			return $this->render('../article.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['cate_id', 'if_show', 'sort_order']);
			
			$model = new \backend\models\ArticleForm();
			if(!($article = $model->save($post, true))) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('add_ok'), ['article/index']);		
		}
	}
	
	public function actionEdit()
	{
		$id = intval(Yii::$app->request->get('id'));
		if(!$id || !($article = ArticleModel::find()->where(['article_id' => $id])->asArray()->one())) {
			return Message::warning(Language::get('no_such_article'));
		}
		
		if(!Yii::$app->request->isPost)
		{
			// 属于文章的图片
			$article['desc_images'] = UploadedFileModel::find()->select('file_id,file_path,file_name,file_type')
				->where(['item_id' => $id, 'belong' => Def::BELONG_ARTICLE, 'store_id' => $article['store_id']])
				->andWhere(['in', 'file_type', explode(',', Def::IMAGE_FILE_TYPE)])
				->orderBy(['file_id' => SORT_ASC])->asArray()->all();
			
			// 属于文章的附件（文件）
			$article['desc_files'] = UploadedFileModel::find()->select('file_id,file_path,file_name,file_type')
				->where(['item_id' => $id, 'belong' => Def::BELONG_ARTICLE, 'store_id' => $article['store_id']])
				->andWhere(['not in', 'file_type', explode(',', Def::IMAGE_FILE_TYPE)])
				->orderBy(['file_id' => SORT_DESC])->asArray()->all();
			
			$this->params['article'] = $article;
			$this->params['parents'] = AcategoryModel::getOptions();
			
			// 编辑器图片批量上传器
			$this->params['build_upload'] = Plugin::getInstance('uploader')->autoBuild(true)->create([
                'belong' 		=> Def::BELONG_ARTICLE,
                'item_id' 		=> $id,
                'upload_url' 	=> Url::toRoute(['upload/add', 'store_id' => $article['store_id']]), // 有可能是编辑店家的文章
				'multiple' 		=> true,
				'archived' 		=> true
			]);
			
			// 所见即所得编辑器
			$this->params['build_editor'] = Plugin::getInstance('editor')->autoBuild(true)->create(['name' => 'description']);
			$this->params['imageJsonArray'] = json_encode(explode(',', Def::IMAGE_FILE_TYPE));

			$this->params['page'] = Page::seo(['title' => Language::get('article_edit')]);
			return $this->render('../article.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['cate_id', 'if_show', 'sort_order']);
			
			$model = new \backend\models\ArticleForm(['article_id' => $id]);
			if(!($article = $model->save($post, true))) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_ok'), ['article/index']);		
		}
	}
	// 允许删除店铺的文章
	public function actionDelete()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		$post->id = explode(',', $post->id);
		foreach($post->id as $id) {
			if(in_array($id, [1,2,3,4]))  continue; // 不能删除系统文章
			if(($model = ArticleModel::findOne($id)) && !$model->delete()) {
				return Message::warning($model->errors);
			}
			UploadedFileModel::deleteFileByQuery(UploadedFileModel::find()->where(['belong' => Def::BELONG_ARTICLE, 'item_id' => $id])->asArray()->all());
		}
		return Message::display(Language::get('drop_ok'));
	}
	
	/* 异步删除编辑器上传的图片 */
	public function actionDeleteimage()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		foreach(explode(',', $post->id) as $id) {
			UploadedFileModel::deleteFileByQuery(UploadedFileModel::find()->where(['belong' => Def::BELONG_ARTICLE, 'file_id' => $id])->asArray()->all());
		}
		return Message::display(Language::get('drop_ok'));
	}
	
	/* 异步修改数据 */
    public function actionEditcol()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id', 'if_show', 'sort_order']);
		if(in_array($post->column, ['title', 'if_show', 'sort_order'])) {
			$model = new \backend\models\ArticleForm(['article_id' => $post->id]);
			$query = ArticleModel::findOne($post->id);
			$query->{$post->column} = $post->value;
			if(!($article = $model->save($query, true))) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_ok'));
		}
    }

	private function getConditions($post, $query = null)
	{
		if($query === null) {
			foreach(array_keys(ArrayHelper::toArray($post)) as $field) {
				if(in_array($field, ['cate_id', 'title', 'articletype'])) {
					return true;
				}
			}
			return false;
		}
		
		if($post->title) {
			$query->andWhere(['like', 'title', $post->title]);
		}
		if($post->cate_id) {
			if(!($allId = AcategoryModel::getDescendantIds($post->cate_id))) {
				$allId = array();
			}
			$query->andWhere(['in', 'a.cate_id', $allId]);
		}
		if($post->articletype) {
			($post->articletype == 1) ? $query->andWhere(['a.store_id' => 0]) : $query->andWhere(['!=', 'a.store_id', 0]);
		}

		return $query;
	}
}
