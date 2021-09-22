<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\widgets\df_floor_2;

use Yii;

use common\widgets\BaseWidget;

/**
 * @Id main.widget.php 2018.9.13 $
 * @author mosir
 */
 
class Df_floor_2Widget extends BaseWidget
{
    var $name = 'df_floor_2';

    public function getData()
    {
        $data = array(
			'model_name'    => $this->options['model_name'],
			'model_color'   => $this->options['model_color'],
            'images'  		=> $this->getImages()
        );
		return $data;
    }
	
    public function parseConfig($input)
    {
		for ($i = 1; $i <= 2; $i++)
        {
			if(($image = $this->upload('ad'.$i.'_image_file'))) {
				$input['ad' . $i . '_image_url'] = $image;
			}
        }	
        return $input;
    }	
	
	public function getImages()
	{
		$images = array();
		for($i = 1; $i <= 2; $i++)
		{
			$images[] = array(
				'ad_image_url' => $this->options['ad'.$i.'_image_url'],
				'ad_link_url'  => $this->options['ad'.$i.'_link_url']
			); 
		}
		return $images;		
	}
}
