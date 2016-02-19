<?php
/**
 * Sentry認証用グループ作成処理
 */

class Controller_Group extends Controller_Template
{
        public function before()
	{
	}
	/**
	 * テストグループ01の作成
	 */
	public function action_group01()
	{
            try
            {
                Sentry::getGroupProvider()
                    ->create(
                            array(
                                'mail_address' => 'test_group01@gmail.com',
                                'tel' => '06-6267-0001',
                                'display_name' => 'テストグループ01',
                                'person_name1' => 'テスト',
                                'person_name2' => 'グループ01',
                                'permissions' => array(
                                                    'admin' => 1,
                                                    'user' => 1,
                                                ),
                            )
                    );
                
                $data = array('result'=>'テストグループ01の登録成功');
                return View::forge('group/index', $data);
            }
            catch (Cartalyst\Sentry\Groups\GroupExistsException $e)
            {
                $data = array('result'=>'テストグループ01は既に存在');
                return View::forge('group/index', $data);
            }
	}
	
	/**
	 * テストグループ02の作成
	 */
	public function action_group02()
	{
            try
            {
                Sentry::getGroupProvider()
                    ->create(
                            array(
                                'mail_address' => 'test_group02@gmail.com',
                                'tel' => '06-6267-0002',
                                'display_name' => 'テストグループ02',
                                'person_name1' => 'テスト',
                                'person_name2' => 'グループ02',
                                'permissions' => array(
                                                    'admin' => 1,
                                                    'user' => 1,
                                                ),
                            )
                    );
                
                $data = array('result'=>'テストグループ02の登録成功');
                return View::forge('group/index', $data);
            }
            catch (Cartalyst\Sentry\Groups\GroupExistsException $e)
            {
                $data = array('result'=>'テストグループ02は既に存在');
                return View::forge('group/index', $data);
            }
	}
}