<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace backend\models;

use Yii;
use yii\base\Model; 
use yii\web\UploadedFile;

use common\library\Timezone;
use common\library\Def;
use common\library\Plugin;

/**
 * @Id UploadForm.php 2018.5.11 $
 * @author mosir
 */
class UploadForm extends Model
{
	public $file;
	public $allowed_type;
	public $allowed_size;
	
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
   			[['file'], 'file', 'skipOnEmpty' => false, 'extensions' => $this->allowed_type, 'maxSize' => $this->allowed_size, 'checkExtensionByMimeType' => false]
  		];
    }
	
	/**
	 * 上传文件
	 * 如果安装了OSS上传插件，则同时上传到OSS
	 */
	public function upload($path = '', $baseName = false, $scheme = '')
	{
		if($baseName === false) {
			$baseName = $this->file->baseName;
		}

		// 上传文件的物理路径
		$savePath = $path . '/' .  $baseName . '.' . $this->file->extension;
		
		// 上传文件的url访问地址
		$saveUrl = str_replace(Def::fileSavePath() . '/', '', $savePath);

		// 先上传到本地
		if(!$this->file->saveAs($savePath)) {
			return false;
		}
		
		// 上传到OSS云存储
		if(($oss = Plugin::getInstance('oss')->autoBuild())) 
		{
			// 包含路径层级，如：data/files/...
			$fileName = $saveUrl;
			if(!($absoluteUrl = $oss->upload($fileName, $savePath))) {
				return false;
			}
			$saveUrl = $absoluteUrl;
		}
		
		return $saveUrl;
	}
	public function filename()
	{
		return Timezone::localDate('YmdHis', Timezone::gmtime()) . mt_rand(100,999);
	}
	public function getInstance($file, $multiple = false)
	{
		if($multiple == true) {
			return UploadedFile::getInstancesByName($file);
		}
		return UploadedFile::getInstanceByName($file);
	}
}
