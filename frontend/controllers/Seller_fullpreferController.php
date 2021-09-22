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

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;
use common\library\Promotool;

/**
 * @Id Seller_fullpreferController.php 2018.11.15 $
 * @author luckey
 */

class Seller_fullpreferController extends \common\controllers\BaseSellerController
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
		if(!Yii::$app->request->isPost)
		{
			$this->params['fullprefer'] = Promotool::getInstance('fullprefer')->build(['store_id' => $this->visitor['store_id']])->getInfo();
			$this->params['appAvailable'] = Promotool::getInstance('fullprefer')->build(['store_id' => $this->visitor['store_id']])->checkAvailable(true, true);
			
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.form.js');

			$this->params['page'] = Page::seo(['title' => Language::get('fullprefer_index')]);
        	return $this->render('../seller_fullprefer.index.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post('fullprefer'), true);
			$model = new \frontend\models\Seller_fullpreferForm(['store_id' => $this->visitor['store_id']]);
			if(!$model->save($post, true)) {
				return Message::warning($model->errors);
			}			
			return Message::display(Language::get('handle_ok'));
		}		
	}
}