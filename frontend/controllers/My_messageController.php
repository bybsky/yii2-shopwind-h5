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

use common\models\MessageModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;

/**
 * @Id My_messageController.php 2018.5.28 $
 * @author mosir
 */

class My_messageController extends \common\controllers\BaseUserController
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
		$curmenu = isset($post->folder) ? $post->folder : 'newpm';

		if(Yii::$app->request->isAjax)
		{
			// 取得列表数据	
			$model = new \frontend\models\My_messageForm();
			list($recordlist, $page) = $model->formData($post, $post->pageper);

			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			return array('result' => array_values($recordlist), 'totalPage' => $page->getPageCount());
		}
		else
		{
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.infinite.js');
			$this->params['infiniteParams'] = json_encode(ArrayHelper::toArray($post));
			
			$this->params['page'] = Page::seo(['title' => Language::get($curmenu)]);
			return $this->render('../my_message.index.html', $this->params);
		}	
	}
	
	public function actionSend()
    {		
		$get = Basewind::trimAll(Yii::$app->request->get(), true);
		
        if (!Yii::$app->request->isPost)
		{
			$model = new \frontend\models\My_messageSendForm();
			$this->params['friends'] = $model->getFriends(100);
			$this->params['to_username'] = $model->getUsersFromId($get);
			
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.form.js');
		
			$this->params['page'] = Page::seo(['title' => Language::get('message_send')]);
			return $this->render('../my_message.send.html', $this->params);
        }
        else
        {
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			
			$model = new \frontend\models\My_messageSendForm();
            if(!$model->send($post, true, 0)) {
				return Message::warning($model->errors);
			}
            return Message::display(Language::get('send_message_successed'), ['my_message/index', 'folder' => 'privatepm']);
        }
    }
	
	public function actionView()
	{
		$get = Basewind::trimAll(Yii::$app->request->get(), true, ['msg_id']);
		
		$model = new \frontend\models\My_messageViewForm();
		if(!($message = $model->formData($get, true))) {
			return Message::warning($model->errors);
		}
		
		if(!Yii::$app->request->isPost)
		{
			if(in_array($message['to_id'], [0, Yii::$app->user->id])) {
				MessageModel::updateAll(['new' => 0], ['msg_id' => $get->msg_id]);
			}
			$this->params['message'] = $message;
			
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.form.js');
		
			$this->params['page'] = Page::seo(['title' => Language::get('message_view')]);
			return $this->render('../my_message.view.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			
			 if(!$model->send($post, $get, $message)) {
				return Message::warning($model->errors);
			}
            return Message::display(Language::get('send_message_successed'));
		}
	}
	
	public function actionDelete()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['msg_id']);
			
		$model = new \frontend\models\My_messageDeleteForm();
		if(!$model->delete($post, true)) {
			return Message::warning($model->errors);
		}
        return Message::display(Language::get('drop_message_successed'), ['my_message/index', 'folder' => 'privatepm']);
    }
}