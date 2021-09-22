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

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Page;

/**
 * @Id ThemeController.php 2018.9.5 $
 * @author mosir
 */

class ThemeController extends \common\controllers\BaseAdminController
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
		$post = Basewind::trimAll(Yii::$app->request->get(), true);

		$list = array();
		$templates = Page::listTemplate('mall');

		$this->params['templates'] = $templates;
		$this->params['cur_template_name'] = Yii::$app->params['template_name'];
		
		$this->params['page'] = Page::seo(['title' => Language::get('template_list')]);
		return $this->render('../theme.index.html', $this->params);
	}
	
	public function actionSet()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
	
        if (!$post->template_name) {
			return Message::warning(Language::get('no_such_template'));
        }
		
		$data = array('template_name' => $post->template_name);
		
        $model = new \backend\models\SettingForm();
		if(!$model->save($data, true)) {
			return Message::warning($model->errors);
		}
		return Message::display(Language::get('set_theme_successed'), ['theme/index']);
    }
}
