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

use common\models\GoodsQaModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;

/**
 * @Id My_qaController.php 2018.4.17 $
 * @author mosir
 */

class My_qaController extends \common\controllers\BaseSellerController
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
		$this->params = ArrayHelper::merge($this->params, Page::getAssign('user'));
	}

    public function actionIndex()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['page', 'pageper']);
		$curmenu = in_array($post->type, ['reply', 'replied']) ? $post->type.'_qa' : 'all_qa';
		
		if(Yii::$app->request->isAjax)
		{
			$model = new \frontend\models\My_qaForm(['store_id' => $this->visitor['store_id']]);
			list($questions, $page) = $model->formData($post, $post->pageper);

			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			return array('result' => array_values($questions), 'totalPage' => $page->getPageCount());
		}
		else
		{
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.infinite.js,jquery.plugins/jquery.form.js');
			$this->params['infiniteParams'] = json_encode(ArrayHelper::toArray($post));
		
			$this->params['page'] = Page::seo(['title' => Language::get('my_qa')]);
        	return $this->render('../my_qa.index.html', $this->params);
		}
	}
	
	/* 回复买家咨询 OR 编辑回复 */
	public function actionReply()
	{
		if(!Yii::$app->request->isPost)
		{
			$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);
			
			$question = GoodsQaModel::find()->select('ques_id,question_content,reply_content')->where(['ques_id' => $post->id, 'store_id' => $this->visitor['store_id']])->asArray()->one();
			$this->params['question'] = $question;
			
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.form.js');
		
			$this->params['page'] = Page::seo(['title' => Language::get('my_qa')]);
        	return $this->render('../my_qa.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['id']);
			
			$model = new \frontend\models\My_qaReplyForm(['store_id' => $this->visitor['store_id']]);
			if(!$model->save($post, true)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('reply_successful'), ['my_qa/index', 'type' => 'replied']);
		}
	}
	
	/* 删除咨询 */
	public function actionDelete()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		
		if(!$post->id || !is_array(($allId = explode(',', $post->id)))) {
			return Message::warning(Language::get('no_qa_to_drop'));
		}
		
		if(!GoodsQaModel::deleteAll(['and', ['in', 'ques_id', $allId], ['store_id' => $this->visitor['store_id']]])) {
			return Message::warning(Language::get('drop_failed'));
		}
		return Message::display(Language::get('drop_successful'));
    }
}