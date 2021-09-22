<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\widgets\df_slide;

use Yii;

use common\widgets\BaseWidget;

/**
 * @Id main.widget.php 2018.9.13 $
 * @author mosir
 */
 
class Df_slideWidget extends BaseWidget
{
    var $name = 'df_slide';

    public function getData()
    {
        return array(
			'model_id' => mt_rand(),
			'images'   => $this->options,
		);
    }

    public function parseConfig($input)
    {
        $result = array();
        $num    = isset($input['ad_link_url']) ? count($input['ad_link_url']) : 0;
        if ($num > 0)
        {
            for ($i = 0; $i < $num; $i++)
            {
				if (($image = $this->upload("ad_image_file[$i]"))) {
            		$input['ad_image_url'][$i] = $image;
				}
				
				if(!empty($input['ad_image_url'][$i])) {
                    $result[] = array(
                        'ad_image_url' => $input['ad_image_url'][$i],
                        'ad_link_url'  => $input['ad_link_url'][$i]
                    );
                }
            }
        }
        return $result;
    }   
}