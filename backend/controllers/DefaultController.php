<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace backend\controllers;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

use common\models\UserModel;
use common\models\UserEnterModel;
use common\models\StoreModel;
use common\models\GoodsModel;
use common\models\OrderModel;
use common\models\RegionModel;
use common\models\GcategoryModel;
use common\models\ScategoryModel;
use common\models\RefundModel;
use common\models\ReportModel;
use common\models\PluginModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Timezone;
use common\library\Page;
use common\library\Def;
use backend\library\Menu;

/**
 * @Id DefaultController.php 2018.7.25 $
 * @author mosir
 */

class DefaultController extends \common\controllers\BaseAdminController
{
	/**
	 * 初始化
	 */
	public function init()
	{
		parent::init();
	}
	
	/**
	 * 排除特定Action外，其他需要登录后访问
	 * @param $action
	 * @var array $extraAction
	 */
	public function beforeAction($action)
    {
		$this->extraAction = ['captcha', 'checkCaptcha'];
		return parent::beforeAction($action);
    }
	
	public function actionIndex()
	{
		$this->params['back_nav'] = Menu::getMenus();
		
		$this->params['page'] = Page::seo(['title' => Language::get('admin_backend')]);
        return $this->render('../index.html', $this->params);
	}
	public function actionWelcome()
	{
		$this->params['sys_info'] = $this->getSysInfo();
		$this->params['stats'] = $this->getStatistics();
		$this->params['data_of_week'] = $this->getDataOfWeek();
		$this->params['loginLogs'] = $this->getUserEnter(5);
		$this->params['remind_info'] = $this->getRemindInfo();
		
		$this->params['_foot_tags'] = Resource::import('jquery.plugins/SuperSlide/jquery.SuperSlide.2.1.2.js,echarts/echarts.min.js,echarts/macarons.js');
			
		$this->params['page'] = Page::seo(['title' => Language::get('admin_backend')]);
        return $this->render('../welcome.html', $this->params);
	}
	
	/* 实时查询访客地区 */
	public function actionGetipinfo()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);
		if(empty($post->id) || !($query = UserEnterModel::find()->select('id,ip')->where(['id' => $post->id])->one())){
			return Message::warning(Language::get('no_such_item'));
		}
		
		if(!($result = RegionModel::getAddressByIp($query->ip))) {
			return Message::warning(Language::get('get_region_fail'));
		}
		if(!($address = $result['city'])) {
			return Message::warning(Language::get('get_region_fail'));
		}
		$query->address = $address;
		$query->save();
		
		return Message::result($address);	
	}	
	
	/* 一周数据 */
	public function getDataOfWeek()
    {
        $lastWeek = Timezone::gmtime() - 7 * 24 * 3600;
        
        return array(
            'new_user_qty'  => UserModel::find()->where(['>', 'create_time', $lastWeek])->count(),
            'new_store_qty' => StoreModel::find()->where(['and', ['state' => Def::STORE_OPEN], ['>', 'add_time', $lastWeek]])->count(), 
            'new_apply_qty' => StoreModel::find()->where(['and', ['state' => Def::STORE_APPLYING], ['>', 'add_time', $lastWeek]])->count(),
            'new_goods_qty' => GoodsModel::find()->where(['and', ['if_show' => 1, 'closed' => 0], ['>', 'add_time', $lastWeek]])->count(),
            'new_order_qty' => OrderModel::find()->where(['and', ['!=', 'status', Def::ORDER_CANCELED], ['>', 'add_time', $lastWeek]])->count(),
			'new_pinglun' 	=> OrderModel::find()->where(['and', ['evaluation_status' => 1], ['>', 'evaluation_time', $lastWeek]])->count()
        );
    }
	/* 基础统计 */
	public function getStatistics()
    {
        return array(
            'user_qty'  => UserModel::find()->count(),
            'store_qty' => StoreModel::find()->where(['state' => 1])->count(),
            'apply_qty' => StoreModel::find()->where(['state' => 0])->count(),
            'goods_qty' => GoodsModel::find()->where(['if_show' => 1, 'closed' => 0])->count(),
            'order_qty' => OrderModel::find()->where(['!=', 'status', Def:: ORDER_CANCELED])->count(),
            'order_amount' => OrderModel::find()->where(['!=', 'status', Def::ORDER_CANCELED])->sum('order_amount'),
            'admin_email' => UserModel::find()->select('email')->where(['userid' => 1])->scalar()
        );
    }
	
	/* 系统信息 */
	private function getSysInfo()
    {
        $file = Yii::getAlias('@frontend') . '/web/data/install.lock';
        return array(
            'server_os'     => PHP_OS,
            'web_server'    => $_SERVER['SERVER_SOFTWARE'],
            'php_version'   => PHP_VERSION, 
            'mysql_version' => Yii::$app->db->getServerVersion(),
			'version'		=> Basewind::getVersion(),
            'install_date'  => file_exists($file) ? date('Y-m-d', fileatime($file)) : date('Y-m-d', time()),
        );
    }
	
	/* 登录记录 */
	public function getUserEnter($limit = 5)
	{
		return UserEnterModel::find()->where(['scene' => 'backend'])->limit($limit)->orderBy(['add_time' => SORT_DESC])->asArray()->all();
	}
	
	/* 取得提醒信息 */
    public function getRemindInfo()
    {
        $remind_info = array();
  
        // 地区
		if(!RegionModel::find()->where(['parent_id' => 0, 'if_show' => 1])->exists()) {
			$remind_info[] = sprintf(Language::get('reminds.region'), Url::toRoute('region/index'));
		}
        // 支付方式
		if(!PluginModel::find()->where(['instance' => 'payment', 'enabled' => 1])->exists()) {
			$remind_info[] = sprintf(Language::get('reminds.payment'), Url::toRoute(['plugin/index', 'instance' => 'payment']));
		}
        // 商品分类
		if(!GcategoryModel::find()->where(['parent_id' => 0, 'store_id' => 0, 'if_show' => 1])->exists()) {
			$remind_info[] = sprintf(Language::get('reminds.gcategory'), Url::toRoute('gcategory'));
		}
        // 店铺分类
		if(!ScategoryModel::find()->where(['parent_id' => 0, 'if_show' => 1])->exists()) {
			$remind_info[] = Language::get('reminds.scategory');
		}
		
		// 待处理的举报
		if(($count = ReportModel::find()->where(['status' => 0])->count()) > 0) {
			$remind_info[] = sprintf(Language::get('reminds.report'), $count, Url::toRoute('report/index'));
		}
		
		//要求平台接入的退款申请
		if(($count = RefundModel::find()->where(['intervene' => 1, 'status' => 'WAIT_SELLER_AGREE'])->count()) > 0) {
			$remind_info[] = sprintf(Language::get('reminds.refund'), $count, Url::toRoute('refund/index'));
		}
		// 待审核的店铺
		if(($count = StoreModel::find()->where(['state' => Def::STORE_APPLYING])->count()) > 0) {
			$remind_info[] = sprintf(Language::get('reminds.apply'), $count, Url::toRoute(['store/index', 'state' => 'applying']));
		}
        return $remind_info;
    }
}
