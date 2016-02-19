<?php
/**
 * OAuth 2.0 controller example
 *
 * @author              Alex Bilbie | www.alexbilbie.com | alex@alexbilbie.com
 * @copyright   		Copyright (c) 2011, Alex Bilbie.
 * @license             http://www.opensource.org/licenses/mit-license.php
 */

class Controller_Index extends Controller_Template
{
    
    /**
     * beforeメソッド
     */
    public function before()
    {

    }

    /**
     * 「ホームページ」画面へのリダイレクト
     */
    public function action_index()
    {
         \Session::delete('oauth.user');
        // ログイン画面へのリダイレクト
        return View::forge('index/index');
    }
}