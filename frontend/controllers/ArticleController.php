<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\controllers;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

use common\models\ArticleModel;
use common\models\AcategoryModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;
use common\library\Timezone;

/**
 * @Id ArticleController.php 2018.7.20 $
 * @author mosir
 */

class ArticleController extends \common\controllers\BaseMallController
{
	/**
	 * 初始化
	 * @var array $view 当前视图
	 * @var array $params 传递给视图的公共参数
	 */
	public function init()
	{
		parent::init();
		$this->view  = Page::setView('mall');
		$this->params = ArrayHelper::merge($this->params, Page::getAssign('mall'));
	}
	
	public function actionIndex()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['cate_id', 'page', 'pageper']);
		
		if(Yii::$app->request->isAjax)
		{
			// 取得列表数据
			$query = ArticleModel::find()->select('article_id,title,add_time')->where(['if_show' => 1, 'store_id' => 0])->orderBy(['article_id' => SORT_DESC]);
			if($post->cate_id) {
				$allId = AcategoryModel::getDescendantIds($post->cate_id);
				$query->andWhere(['in', 'cate_id', $allId]);
			}
			if($post->keyword) {
				$query->andWhere(['like', 'title', $post->keyword]);
			}
			
			$page = Page::getPage($query->count(), $post->pageper);
			$articlelist = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
			foreach($articlelist as $key => $val) {
				$articlelist[$key]['add_time'] = Timezone::localDate('Y-m-d', $val['add_time']);
			}
	
			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			return array('result' => array_values($articlelist), 'totalPage' => $page->getPageCount());
		}
		else
		{
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.infinite.js');
			$this->params['infiniteParams'] = json_encode(ArrayHelper::toArray($post));
			
			if($post->cate_id) {
				$category = AcategoryModel::find()->select('cate_name')->where(['cate_id' => $post->cate_id, 'if_show' => 1, 'store_id' => 0])->asArray()->one();
			}

			$this->params['category'] = $category;
		
			// 文章分类
        	$acategories = AcategoryModel::find()->select('cate_id,cate_name')->where(['parent_id' => 0, 'if_show' => 1])->asArray()->all();
			foreach($acategories as $key => $val) {
				$acategories[$key]['children'] = AcategoryModel::find()->select('cate_id,cate_name')->where(['parent_id' => $val['cate_id'], 'if_show' => 1])->asArray()->all();
			}
			$this->params['acategories'] = $acategories;
		
			$this->params['page'] = Page::seo(['title' => $category ? $category['cate_name'] : Language::get('new_article')]);
        	return $this->render('../article.index.html', $this->params);
		}
	}
	
	public function actionView()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);
		
		if(!$post->id || !($article = ArticleModel::find()->select('article_id,title,link,description,add_time')->where(['article_id' => $post->id, 'if_show' => 1, 'store_id' => 0])->asArray()->one())) {
			return Message::warning(Language::get('no_such_article'));
		}
		
		// 外链文章跳转
		if ($article['link'] && ($article['link'] != Url::toRoute(['article/view', 'id' => $post->id], true))) {
			return $this->redirect($article['link']);
		}
		$this->params['article'] = $article;

		$this->params['page'] = Page::seo(['title' => $article['title']]);
        return $this->render('../article.view.html', $this->params);
	}
}