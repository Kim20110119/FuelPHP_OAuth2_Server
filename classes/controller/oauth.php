<?php
/**
 * OAuth 2.0 controller example
 *
 * @author              Alex Bilbie | www.alexbilbie.com | alex@alexbilbie.com
 * @copyright   		Copyright (c) 2011, Alex Bilbie.
 * @license             http://www.opensource.org/licenses/mit-license.php
 */

class Controller_Oauth extends Controller_Template
{
    /* レスポンス */
    public $response = null;
    
    /**
     * 「同意」画面へのリダイレクト
     */
    public function before()
    {

        // Serverモデル
        $this->oauth = \OAuth2\Server::forge();
        // レスポンス
        $this->response = new \Fuel\Core\Response();
    }

    /**
     * 「同意」画面へのリダイレクト
     */
    public function action_index()
    {
        // GETクエリのパラメータ
        $params = array();

        // クライアントID
        if (($client_id = Input::get('client_id')))
        {
            $params['client_id'] = trim($client_id);
        }
        else
        {
            $this->_fail('invalid_request', 'The request is missing a required parameter, includes an invalid parameter value, or is otherwise malformed. See client_id.', NULL, array(), 400);
            return;
        }

        // クライアントリダイレクトURL
        if (($redirect_uri = Input::get('redirect_uri')))
        {
            $params['redirect_uri'] = trim($redirect_uri);
        }
        else
        {
            $this->_fail('invalid_request', 'The request is missing a required parameter, includes an invalid parameter value, or is otherwise malformed. See redirect_uri.', NULL, array(), 400);
            return;
        }

        // レスポンスタイプの検証
        if (($response_type = Input::get('response_type')))
        {
            $response_type = trim($response_type);
            $valid_response_types = array('code'); // array to allow for future expansion

            if ( ! in_array($response_type, $valid_response_types))
            {
                $this->_fail('unsupported_response_type', 'The authorization server does not support obtaining the an authorization code using this method. Supported response types are \'' . implode('\' or ', $valid_response_types) . '\'.', $params['redirect_uri'], array(), 400);
                return;
            }
            else
            {
                $params['response_type'] = $response_type;
            }
        }
        else
        {
            $this->_fail('invalid_request', 'The request is missing a required parameter, includes an invalid parameter value, or is otherwise malformed. See response_type.', NULL, array(), 400);
            return;
        }

        // クライアントIDとリダイレクトURLの検証(「オブジェクト」或いは「False」をリターンする)
        $client_details = $this->oauth->validate_client($params['client_id'], NULL, $params['redirect_uri']);

        if ($client_details === FALSE)
        {
            $this->_fail('unauthorized_client', 'The client is not authorized to request an authorization code using this method.', NULL, array(), 403);
            return;
        }
        else
        {
            // クライアント情報をセッションに設定
            Session::set('oauth.client', $client_details);
        }


        // スコープの検証
        if (($scope_string = Input::get('scope')))
        {
            $scopes = explode('+', $scope_string);
            $params['scope'] = $scopes;
        }
        else
        {
            $params['scope'] = array();
        }

        // スコープ有効性のチェック
        if (count($params['scope']) > 0)
        {
            foreach($params['scope'] as $s)
            {
                $exists = $this->oauth->scope_exists($s);
                if ( ! $exists)
                {
                    $this->_fail('invalid_request', 'The request is missing a required parameter, includes an invalid parameter value, or is otherwise malformed. See scope \''.$s.'\'.', NULL, array(), 400);
                    return;
                }
            }
        }
        else
        {
            $this->_fail('invalid_request', 'The request is missing a required parameter, includes an invalid parameter value, or is otherwise malformed. See scope.', NULL, array(), 400);
            return;
        }

        // クライアント詳細情報をセッションに設定
        Session::set('oauth.client', $client_details);
        // セッションからログインユーザー情報を削除する
//        Session::delete('oauth.user');

        // ステートを取得する
        $params['state'] = Input::get('state') ? trim(Input::get('state')) : '';

        // パラメータをセッションに設定
        Session::set('oauth.params', $params);

        // ログイン画面へのリダイレクト
        Response::redirect('oauth/sign_in');
    }
	
    /**
     * SSOサービスのサイン処理
     */
    public function action_sign_in()
    {
        $user = Session::get('oauth.user');
        $client = Session::get('oauth.client');
        
        // ユーザーのログイン状況のチェック（ログインされてないと承認画面へリダイレクト）
        if ($user && $client)
        {
            Response::redirect('oauth/authorise');
        }

        // クライアントパラメータの格納状況のチェック
        if ($client === NULL)
        {
            $this->_fail('invalid_request', 'No client details have been saved. Have you deleted your cookies?', NULL, array(), 400);
            return \Fuel\Core\Response::forge($this->response);
        }

        // エラーメッセージ
        $vars = array(
            'error' => FALSE,
            'error_messages' => array(),
            'client_name' => $client->name,
        );

        // 認証サーバーログインチェック
        if (Input::post('validate_user'))
        {
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
            if ($vars['error'] === FALSE)
            {
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
                }
                else
                {
                    // セッションにユーザー情報の設定
                    Session::set('oauth.user', (object) array(
                        'id' => $user->id,
                        'username' => $user->username,
                        'email' => $user->email,
                        'non_ad_user' => TRUE
                        )
                            );
                }
            }

            // エラーが発生されてない場合、「同意」画面へリダイレクト
            if ($vars['error'] === FALSE)
            {
                Response::redirect('oauth/authorise');
            }
        }

        return View::forge('oauth/sign_in', $vars);
    }	
	
    /**
     * SSOサービスのサインアウト処理
     */
    public function action_sign_out()
    {
        Session::destroy();

        if (($redirect_uri = Input::get('redirect_uri')))
        {
            Response::redirect($redirect_uri);
        }
        else
        {
            $this->template->body = View::forge('oauth_server/sign_out');
        }
    }

//    /**
//     * 認可エンドポイント
//     */
//    public function action_authorise()
//    {
//        $user = Session::get('oauth.user');
//        $client = Session::get('oauth.client');
//        $params = Session::get('oauth.params');
//        
//        // ユーザーが認証サーバーへのログインのチェック
//        if ($user === NULL)
//        {
//            Session::set('sign_in_redirect', 'oauth/authorise');
//            Response::redirect('oauth/sign_in');
//        }
//
//        // クライアントのチェック
//        if ($client === NULL)
//        {
//            $this->_fail('invalid_request', 'No client details have been saved. Have you deleted your cookies?', NULL, array(), 400);
//            return \Fuel\Core\Response::forge($this->response);
//        }
//
//        // リクエストパラメータのチェック
//        if ($params === NULL)
//        {
//            $this->_fail('invalid_request', 'No client details have been saved. Have you deleted your cookies?', NULL, array(), 400);
//            return \Fuel\Core\Response::forge($this->response);
//        }
//
//        // ユーザーがアプリケーションを承認するかのチェック
//        if (($doauth = Input::post('doauth')))
//        {		
//            
//            switch ($doauth)
//            {
//                // 承認の場合
//                case "Approve":
//                        $authorised = FALSE;
//                        $action = 'newrequest';		
//                break;
//
//                // 拒否の場合
//                case "Deny":
//
//                    $error_params = array(
//                        'error' => 'access_denied',
//                        'error_description' => 'The resource owner or authorization server denied the request.'
//                    );
//
//                    if ($params['state'])
//                    {
//                        $error_params['state'] = $params['state'];
//                    }				
//
//                    $redirect_uri = $this->oauth->redirect_uri($params['redirect_uri'], implode('&', $error_params));
//                    Session::delete(array('params','oauth.client', 'sign_in_redirect'));
//                    Response::redirect($redirect_uri);
//                break;
//
//            }
//        }
//        else
//        {
//            // ユーザーのアクセストークンを持っているかのチェック
//            $authorised = $this->oauth->access_token_exists($user->id, $client->client_id);
//            
//            if ($authorised)
//            {
//                $match = $this->oauth->validate_access_token($authorised, $params['scope']);
//                $action = $match ? 'finish' : 'approve';
//            }
//            else
//            {
//                // アプリケーションの自動承認のチェック
//                $action = ! empty($client->auto_approve) ? 'newrequest' : 'approve';
//            }
//        }
//
//        switch ($action)
//        {
//            // 承認処理
//            case 'approve':
//
//                $requested_scopes = $params['scope'];
//                $scopes = $this->oauth->get_scope($requested_scopes);
//
//                $vars = array(
//                    'client_name'   => $client->name,
//                    'scopes'        => $scopes
//                );
//
//                return View::forge('oauth/authorise', $vars);
//
//            // 新リクエスト
//            case 'newrequest':
//                $code = $this->oauth->new_auth_code($client->client_id, $user->id, $params['redirect_uri'], $params['scope'], $authorised);
//                $this->fast_code_redirect($params['redirect_uri'], $params['state'], $code);
//                break;
//            // 承認完了
//            case 'finish':
//                $code = $this->oauth->new_auth_code($client->client_id, $user->id, $params['redirect_uri'], $params['scope'], $authorised);
//                $this->fast_token_redirect($params['redirect_uri'], $params['state'], $code);
//                break;
//        }
//    }
	
    /**
     * 認可エンドポイント
     */
    public function action_authorise()
    {
        $user = Session::get('oauth.user');
        $client = Session::get('oauth.client');
        $params = Session::get('oauth.params');
        
        // ユーザーが認証サーバーへのログインのチェック
        if ($user === NULL)
        {
            Session::set('sign_in_redirect', 'oauth/authorise');
            Response::redirect('oauth/sign_in');
        }

        // クライアントのチェック
        if ($client === NULL)
        {
            $this->_fail('invalid_request', 'No client details have been saved. Have you deleted your cookies?', NULL, array(), 400);
            return \Fuel\Core\Response::forge($this->response);
        }

        // リクエストパラメータのチェック
        if ($params === NULL)
        {
            $this->_fail('invalid_request', 'No client details have been saved. Have you deleted your cookies?', NULL, array(), 400);
            return \Fuel\Core\Response::forge($this->response);
        }

        $authorised = FALSE;
        $action = 'newrequest';	
        switch ($action)
        {
            // 承認処理
            case 'approve':

                $requested_scopes = $params['scope'];
                $scopes = $this->oauth->get_scope($requested_scopes);

                $vars = array(
                    'client_name'   => $client->name,
                    'scopes'        => $scopes
                );

                return View::forge('oauth/authorise', $vars);

            // 新リクエスト
            case 'newrequest':
                $code = $this->oauth->new_auth_code($client->client_id, $user->id, $params['redirect_uri'], $params['scope'], $authorised);
                $this->fast_code_redirect($params['redirect_uri'], $params['state'], $code);
                break;
            // 承認完了
            case 'finish':
                $code = $this->oauth->new_auth_code($client->client_id, $user->id, $params['redirect_uri'], $params['scope'], $authorised);
                $this->fast_token_redirect($params['redirect_uri'], $params['state'], $code);
                break;
        }
    }
	
    /**
     * 新しいアクセストークンを生成する処理
     */
    public function action_access_token()
    {
        // POSTクエリのパラメータ
        $params = array();
        // クライアントID
        if (($client_id = Input::post('client_id')))
        {
            $params['client_id'] = trim($client_id);
        }
        else
        {
            $this->_fail('invalid_request', 'The request is missing a required parameter, includes an invalid parameter value, or is otherwise malformed. See client_id.', NULL, array(), 400, 'json');
            return \Fuel\Core\Response::forge($this->response);
        }

        // クライアントシークレット
        if (($client_secret = Input::post('client_secret')))
        {
            $params['client_secret'] = trim($client_secret);
        }
        else
        {
            $this->_fail('invalid_request', 'The request is missing a required parameter, includes an invalid parameter value, or is otherwise malformed. See client_secret.', NULL, array(), 400, 'json');
            return \Fuel\Core\Response::forge($this->response);
        }

        // クライアントリダイレクトURL
        if (($redirect_uri = Input::post('redirect_uri')))
        {
            $params['redirect_uri'] = urldecode(trim($redirect_uri));
        }
        else
        {
            $this->_fail('invalid_request', 'The request is missing a required parameter, includes an invalid parameter value, or is otherwise malformed. See redirect_uri.', NULL, array(), 400, 'json');
            return \Fuel\Core\Response::forge($this->response);
        }

        // 認可コード
        if (($code = Input::post('code')))
        {
            $params['code'] = trim($code);
        }
        else
        {
            $this->_fail('invalid_request', 'The request is missing a required parameter, includes an invalid parameter value, or is otherwise malformed. See code.', NULL, array(), 400, 'json');
            return \Fuel\Core\Response::forge($this->response);
        }

        // 認可タイプを検証する
        if (($grant_type = Input::post('grant_type')))
        {
            $grant_type = trim($grant_type);

            if ( ! in_array($grant_type, array('authorization_code')))
            {
                $this->_fail('invalid_request', 'The request is missing a required parameter, includes an invalid parameter value, or is otherwise malformed. See grant_type.', NULL, array(), 400, 'json');
                return \Fuel\Core\Response::forge($this->response);
            }
            else
            {
                $params['grant_type'] = $grant_type;
            }
        }		
        else
        {
            $this->_fail('invalid_request', 'The request is missing a required parameter, includes an invalid parameter value, or is otherwise malformed. See grant_type.', NULL, array(), 400, 'json');
            return \Fuel\Core\Response::forge($this->response);
        }

        // クライアントIDとクライアントリダイレクトURLを検証する
        $client_details = $this->oauth->validate_client($params['client_id'], $params['client_secret'], $params['redirect_uri']); // returns object or FALSE

        if ($client_details === FALSE )
        {
            $this->_fail('unauthorized_client', 'The client is not authorized to request an authorization code using this method', NULL, array(), 403, 'json');
            return \Fuel\Core\Response::forge($this->response);
        }

        // 認可タイプに対応する処理
        switch ($params['grant_type'])
        {
            case "authorization_code":

                // 認可コードを検証する
                $session = $this->oauth->validate_auth_code($params['code'], $params['client_id'], $params['redirect_uri']);

                if ($session === FALSE)
                {
                    $this->_fail('invalid_request', 'The authorization code is invalid.', NULL, array(), 403, 'json');
                    return;
                }

                // 新しいアクセストークンを生成 (セッションから認可コードを削除)
                $access_token = $this->oauth->get_access_token($session);

                // アプリケーションにレスポンスする
                $this->_response(array('access_token' => $access_token));

                return \Fuel\Core\Response::forge($this->response);
        }
    }	
    
    /**
     * ユーザー情報を取得処理
     */
    public function action_attribute()
    {
        // パラメータ 
        $params = Session::get('oauth.params');
        // スキマー
        if (($schema = Input::get('schema')))
        {
            $params['schema'] = trim($schema);
        }
        else
        {
            $this->_fail('invalid_request', 'The request is missing a required parameter, includes an invalid parameter value, or is otherwise malformed. See schema.', NULL, array(), 400, 'json');
            return \Fuel\Core\Response::forge($this->response);
        }

        // アクセストークン
        if (($access_token = Input::get('access_token')))
        {
            $params['access_token'] = trim($access_token);
            
        }
        else
        {
            $this->_fail('invalid_request', 'The request is missing a required parameter, includes an invalid parameter value, or is otherwise malformed. See access_token.', NULL, array(), 400, 'json');
            return \Fuel\Core\Response::forge($this->response);
        }
        
        // アクセストークンを検証する
        $type_id = $this->oauth->get_type_id($params['access_token']);

        if($type_id){
            // ユーザー情報を取得する
            $userInfo = \Sentry::findUserById($type_id);
            // グループ情報
            $groupInfo = array(
                'mail_address'          => NULL, // グループメールアドレス
                'person_name1'          => NULL, // 登録名（姓）
                'person_name2'          => NULL, // 登録名（名）
                'person_name_kana1'     => NULL, // ひらがな（姓）
                'person_name_kana2'     => NULL, // ひらがな（名）
                'user_tel'              => NULL, // 連絡先（電話番号）
            );
            
            // ユーザーに紐付いているグループ情報を取得する
            if($userInfo->group_id){
                $group = \Sentry::findGroupById($userInfo->group_id);
                $groupInfo = array(
                    'mail_address'          => $group->mail_address,        // グループメールアドレス
                    'person_name1'          => $group->person_name1,        // 登録名（姓）
                    'person_name2'          => $group->person_name2,        // 登録名（名）
                    'person_name_kana1'     => $group->person_name_kana1,   // ひらがな（姓）
                    'person_name_kana2'     => $group->person_name_kana2,   // ひらがな（名）
                    'user_tel'              => $group->tel,                 // 連絡先（電話番号）
                );
            }

            // アプリケーションにレスポンスする
            $msg = array(
                'open_id'                   => $userInfo->id,                   // オープンID
                'client_display_name'       => $userInfo->display_name,         // ユーザー表示名
                'client_mail_address'       => $userInfo->mail_address,         // ユーザーメールアドレス
                'client_person_name1'       => $userInfo->person_name1,         // ユーザー登録名（姓）
                'client_person_name2'       => $userInfo->person_name2,         // ユーザー登録名（名）
                'client_person_name_kana1'  => $userInfo->person_name_kana1,    // ユーザーひらがな（姓）
                'client_person_name_kana2'  => $userInfo->person_name_kana2,    // ユーザーひらがな（名）
                'user_mail_address'         => $groupInfo['mail_address'],      // グループメールアドレス
                'user_person_name1'         => $groupInfo['person_name1'],      // グループ登録名（姓）
                'user_person_name2'         => $groupInfo['person_name2'],      // グループ登録名（名）
                'user_person_name_kana1'    => $groupInfo['person_name_kana1'], // グループひらがな（姓）
                'user_person_name_kana2'    => $groupInfo['person_name_kana2'], // グループひらがな（名）
                'user_tel'                  => $groupInfo['user_tel'],          // グループ連絡先（電話番号）
                );
            $this->_response($msg);
        }
        else
        {
            $this->_fail('invalid_request', 'The request is missing a required parameter, includes an invalid parameter value, or is otherwise malformed. See schema.', NULL, array(), 404, 'json');
        }
        return Fuel\Core\Response::forge($this->response);
    }

    
    /**
     * 新しい認可コードを生成し、クライアントへリダイレクトする
     * WEBサーバフローに使用される
     * 
     * @access private
     * @param string $redirect_uri リダイレクト
     * @param string $state ステート
     * @param string $code 認可コード
     * @return void
     */
    private function fast_code_redirect($redirect_uri = '', $state = '', $code = '')
    {
        $redirect_uri = $this->oauth->redirect_uri($redirect_uri, array('code' => $code, 'state' => $state));
        Session::delete(array('oauth.params', 'oauth.client', 'sign_in_redirect'));
        Response::redirect($redirect_uri);	
    }
	
    /**
     * 新しいアクセストークンを生成し、クライアントへリダイレクトする
     * ユーザーエージェントフローで使用される
     * 
     * @access private
     * @param string $redirect_uri リダイレクトURL
     * @param string $state ステート
     * @param string $code 認可コード
     * @return void
     */
    private function fast_token_redirect($redirect_uri = '', $state = '', $code = '')
    {
        $redirect_uri = $this->oauth->redirect_uri($redirect_uri, array('code' => $code, 'state' => $state), '?');
        Session::delete(array('oauth.params','oauth.client', 'sign_in_redirect'));
        Response::redirect($redirect_uri);
    }
	
	
    /**
     * エラーメッセージを設定する処理
     * 
     * @access private
     * @param mixed $error エラーフラグ
     * @param mixed $description エラー説明
     * @param mixed $url リダイレクトURL
     * @param mixed $params パラメータ
     * @param mixed $status レスポンスステータス
     * @param mixed $output レスポンス形式
     * @return void
     */

    private function _fail($error, $description, $url = NULL, $params = array(), $status = 400, $output = 'html')
    {
        // リダイレクトURL指定した場合
        if ($url)
        {
            $this->oauth->redirect_uri(
                    $url,
                    array_merge($params, array(
                        'error=' . $error,
                        'error_description=' . urlencode($description)
                                )
                            )
                    );
        }
        // リダイレクトURL指定されてない場合
        else
        {
            switch ($output)
            {
                case 'html':
                    throw new Exception('[OAuth error: ' . $error . '] ' . $description, $status);
                case 'json':
                    // コンテンツタイプを「JSON」に設定
                    $this->response->set_header('Content-Type', 'application/json');
                    // レスポンスステータスを設定
                    $this->response->status = $status;
                    // エラーメッセージを設定
                    $this->response->body(
                            json_encode(
                                    array(
                                        'error' => true,
                                        'error_message' => '[OAuth error: ' . $error . '] ' . $description,
                                        'access_token' => null
                                        )
                                    )
                            );
                    break;
                default:
                        throw new Exception('[OAuth error: ' . $error . '] ' . $description, $status);
            }

        }
    }
	
	
    /**
     * JSON形式レスポンス
     * 
     * @access private
     * @param mixed $msg メッセージ
     * @return voidｓ
     */
    private function _response($msg)
    {
        // エラーメッセージフラグ
        $msg['error'] = false;
        // エラーメッセージ内容
        $msg['error_message'] = '';
        // レスポンスヘッダー
        $this->response->set_header('Content-Type', 'application/json');
        // レスポンスステータス
        $this->response->status = 200;
        // レスポンスボディ
        $this->response->body(json_encode($msg));
    }

}