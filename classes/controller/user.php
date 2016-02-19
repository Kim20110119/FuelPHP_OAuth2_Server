<?php
/**
 * Sentry認証用ユーザー作成処理
 */

class Controller_User extends Controller_Template
{
    public function before()
    {
    }
    /**
     * ユーザー01の作成
     */
    public function action_user01()
    {
        try
        {
            $user = Sentry::getUserProvider()
                    ->create(
                            array(
                               'id'                 => $this->makeRandStr(),
                               'display_name'       => 'テストユーザー01',
                               'person_name1'       => 'テスト',
                               'person_name2'       => 'ユーザー01',
                               'person_name_kana1'  => 'テスト',
                               'person_name_kana2'  => 'ユーザー01',
                               'mail_address'       => 'test_user01@gmail.com',
                               'password'           => 'password',
                               'activated'          => 1,
                               'group_id'           => 1,
                           )
                   );
            $data = array('result'=>'「test_user01@gmail.com」ユーザーの登録成功');
            return View::forge('user/index', $data);
        }
        catch (Cartalyst\Sentry\Users\LoginRequiredException $e)
        {
            $data = array('result'=>'ログインフィールドは必須です。');
            return View::forge('user/index', $data);
        }
        catch (Cartalyst\Sentry\Users\PasswordRequiredException $e)
        {
            $data = array('result'=>'パスワードフィールドは必須です。');
            return View::forge('user/index', $data);
        }
        catch (Cartalyst\Sentry\Users\UserExistsException $e)
        {
            $data = array('result'=>'このログインユーザーは存在します。');
            return View::forge('user/index', $data);
        }
        catch (Cartalyst\Sentry\Groups\GroupNotFoundException $e)
        {
            $data = array('result'=>'グループは見つかりません。');
            return View::forge('user/index', $data);
        }
    }
    
    /**
     * ユーザー02の作成
     */
    public function action_user02()
    {
        try
        {
            $user = Sentry::getUserProvider()
                    ->create(
                            array(
                               'id'                 => $this->makeRandStr(),
                               'display_name'       => 'テストユーザー02',
                               'person_name1'       => 'テスト',
                               'person_name2'       => 'ユーザー02',
                               'person_name_kana1'  => 'テスト',
                               'person_name_kana2'  => 'ユーザー02',
                               'mail_address'       => 'test_user02@gmail.com',
                               'password'           => 'password',
                               'activated'          => 1,
                               'group_id'           => 2,
                           )
                   );
            $data = array('result'=>'「test_user02@gmail.com」ユーザーの登録成功');
            return View::forge('user/index', $data);
        }
        catch (Cartalyst\Sentry\Users\LoginRequiredException $e)
        {
            $data = array('result'=>'ログインフィールドは必須です。');
            return View::forge('user/index', $data);
        }
        catch (Cartalyst\Sentry\Users\PasswordRequiredException $e)
        {
            $data = array('result'=>'パスワードフィールドは必須です。');
            return View::forge('user/index', $data);
        }
        catch (Cartalyst\Sentry\Users\UserExistsException $e)
        {
            $data = array('result'=>'このログインユーザーは存在します。');
            return View::forge('user/index', $data);
        }
        catch (Cartalyst\Sentry\Groups\GroupNotFoundException $e)
        {
            $data = array('result'=>'グループは見つかりません。');
            return View::forge('user/index', $data);
        }
    }
        
    /**
     * ランダム文字列生成 (英数字)
     * $length: 生成する文字数
     */
    function makeRandStr($length = 26) {
        $str = array_merge(range('a', 'z'), range('0', '9'), range('A', 'Z'));
        $r_str = null;
        for ($i = 0; $i < $length; $i++) {
            $r_str .= $str[rand(0, count($str))];
        }
        return $r_str;
   }
}