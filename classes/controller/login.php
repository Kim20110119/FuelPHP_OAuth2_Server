<?php
/**
 * OAuth 2.0 controller example
 *
 * @author              Alex Bilbie | www.alexbilbie.com | alex@alexbilbie.com
 * @copyright   		Copyright (c) 2011, Alex Bilbie.
 * @license             http://www.opensource.org/licenses/mit-license.php
 */

class Controller_Login extends Controller_Template
{
    
    /**
     * 「同意」画面へのリダイレクト
     */
    public function before()
    {

    }

    /**
     * 「ログイン」画面へのリダイレクト
     */
    public function action_index()
    {
        $vars = array(
            'error' => FALSE,
            'error_messages' => array(),
            'user_name' => null,
        );
        \Session::delete('oauth.user');
        // ログイン画面へのリダイレクト
        return View::forge('login/sign_in',$vars);
    }
	
    /**
     * SSOサービスのサイン処理
     */
    public function action_top()
    {
        try{
            $user = Session::get('oauth.user');
            if(!empty($user)){
                $vars['user_name'] = $user->username;
                return View::forge('index/top', $vars);
            }
            $vars = array(
                'error' => FALSE,
                'error_messages' => array(),
                'user_name' => null,
            );
            $u = trim(Input::post('username')); // ユーザー名
            $p = trim(Input::post('password')); // パスワード

            // ユーザー名の検証
            if ($u === FALSE || empty($u))
            {
                $vars['error_messages'][] = 'The username field should not be empty';
                $vars['error'] = TRUE;
            }
            // パスワードの検証
            if ($p === FALSE || empty($p))
            {
                $vars['error_messages'][] = 'The password field should not be empty';
                $vars['error'] = TRUE;
            }

            // ログインを確認し、承認を取得
            if ($vars['error'] === TRUE)
            {
                return View::forge('login/sign_in', $vars);
            }
            
            // ログイン情報の設定
            $credentials = array(
              'mail_address'    => $u,
              'password'        => $p,
            );
            // ログインの承認
            $user = \Sentry::authenticate($credentials, false);

            if ($user === FALSE)
            {
                $vars['error_messages'][] = 'Invalid username and/or password';
                $vars['error'] = TRUE;
                return View::forge('login/sign_in', $vars);
            }
            else
            {
                
                // セッションにユーザー情報の設定
                Session::set('oauth.user', (object) array(
                    'id' => $user->id,
                    'username' => $user->display_name
                    )
                        );
                
                $vars['user_name'] = $user->display_name;
                return View::forge('index/top', $vars);
            }
        }catch(UserNotFoundException $unfe){
            $vars['error_messages'][] = 'Invalid username and/or password';
            $vars['error'] = TRUE;
            return View::forge('login/sign_in', $vars);
        }        
    }
    
    /**
     * SSOサービスのサイン処理
     */
    public function action_logout()
    {
        \Session::delete('oauth.user');
        // ホームページ画面へのリダイレクト
        return View::forge('index/index');
    }
}