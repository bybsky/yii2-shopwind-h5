<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace mobile\models;

use Yii;
use yii\base\Model; 

use common\models\LimitbuyModel;

use common\library\Timezone;
use common\library\Page;
use common\library\Promotool;

/**
 * @Id StoreLimitbuyForm.php 2018.10.15 $
 * @author mosir
 */
class StoreLimitbuyForm extends Model
{
	public $store_id = 0;
	public $errors = null;

	public function formData($post = null, $pageper = 4) 
	{
		$query = LimitbuyModel::find()->alias('lb')->select('lb.goods_id,lb.image,lb.end_time,lb.title,g.default_image,g.price,g.default_spec,g.goods_name')->joinWith('goods g', false)->where(['lb.store_id' => $this->store_id])->andWhere(['<=', 'start_time', Timezone::gmtime()])->andWhere(['>=', 'end_time', Timezone::gmtime()])->orderBy(['id' => SORT_DESC]);
	
		$page = Page::getPage($query->count(), $pageper);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		
		$promotool = Promotool::getInstance()->build();
		foreach($list as $key => $value)
		{
			$result = $promotool->getItemProInfo($value['goods_id'], $value['default_spec']);
			if($result !== false) {
				$list[$key]['pro_price'] = $result['price'];
				$list[$key]['lefttime'] = $result['lefttime'];
			} else $list[$key]['pro_price'] = $result['lefttime'];
		}
		return array($list, $page);
	}
}
