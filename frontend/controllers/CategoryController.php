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

use common\models\GcategoryModel;
use common\models\ScategoryModel;
use common\models\UploadedFileModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Page;
use common\library\Def;

/**
 * @Id CategoryController.php 2018.9.14 $
 * @author mosir
 */

class CategoryController extends \common\controllers\BaseMallController
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
		// 头部商品分类（页面共用）
		$gcategories = GcategoryModel::getTree(0, true, 3);
		
		foreach($gcategories as $key => $val) {
			$gcategories[$key]['gads'] = UploadedFileModel::find()->select('file_path,link_url')->where(['store_id' => 0, 'item_id' => $val['id'], 'belong' => Def::BELONG_GCATEGORY_AD])->orderBy(['add_time' => SORT_DESC])->asArray()->all();
			foreach($val['children'] as $k => $v) {
				foreach($v['children'] as $k1 => $v1) {
					$gcategories[$key]['children'][$k]['children'][$k1]['image'] = GcategoryModel::find()->select('image')->where(['cate_id' => $v1['id']])->scalar();
				}
			}
		}
		$this->params['gcategories'] = $gcategories;
	
		$this->params['page'] = Page::seo(['title' => Language::get('category_goods')]);
		return $this->render('../category.goods.html', $this->params);
	}
	public function actionStore()
	{
		// 店铺分类
		$this->params['scategories'] = ScategoryModel::getTree();
		
		$this->params['page'] = Page::seo(['title' => Language::get('category_store')]);
		return $this->render('../category.store.html', $this->params);
	}
}