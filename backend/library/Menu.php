<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace backend\library;

use yii;
use yii\helpers\Url;

use common\library\Language;

/**
 * @Id Menu.php 2018.7.26 $
 * @author mosir
 */
 
class Menu
{
	/* 获取全局菜单列表 */
    public static function getMenus()
    {
        $menu = array(
			'dashboard' => array(
				'text'      => Language::get('dashboard'),
				'subtext'   => Language::get('offen_used'),
				'default'   => 'welcome',
				'children'  => array(
					'welcome'   => array(
						'text'  => Language::get('welcome_page'),
						'url'   => Url::toRoute('default/welcome'),
						'ico'   => 'icon-huanyingye'
					),
					'base_setting'  => array(
						'parent'=> 'setting',
						'text'  => Language::get('base_setting'),
						'url'   => Url::toRoute('setting/index'),
						'ico'   => 'icon-wangzhanshezhi'
					),
					'user_manage' => array(
						'text'  => Language::get('user_manage'),
						'parent'=> 'user',
						'url'   => Url::toRoute('user/index'),
						'ico'   => 'icon-huiyuan'
					),
					'store_manage'  => array(
						'text'  => Language::get('store_manage'),
						'parent'=> 'store',
						'url'   => Url::toRoute('store/index'),
						'ico'   => 'icon-dianpu1'
					),
					'goods_manage'  => array(
						'text'  => Language::get('goods_manage'),
						'parent'=> 'goods',
						'url'   => Url::toRoute('goods/index'),
						'ico'   => 'icon-shangpin'
					),
					'order_manage' => array(
						'text'  => Language::get('order_manage'),
						'parent'=> 'trade',
						'url'   => Url::toRoute('order/index'),
						'ico'   => 'icon-order'
					)
				)
			),
			// 设置
			'setting'   => array(
				'text'      => Language::get('setting'),
				'default'   => 'base_setting',
				'children'  => array(
					'base_setting'  => array(
						'text'  => Language::get('base_setting'),
						'url'   => Url::toRoute('setting/index'),
						'ico'   => 'icon-website_setup',
						'priv'  => ['key' => 'setting|all', 'label' => Language::get('setting')]
					),
					'region' => array(
						'text'  => Language::get('region'),
						'url'   => Url::toRoute('region/index'),
						'ico'   => 'icon-diqu',
						'priv'  => ['key' => 'region|all']
					),
					'cache' => array(
						'text' => Language::get('cache_server'),
						'url' => Url::toRoute('cache/redis'),
						'ico' => 'icon-cache',
						'priv' => ['key' => 'cache|all']
					)
				)
			),
			// 商品
			'goods' => array(
				'text'      => Language::get('goods'),
				'default'   => 'goods_manage',
				'children'  => array(
					'goods_manage' => array(
						'text'  => Language::get('goods_manage'),
						'url'   => Url::toRoute('goods/index'),
						'ico'   => 'icon-shangpin',
						'priv' => ['key' => 'goods|all', 'depends' => 'upload|all']
					),
					'gcategory' => array(
						'text'  => Language::get('gcategory'),
						'url'   => Url::toRoute('gcategory/index'),
						'ico'   => 'icon-leimupinleifenleileibie',
						'priv' => ['key' => 'gcategory|all']
					),
					'brand' => array(
						'text'  => Language::get('brand'),
						'url'   => Url::toRoute('brand/index'),
						'ico'   => 'icon-pinpai',
						'priv'  => ['key' => 'brand|all']
					),
					'prop_manage' => array(
					   'text' => Language::get('prop_manage'),
					   'url'  => Url::toRoute('goodsprop/index'),
					   'ico'  => 'icon-shuxing',
					   'priv' => ['key' => 'goodsprop|all'] 
					),		
					'recommend_manage' => array(
						'text'  => Language::get('recommend_manage'),
						'url'   => Url::toRoute('recommend/index'),
						'ico'   => 'icon-tuijian',
						'priv'  => ['key' => 'recommend|all']
					),
					'report_manage' => array(
						'text'  => Language::get('report_manage'),
						'url'   => Url::toRoute('report/index'),
						'ico'   => 'icon-jubao',
						'priv'  => ['key' => 'report|all'] 
					)
				)
			),
			// 店铺
			'store'     => array(
				'text'      => Language::get('store'),
				'default'   => 'store_manage',
				'children'  => array(
					'store_manage'  => array(
						'text'  => Language::get('store_manage'),
						'url'   => Url::toRoute('store/index'),
						'ico'   => 'icon-dianpu1',
						'priv'  => ['key' => 'store|all', 'depends' => 'mlselection|all']
					),
					'sgrade' => array(
						'text'  => Language::get('sgrade'),
						'url'   => Url::toRoute('sgrade/index'),
						'ico'   => 'icon-dengji',
						'priv'  => ['key' => 'sgrade|all']
					),
					'scategory' => array(
						'text'  => Language::get('scategory'),
						'url'   => Url::toRoute('scategory/index'),
						'ico'   => 'icon-leimupinleifenleileibie',
						'priv'  => ['key' => 'scategory|all']
					),
					'flagstore'  =>array(
						'text'  => Language::get('flagstore'),
						'url'   => Url::toRoute('flagstore/index'),
						'ico'   => 'icon-qijiandian',
						'priv'  => ['key' => 'flagstore|all']
					)
				)
			),
			// 会员
			'user' => array(
				'text'      => Language::get('user'),
				'default'   => 'user_manage',
				'children'  => array(
					'user_manage' => array(
						'text'  => Language::get('user_manage'),
						'url'   => Url::toRoute('user/index'),
						'ico'   => 'icon-huiyuan',
						'priv'  => ['key' => 'user|all']
					),
					'admin_manage' => array(
						'text' => Language::get('admin_manage'),
						 'url'   => Url::toRoute('admin/index'),
						 'ico'   => 'icon-guanliyuan',
						 'priv'  => ['key' => 'admin|all']
					 ),
					 'user_integral'=> array(
						'text' => Language::get('integral_manage'),
						'url'  => Url::toRoute('integral/index'),
						'ico'  => 'icon-jifen1',
						'priv' => ['key' => 'integral|all'] 
					 )
				)
			),
			// 交易
			'trade' => array(
				'text'      => Language::get('trade'),
				'default'   => 'order_manage',
				'children'  => array(
					'deposit_manage' => array(
						'text' => Language::get('deposit_manage'),
						'url'  => Url::toRoute('deposit/index'),
						'ico'  => 'icon-yue',
						'priv' => ['key' => 'deposit|all']
					 ),
					 'cashcard' => array(
						'text' => Language::get('cashcard_manage'),
						'url'  => Url::toRoute('cashcard/index'),
						'ico'  => 'icon-chongzhika',
						'priv' => ['key' => 'cashcard|all']
					),
					'order_manage' => array(
						'text'  => Language::get('order_manage'),
						'url'   => Url::toRoute('order/index'),
						'ico'   => 'icon-order',
						'priv'  => ['key' => 'order|all']
					),
					'refund_manage' => array(
						'text' => Language::get('refund_manage'),
						'url'  => Url::toRoute('refund/index'),
						'ico'  => 'icon-chongzhi2',
						'priv' => ['key' => 'refund|all']
					)
				)
			),
			// 网站
			'website' => array(
				'text'      => Language::get('website'),
				'default'   => 'acategory',
				'children'  => array(
					'acategory' => array(
						'text'  => Language::get('acategory'),
						'url'   => Url::toRoute('acategory/index'),
						'ico'   => 'icon-wenzhangfenlei',
						'priv'  => ['key' => 'acategory|all']
					),
					'article' => array(
						'text'  => Language::get('article'),
						'url'   => Url::toRoute('article/index'),
						'ico'   => 'icon-wenzhang',
						'priv'  => ['key' => 'article|all', 'depends' => 'upload|all']
					),
					'navigation' => array(
						'text'  => Language::get('navigation'),
						'url'   => Url::toRoute('navigation/index'),
						'ico'   => 'icon-daohang',
						'priv'  => ['key' => 'navigation|all']
					),
					'db' => array(
						'text'  => Language::get('db'),
						'url'   => Url::toRoute('db/backup'),
						'ico'   => 'icon-shujuku',
						'priv'  => ['key' => 'db|all']
					),
					'appmarket' => array(
						'text'	=> Language::get('appmarket'),
						'url'	=> Url::toRoute('appmarket/index'),
						'ico'   => 'icon-app',
						'priv'  => ['key' => 'appmarket|all']
					)
				)
			), 
			// 布局
			'layout' => array(
				'text'  => Language::get('layout'),
				'default'   => 'theme',
				'children' => array(
					'theme' => array(
						'text'  => Language::get('theme'),
						'url'   => Url::toRoute('theme/index'),
						'ico'   => 'icon-zhuti',
						'priv'  => ['key' => 'theme|all']
					),
					'template' => array(
						'text'  => Language::get('template'),
						'url'   => Url::toRoute('template/index'),
						'ico'   => 'icon-moban',
						'priv'  => ['key' => 'template|all', 'depends' => 'channel|all']
					)
				)
			),
			// 插件
			'plugin' => array(
				'text'  => Language::get('plugin'),
				'default'   => 'connect',
				'children' => array(
					'connect' => array(
						'text'  => Language::get('plugin_connect'),
						'url'   => Url::toRoute(['plugin/index', 'instance' => 'connect']),
						'ico'   => 'icon-denglu',
						'priv'  => ['key' => 'plugin|connect|all', 'label' => Language::get('plugin_connect')]
					),
					'payment' => array(
						'text'  => Language::get('plugin_payment'),
						'url'   => Url::toRoute(['plugin/index', 'instance' => 'payment']),
						'ico'   => 'icon-zhifu1',
						'priv'  => ['key' => 'plugin|payment|all', 'label' => Language::get('plugin_payment')]
					),
					'oss' 	  => array(
						'text'  => Language::get('plugin_oss'),
						'url'	=> Url::toRoute(['plugin/index', 'instance' => 'oss']),
						'ico'	=> 'icon-oss-upload',
						'priv' 	=> ['key' => 'plugin|oss|all', 'label' => Language::get('plugin_oss')]
					),
					'express' => array(
						'text'  => Language::get('plugin_express'),
						'url'   => Url::toRoute(['plugin/index', 'instance' => 'express']),
						'ico'   => 'icon-wuliu',
						'priv'  => ['key' => 'plugin|express|all', 'label' => Language::get('plugin_express')]
					),
					'sms' 	=> array(
						'text' => Language::get('plugin_sms'),
						'url'  => Url::toRoute(['plugin/index', 'instance' => 'sms']),
						'ico'  => 'icon-duanxin',
						'priv' => ['key' => 'plugin|sms|all', 'label' => Language::get('plugin_sms'), 'depends' => 'msg|all']
					),
					'editor' => array(
						'text'  => Language::get('plugin_editor'),
						'url'   => Url::toRoute(['plugin/index', 'instance' => 'editor']),
						'ico'   => 'icon-duanxin',
						'priv'  => ['key' => 'plugin|editor|all', 'label' => Language::get('plugin_editor')]
					),
					/*'uploader' => array(
						'text'  => Language::get('plugin_uploader'),
						'url'   => Url::toRoute(['plugin/index', 'instance' => 'uploader']),
						'ico'   => 'icon-oss-upload',
						'priv'  => ['key' => 'plugin|uploader|all', 'label' => Language::get('plugin_uploader')]
					),*/
				)
			),  
			// 微信
			'weixin' => array(
				'text'      => Language::get('weixin'),
				'default'   => 'wxsetting',
				'children'  => array(
					'wxsetting' => array(
						'text'  => Language::get('weixin_setting'),
						'url'   => Url::toRoute('weixin/setting'),
						'ico'   => 'icon-weixin',
						'priv'  => ['key' => 'weixin|setting']
					),
					'wxreply' => array(
						'text'  => Language::get('weixin_reply'),
						'url'   => Url::toRoute('weixin/index'),
						'ico'   => 'icon-huifu',
						'priv'  => ['key' => 'weixin|index', 'depends' => 'weixin|addreply,weixin|editreply,weixin|deletereply']
					),
					'wxmenu' => array(
						'text'  => Language::get('weixin_menu'),
						'url'   => Url::toRoute('weixin/menu'),
						'ico'   => 'icon-zidingyicaidan',
						'priv'  => ['key' => 'weixin|menu', 'depends' => 'weixin|add,weixin|edit,weixin|delete,weixin|editcol,weixin|createmenu,weixin|child']
					),
					'wxapplet' => array(
						'text' => Language::get('weixin_applet'),
						'url'  => Url::toRoute(['weixin/applet']),
						'ico' => 'icon-weixinxiaochengxu',
						'priv' => ['key' => 'weixin|applet']
					)
				)
			)
		);
		
        return $menu;
    }
}