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

use common\models\StoreModel;

/**
 * @Id SellerController.php 2018.4.1 $
 * @author mosir
 */

class SellerController extends \common\controllers\BaseUserController
{
    public function actionIndex()
    {
		if(StoreModel::find()->where(['store_id' => $this->visitor['store_id']])->exists()) {
			Yii::$app->session->set('userRole', 'seller');
			return $this->redirect(['user/index']);
		}
		return $this->redirect(['apply/index']);	
	}
}