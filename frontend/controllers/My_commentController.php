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

use common\models\OrderGoodsModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Timezone;
use common\library\Page;

/**
 * @Id My_commentController.php 2018.5.15 $
 * @author mosir
 */

class My_commentController extends \common\controllers\BaseSellerController
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
		$curmenu = in_array($post->type, ['reply', 'replied']) ? $post->type.'_comment' : 'all_comment';
		
		if(Yii::$app->request->isAjax)
		{
			$model = new \frontend\models\My_commentForm(['store_id' => $this->visitor['store_id']]);
			list($list, $page) = $model->formData($post, $post->pageper);

			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			return array('result' => array_values($list), 'totalPage' => $page->getPageCount());
		}
		else
		{
			$this->params['_foot_tags'] = Resource::import([
				'script' => 'jquery.plugins/jquery.form.js,jquery.ui/jquery.ui.js,jquery.ui/i18n/' . Yii::$app->language . '.js, dialog/dialog.js,jquery.plugins/jquery.infinite.js',
				'style' => 'jquery.ui/themes/smoothness/jquery.ui.css,dialog/dialog.css'
			]);
			$this->params['infiniteParams'] = json_encode(ArrayHelper::toArray($post));

			$this->params['page'] = Page::seo(['title' => Language::get($curmenu)]);
			return $this->render('../my_comment.index.html', $this->params);
		}
	}
	
	public function actionReply()
    {
        if (!Yii::$app->request->isPost)
        {
			$post = Basewind::trimAll(Yii::$app->request->get(), true, ['rec_id']);
			
			$my_comment = OrderGoodsModel::find()->alias('og')->select('rec_id,comment,reply_comment')->joinWith('order o', false)->where(['rec_id' => $post->rec_id, 'seller_id' => $this->visitor['store_id']])->asArray()->one();
			
			if(!$my_comment || $my_comment['reply_comment'] != '') {
				return Message::warning(Language::get('already_replied'));
			}
			$this->params['my_comment'] = $my_comment;
		
			$this->params['page'] = Page::seo(['title' => Language::get('reply')]);
        	return $this->render('../my_comment.form.html', $this->params);
        }
        else
        {
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['rec_id']);
			
			$my_comment = OrderGoodsModel::find()->alias('og')->select('rec_id,comment,reply_comment')->joinWith('order o', false)->where(['rec_id' => $post->rec_id, 'seller_id' => $this->visitor['store_id']])->asArray()->one();
			
			if(!$my_comment || $my_comment['reply_comment'] != '') {
				return Message::warning(Language::get('already_replied'));
			}

            if (!$post->content) {
                return Message::warning(Language::get('content_not_null'));
            }
			if(!OrderGoodsModel::updateAll(['reply_comment' => $post->content, 'reply_time' => Timezone::gmtime()], ['rec_id' => $post->rec_id])) {
				 return Message::warning(Language::get('reply_failed'));
			}
			return Message::display(Language::get('reply_successful'));	
        }
    }
}