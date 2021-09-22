<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\widgets\df_infinite;

use Yii;

use common\widgets\BaseWidget;

/**
 * @Id main.widget.php 2018.9.13 $
 * @author mosir
 */

class Df_infiniteWidget extends BaseWidget
{
    var $name = 'df_infinite';

    public function getData()
    {
       $data = array(
	   		'model_id'		=> mt_rand(),
			'model_name'    => $this->options['model_name'],
			'model_color'  	=> $this->options['model_color'],
			'recom_id' 		=> $this->options['recom_id'],
			'cate_id' 		=> $this->options['cate_id'],
			'sort_by' 		=> $this->options['sort_by'],
			'maxshow' 		=> intval($this->options['maxshow']) ? intval($this->options['maxshow']) : 100
        );

		return $data;
    }
	
    public function parseConfig($input)
    {
        return $input;
    }
	
 	public function getConfigDataSrc()
    {
		// 取得推荐类型
		$this->params['recommends'] = $this->getRecommendOptions(true);
		
        // 取得一级商品分类
		$this->params['gcategories'] = $this->getGcategoryOptions(0, 0);
    }
}
