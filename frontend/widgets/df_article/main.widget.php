<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\widgets\df_article;

use Yii;

use common\models\AcategoryModel;
use common\models\ArticleModel;

use common\widgets\BaseWidget;

/**
 * @Id main.widget.php 2018.9.13 $
 * @author mosir
 */
 
class Df_articleWidget extends BaseWidget
{
    var $name = 'df_article';

    public function getData()
    {
        $cache = Yii::$app->cache;
        $key = $this->getCacheId();
		$data = $cache->get($key);
        if($data === false)
        {
			$num = intval($this->options['num']) > 0 ? intval($this->options['num']) : 5;

			$query = ArticleModel::find()->where(['if_show' => 1, 'store_id' => 0]);
			if(($allId = AcategoryModel::getDescendantIds(intval($this->options['cate_id'])))) {
				$query->andWhere(['in', 'cate_id', $allId]);
			}
			$articles = $query->orderBy(['sort_order' => SORT_ASC, 'add_time' => SORT_DESC])->limit($num)->asArray()->all();
			
			$data = array(
				'model_id' 		=> mt_rand(),
				'model_name' 	=> $this->options['model_name'],
				'ad_image_url'  => $this->options['ad_image_url'],
				'articles' 		=> array_chunk($articles, 2)
			);
            $cache->set($key, $data, $this->ttl);
        }
		return $data;
    }

    public function parseConfig($input)
    {
        if (($image = $this->upload('ad_image_file'))) {
            $input['ad_image_url'] = $image;
        }
        return $input;
    }

	public function getConfigDatasrc()
    {
		// 取得二级文章分类
		$this->params['acategories'] = AcategoryModel::getOptions(0, -1, null, 2);	
    }

}
