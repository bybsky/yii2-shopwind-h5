<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\library;

use yii;
use yii\helpers\Url;

use common\models\StoreModel;
use common\models\IntegralSettingModel;
use common\models\DistributeMerchantModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Def;
use common\library\Plugin;

/**
 * @Id Menu.php 2018.4.1 $
 * @author mosir
 */
 
class Menu
{
	/* 用户中心页面栏目：当前选中的菜单项 */
    public static function curitem($item = null)
    {
		$userRole = array();
		$userMenu = self::getUserMenus();
	
		// 包含关闭状态，是为了查看店铺历史数据
		if(!(StoreModel::find()->where(['and', ['store_id' => Yii::$app->user->id], ['in', 'state', [Def::STORE_OPEN,Def::STORE_CLOSED]]])->exists())) 
		{
			unset($userMenu['im_seller'], $userMenu['promotool']);
			$userRole = 'buyer';
		}
		else
		{
			if(Yii::$app->session->get('userRole') == 'buyer') {
				unset($userMenu['im_seller'], $userMenu['promotool']);
				$userRole = 'buyer';
			}
			else {
				unset($userMenu['im_buyer']);
				$userRole = 'seller';
			}
		}
		Yii::$app->session->set('userRole', $userRole);
		return array('menus' => $userMenu, 'curitem' => $item);
    }
	
    /* 用户中心页面栏目：当前选中的子菜单 */
    public static function curmenu($item = null)
    {
        $userSubmenu = Yii::$app->controller->getUserSubmenu();
        foreach ($userSubmenu as $key => $value) {
            $userSubmenu[$key]['text'] = (isset($value['text']) && $value['text']) ? $value['text'] : Language::get($value['name']);
        }
		return array('submenus' => $userSubmenu, 'curmenu' => $item);
    }
	
	/* 获取用户中心全局菜单列表 */
    public static function getUserMenus()
    {
        $menu = array();
		
		// 我是买家
		$menu['im_buyer'] = array(
			'name'  => 'im_buyer',
			'text'  => Language::get('im_buyer'),
			'submenu'   => array(
				'my_order'  => array(
					'text'  => Language::get('my_order'),
					'sub_text'  => Language::get('view_my_order'),
					'url'   => Url::toRoute(['buyer_order/index']),
					'name'  => 'my_order',
				),
				'my_capital' => array(
					'text' => Language::get('my_capital'),
					'sub_text' => Language::get('view_my_deposit'),
					'url'      => Url::toRoute('deposit/index'),
					'name'     => 'my_capital',
				),
				'refund' => array(
					'text' => Language::get('refund_apply'),
					'url'  => Url::toRoute('refund/index'),
					'name' => 'refund_apply',
				),
				'my_question' =>array(
					'text'  => Language::get('my_question'),
					'url'   => Url::toRoute(['my_question/index']),
					'name'  => 'my_question',
				),
				'my_address'  => array(
					'text'  => Language::get('my_address'),
					'url'   => Url::toRoute('my_address/index'),
					'name'  => 'my_address',
				),
				'my_report'  => array(
					'text'  => Language::get('my_report'),
					'url'   => Url::toRoute('my_report/index'),
					'name'  => 'my_report',
				),
				'connect'  => array(
					'text'  => Language::get('connect_bind'),
					'url'   => Url::toRoute(['connect/index']),
					'name'  => 'connect',
				),
				'my_message'  => array(
					'text'  => Language::get('my_message'),
					'url'   => Url::toRoute('my_message/index'),
					'name'  => 'my_message',
				),
				'distribute' => array(
					'text' => Language::get('distribute_apply'),
					'url' => Url::toRoute(['distribute/apply']),
					'name' => 'distribute_apply',
				)
			),
		);
		
		if(DistributeMerchantModel::find()->where(['userid' => Yii::$app->user->id])->exists()) {
			$menu['im_buyer']['submenu']['distribute'] = array(
					'text' => Language::get('distribute_index'),
					'url' => Url::toRoute(['distribute/index']),
					'name' => 'distribute',
			);
		}
		else
		{
			$menu['im_buyer']['submenu']['distribute'] = array(
					'text' => Language::get('distribute_apply'),
					'url' => Url::toRoute(['distribute/apply']),
					'name' => 'distribute_apply',
			);
		}

        if(Yii::$app->params['store_allow'])
		{
			// 没有拥有店铺，且开放申请，则显示申请开店链接
			$menu['im_buyer']['submenu']['apply_store'] = array(
				'text' => Language::get('apply_store'),
				'url'  => Url::toRoute(['apply/index']),
				'name' => 'apply_store'
			);
		}
		
		// 包含关闭状态，是为了查看店铺历史数据
		if(StoreModel::find()->where(['and', ['store_id' => Yii::$app->user->id], ['in', 'state', [Def::STORE_OPEN,Def::STORE_CLOSED]]])->exists())
		{
			// 指定了要管理的店铺
			$menu['im_seller'] = array(
				'name'  => 'im_seller',
				'text'  => Language::get('im_seller'),
				'submenu'   => array(),
			);
			$menu['im_seller']['submenu']['seller_order'] = array(
					'text'  => Language::get('seller_order'),
					'sub_text' => Language::get('view_all_order'),
					'url'      => Url::toRoute(['seller_order/index']),
					'name'     => 'seller_order',
			);
			$menu['im_seller']['submenu']['my_capital'] = array(
					'text' => Language::get('my_capital'),
					'sub_text' => Language::get('view_my_deposit'),
					'url'      => Url::toRoute('deposit/index'),
					'name'     => 'my_capital',
			);
			
			$menu['im_seller']['submenu']['my_store']  = array(
					'text'  => Language::get('my_store'),
					'url'   => Url::toRoute(['my_store/index']),
					'name'  => 'my_store',
			);
			
			$menu['im_seller']['submenu']['my_goods'] = array(
					'text'  => Language::get('my_goods'),
					'url'   => Url::toRoute(['my_goods/index']),
					'name'  => 'my_goods',
			); 
			$menu['im_seller']['submenu']['refund_receive']  = array(
				'text' => Language::get('refund_receive'),
				'url'  => Url::toRoute(['refund/receive']),
				'name' => 'refund_receive',
			);
			$menu['im_seller']['submenu']['my_delivery'] = array(
					'text'  => Language::get('my_delivery'),
					'url'   => Url::toRoute(['my_delivery/index']),
					'name'  => 'my_delivery',
			); 
			
			$menu['im_seller']['submenu']['my_qa'] = array(
					'text'  => Language::get('my_qa'),
					'url'   => Url::toRoute(['my_qa/index']),
					'name'  => 'my_qa',
			);    
			$menu['im_seller']['submenu']['my_comment'] = array(
					'text'  => Language::get('my_comment'),
					'url'   => Url::toRoute(['my_comment/index']),
					'name'  => 'my_comment',
			);
			$menu['im_seller']['submenu']['seller_coupon']  = array(
					'text'  => Language::get('seller_coupon'),
					'url'   => Url::toRoute(['seller_coupon/index']),
					'name'  => 'seller_coupon',
			);

			
			$menu['im_seller']['submenu']['map'] = array(
					'text'  => Language::get('store_map'),
					'url'   => Url::toRoute('my_store/map'),
					'name'  => 'map',
			);
			$menu['im_seller']['submenu']['view_store'] = array(
					'text'  => Language::get('view_store'),
					'url'   => Url::toRoute(['store/index', 'id' => Yii::$app->user->id]),
					'name'  => 'view_store',
			);
			$menu['im_seller']['submenu']['buyer_admin'] = array(
					'text'  => Language::get('buyer_admin'),
					'url'   => Url::toRoute('buyer/index'),
					'name'  => 'buyer_admin',
			);
			
			// 营销中心
			$menu['promotool'] = array(
				'name'  => 'promotool',
				'text'  => Language::get('promotool'),
				'url'	=> Url::toRoute('appmarket/index'),
				'sub_text' => Language::get('view_allapp'),
				'submenu'  => array(),
			);
			$menu['promotool']['submenu']['seller_limitbuy'] = array(
					'text'  => Language::get('seller_limitbuy'),
					'url'   => Url::toRoute(['seller_limitbuy/index']),
					'name'  => 'seller_limitbuy',
			);
			$menu['promotool']['submenu']['seller_meal'] = array(
					'text'  => Language::get('seller_meal'),
					'url'   => Url::toRoute(['seller_meal/index']),
					'name'  => 'seller_meal',
			);
			$menu['promotool']['submenu']['seller_fullfree'] = array(
					'text'  => Language::get('seller_fullfree'),
					'url'   => Url::toRoute(['seller_fullfree/index']),
					'name'  => 'seller_fullfree',
			);
			$menu['promotool']['submenu']['seller_fullprefer'] = array(
					'text'  => Language::get('seller_fullprefer'),
					'url'   => Url::toRoute(['seller_fullprefer/index']),
					'name'  => 'seller_fullprefer',
			);
			$menu['promotool']['submenu']['seller_exclusive'] = array(
					'text'  => Language::get('seller_exclusive'),
					'url'   => Url::toRoute(['seller_exclusive/index']),
					'name'  => 'seller_exclusive',
			);
			$menu['promotool']['submenu']['distribute'] = array(
					'text' => Language::get('distribute'),
					'url' => Url::toRoute(['distribute/index']),
					'name' => 'distribute',
			);
		}

        return $menu;
    }
}