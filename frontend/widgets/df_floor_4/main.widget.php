<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\widgets\df_floor_4;

use Yii;

use common\widgets\BaseWidget;

/**
 * @Id main.widget.php 2018.9.13 $
 * @author mosir
 */

class Df_floor_4Widget extends BaseWidget
{
    var $name = 'df_floor_4';

    public function getData()
    {
        $data = array(
			'model_name'    => $this->options['model_name'],
			'model_color'   => $this->options['model_color'],
            'top_images'  	=> $this->getTopImages(),
			'bottom_images' => $this->getBottomImages()
        );
		return $data;
    }

    public function parseConfig($input)
    {
		for ($i = 1; $i <= 6; $i++)
        {
			if(($image = $this->upload('ad'.$i.'_image_file'))) {
				$input['ad' . $i . '_image_url'] = $image;
			}
        }	
        return $input;
    }
	
	public function getTopImages()
	{
		$images = array();
		for($i = 1; $i<= 4; $i++)
		{
			$images[] = array(
				'ad_image_url' => $this->options['ad'.$i.'_image_url'],
				'ad_link_url'  => $this->options['ad'.$i.'_link_url']
			); 
		}
		return $images;		
	}
	
	public function getBottomImages()
	{
		$images = array();
		for($i = 5; $i <= 6; $i++)
		{
			$images[] = array(
				'ad_image_url' => $this->options['ad'.$i.'_image_url'],
				'ad_link_url'  => $this->options['ad'.$i.'_link_url']
			); 
		}
		return $images;		
	}
}
