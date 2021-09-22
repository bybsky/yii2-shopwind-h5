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
use common\models\UserPrivModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;
use common\library\Timezone;

/**
 * @Id AdminController.php 2018.7.31 $
 * @author mosir
 */

class AdminController extends \common\controllers\BaseAdminController
{
	/**
	 * 初始化
	 */
	public function init()
	{
		parent::init();
	}
	
	public function actionIndex()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['rp', 'page']);
		
		if(!Yii::$app->request->isAjax) 
		{
			$this->params['filtered'] = $this->getConditions($post);
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/flexigrid.js');
			
			$this->params['page'] = Page::seo(['title' => Language::get('admin_list')]);
			return $this->render('../admin.index.html', $this->params);
		}
		else
		{
			$query = UserPrivModel::find()->alias('up')->select('up.*,u.username,u.real_name,u.email,u.phone_mob,u.create_time,u.last_login,u.logins,u.last_ip')->joinWith('user u', false)->where(['store_id' => 0])->indexBy('userid');
			$query = $this->getConditions($post, $query);
			
			$orderFields = ['username', 'real_name', 'email', 'phone_mob', 'create_time', 'last_login', 'logins', 'last_ip'];
			if(in_array($post->sortname, $orderFields) && in_array(strtolower($post->sortorder), ['asc', 'desc'])) {
				$query->orderBy([$post->sortname => strtolower($post->sortorder) == 'asc' ? SORT_ASC : SORT_DESC]);
			} else $query->orderBy(['userid' => SORT_ASC]);
			
			$page = Page::getPage($query->count(), $post->rp ? $post->rp : 10);
			
			$result = ['page' => $post->page, 'total' => $query->count()];
			foreach ($query->offset($page->offset)->limit($page->limit)->asArray()->each() as $key => $val)
			{
				$list = array();
				if($val['privs'] == 'all'){
					$operation = "<em class='red fs12'>".Language::get('system_manager')."</em>";
				} else {
					$operation = "<a class='btn red' onclick=\"fg_delete({$key},'admin')\"><i class='fa fa-trash-o'></i>删除</a>";
					$operation .= "<a class='btn blue' href='".Url::toRoute(['admin/edit', 'id' => $key])."'><i class='fa fa-pencil-square-o'></i>权限</a>";
				}
				$list['operation'] 	= $operation;
				$list['username'] 	= $val['username'];
				$list['real_name'] 	= $val['real_name'];
				$list['email'] 		= $val['email'];
				$list['phone_mob'] 	= $val['phone_mob'];
				$list['create_time']= Timezone::localDate('Y-m-d', $val['create_time']);
				$list['last_login'] = Timezone::localDate('Y-m-d H:i:s', $val['last_login']);
				$list['last_ip'] 	= $val['last_ip'];
				$list['logins']		= $val['logins'];
				$result['list'][$key]= $list;
			}
			return Page::flexigridXML($result);
		}
	}
	
	/* 分配管理权限 */
	public function actionAdd()
    {
        $id = intval(Yii::$app->request->get('id'));
		
		if(!Yii::$app->request->isPost)
        {
			// 查询用户是存在
			if(!($admin = UserModel::find()->select('username,real_name')->where(['userid' => $id])->asArray()->one())) {
				return Message::warning(Language::get('no_such_user'));
			}
			// 查询是否已是管理员
			if (UserPrivModel::isManager($id)) {
				return Message::warning(Language::get('already_admin'));
			}
			
			$this->params['admin'] = $admin;
			$this->params['privs'] = UserPrivModel::getPrivs();
			
			$this->params['page'] = Page::seo(['title' => Language::get('admin_add')]);
			return $this->render('../admin.form.html', $this->params);
        }
        else
        {
			$post = Basewind::trimAll(Yii::$app->request->post());
	
			if(empty($post['privs'])) {
				return Message::warning(Language::get('add_priv'));
			}
            
			if(!($model = UserPrivModel::find()->where(['userid' => $id, 'store_id' => 0])->one())) {
				$model = new UserPrivModel();
				$model->userid = $id;
				$model->store_id = 0;
			}
			$model->privs = implode(',', array_unique($post['privs']));
			if(!$model->save()) {
				return Message::warning($model->errors);
			}
            return Message::display(Language::get('add_admin_ok'), ['admin/index']);
        }
    }
	
	/* 编辑管理权限 */
	public function actionEdit()
    {
        $id = intval(Yii::$app->request->get('id'));
		
		if(!Yii::$app->request->isPost)
        {
			// 查询用户是存在
			if(!($admin = UserModel::find()->select('username,real_name')->where(['userid' => $id])->asArray()->one())) {
				return Message::warning(Language::get('no_such_user'));
			}
			// 查询是否已是管理员
			if(!UserPrivModel::isManager($id)) {
				return Message::warning(Language::get('choose_admin'));
			}
			// 判断是否是系统初始管理员
         	if (UserPrivModel::isAdmin($id)) {
				return Message::warning(Language::get('system_admin_edit'));
        	}
			
			$this->params['admin'] = $admin;
			$this->params['privs'] = UserPrivModel::getPrivs($id);
			
			$this->params['page'] = Page::seo(['title' => Language::get('admin_add')]);
			return $this->render('../admin.form.html', $this->params);
        }
        else
        {
			$post = Basewind::trimAll(Yii::$app->request->post());
	
			if(empty($post['privs'])) {
				return Message::warning(Language::get('add_priv'));
			}
            
			if(!($model = UserPrivModel::find()->where(['userid' => $id, 'store_id' => 0])->one())) {
				$model = new UserPrivModel();
				$model->userid = $id;
				$model->store_id = 0;
			}
			$model->privs = implode(',', array_unique($post['privs']));
			if(!$model->save()) {
				return Message::warning($model->errors);
			}
            return Message::display(Language::get('edit_admin_ok'), ['admin/index']);
        }
    }
	
	public function actionDelete()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		if($post->id) $post->id = explode(',', $post->id);
		
		if(empty($post->id) || !UserPrivModel::isManager($post->id)) {
			return Message::warning(Language::get('choose_admin'));
		}
		if(UserPrivModel::isAdmin($post->id)) {
			return Message::warning(Language::get('system_admin_drop'));
		}
		if(!UserPrivModel::deleteAll(['and', ['store_id' => 0], ['in', 'userid', $post->id]])) {
			return Message::warning(Language::get('drop_failed'));
		}
		return Message::display(Language::get('drop_ok'));
	}
	
	private function getConditions($post, $query = null)
	{
		if($query === null) {
			foreach(array_keys(ArrayHelper::toArray($post)) as $field) {
				if(in_array($field, ['username', 'real_name', 'email', 'phone_mob'])) {
					return true;
				}
			}
			return false;
		}
		if($post->username) {
			$query->andWhere(['username' => $post->username]);
		}
		if($post->real_name) {
			$query->andWhere(['real_name' => $post->real_name]);
		}
		if($post->email) {
			$query->andWhere(['email' => $post->email]);
		}
		if($post->phone_mob) {
			$query->andWhere(['phone_mob' => $post->phone_mob]);
		}
		return $query;
	}
}
