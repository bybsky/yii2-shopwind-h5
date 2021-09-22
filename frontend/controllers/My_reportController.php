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

use common\models\ReportModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;
use common\library\Timezone;

/**
 * @Id My_reportController.php 2018.3.20 $
 * @author mosir
 */

class My_reportController extends \common\controllers\BaseUserController
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
		
		if(Yii::$app->request->isAjax)
		{
			$query = ReportModel::find()->alias('r')->select('r.*,g.goods_name,g.default_image,s.store_name')->joinWith('goods g', false)->joinWith('store s', false)->where(['userid' => Yii::$app->user->id])->orderBy(['id' => SORT_DESC]);
			$page = Page::getPage($query->count(), $post->pageper);
			$reports = $query->offset($page->offset)->limit($page->limit)->asArray()->all();

			foreach($reports as $key => $val) {
				$reports[$key]['add_time'] = TimeZone::localDate('Y-m-d H:i:s', $val['add_time']);
			}
			
			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			return array('result' => array_values($reports), 'totalPage' => $page->getPageCount());
		}
		else
		{
			$this->params['_foot_tags'] = Resource::import([
				'script' => 'jquery.plugins/jquery.form.js,weui/js/jquery-weui.min.js,jquery.plugins/jquery.infinite.js',
				'style'  => 'weui/lib/weui.min.css,weui/css/jquery-weui.min.css'
			]);
			
			$this->params['infiniteParams'] = json_encode(ArrayHelper::toArray($post));
			
			$this->params['page'] = Page::seo(['title' => Language::get('my_report')]);
			return $this->render('../my_report.index.html', $this->params);
		}
    }
	
	public function actionDelete()
    {
        $post = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);

		if(!$post->id) {
			return Message::warning(Language::get('no_such_item'));
		}
		
		if(!ReportModel::deleteAll(['id' => $post->id, 'userid' => Yii::$app->user->id])) {
			return Message::warning(Language::get('drop_fail'));
		}
		
        return Message::display(Language::get('drop_ok'));
    }

}