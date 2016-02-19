<?php

/**
 * OAuth Provider
 *
 * @package    FuelPHP/OAuth2
 * @category   Server
 * @author     Phil Sturgeon
 * @copyright  (c) 2012 HappyNinjas Ltd
 * @license    http://philsturgeon.co.uk/code/dbad-license
 */

namespace OAuth2;

class Server {
    
    /**
     * フォージメソッド
     *
     * @param Model_Server $model サーバーモデル
     * @return Model_Server $model サーバーモデル
     */
    public static function forge(Model_Server $model = null)
    {
        return new static($model);
    }
    
    /**
     * コンストラクタメソッド
     *
     * @param Model_Server $model サーバーモデル
     * @return Model_Server $model サーバーモデル/Model_Server_Db $model サーバーDBモデル
     */
    public function __construct(Model_Server $model = null)
    {
        $this->model = $model === null ? new Model_Server_Db : $model;
    }

    /**************************************************************
    //! クライアントスタッフ
    **************************************************************/

    /**
     * クライアント情報を検証する
     * 
     * @param string $client_id クライアントID
     * @param mixed $client_secret クライアントシークレット
     * @param mixed $redirect_uri コールバックURL
     * @return bool|object
     */
    public function validate_client($client_id, $client_secret = NULL, $redirect_uri = NULL)
    {
        $params = array(
            'client_id' => $client_id,
        );

        if ($client_secret !== NULL)
        {
            $params['client_secret'] = $client_secret;
        }

        // パラメータによりクライアント情報を取得する
        if ( ! ($client = $this->model->get_client($params)))
        {
            return false;
        }

        $given_domain = parse_url($redirect_uri, PHP_URL_HOST);          // 指定されたドメイン
        $stored_domain = parse_url($client->redirect_uri, PHP_URL_HOST); // 保存されたドメイン

        // 指定されたドメインと保存されたドメインの比較処理
        return ($given_domain === $stored_domain) ? $client : false;
    }
	
	/**************************************************************
	//! 認可コードスタッフ
	**************************************************************/
	
	/**
	 * アプリケーションの承認後、認可コードを生成する
	 * 
	 * @param mixed $client_id クライアントID
	 * @param mixed $user_id ユーザーID
	 * @param mixed $redirect_uri コールバックURL
	 * @param array $scopes スコープ
         * @param string $access_token アクセストークン
	 * @return string
	 */
	public function new_auth_code($client_id = '', $user_id = '', $redirect_uri = '', $scopes = array(), $access_token = null)
	{
            // アクセストークンが存在する場合、OAuthセッションを更新する
            if ($access_token)
            {
                // 認可コード
                $code = md5(time().uniqid());
                // OAuthセッションを更新する
                $this->model->update_session(
                        array(
                            'type_id'       => $user_id,
                            'type'          => 'user',
                            'client_id'     => $client_id,
                            'access_token'  => $access_token
                        ),
                        array(
                            'code'          => $code,
                            'stage'         => 'request',
                            'redirect_uri'  => $redirect_uri,
                            'last_updated'  => time(),
                ));
                return $code;
            }
            // アクセストークンが存在しない場合、OAuthセッションを登録する
            else
            {
                // 既存のOAuthセッション情報を削除する
                $this->model->delete_session(
                        array(
                            'client_id'     => $client_id,
                            'type_id'       => $user_id,
                            'type'          => 'user'
                            )
                        );
                // 認可コード
                $code = md5(time().uniqid());

                // OAuthセッション情報登録する
                $this->model->new_session(
                        array(
                            'client_id'			=> $client_id,
                            'redirect_uri'		=> $redirect_uri,
                            'type_id'			=> $user_id,
                            'type'			=> 'user',
                            'code'			=> $code,
                            'first_requested'           => time(),
                            'last_updated'		=> time(),
                            'access_token'		=> NULL,
                            ),
                        $scopes
                        );
            }

            return $code;
	}
	
	
	/**
         * 認可コードを検証する
	 * 
	 * @param string $code 認可コード
	 * @param string $client_id アプリケーションID
	 * @param string $redirect_uri コールバックURL
	 * @return bool|int
	 */
	public function validate_auth_code($code = '', $client_id = '', $redirect_uri = '')
	{
            return $this->model->get_session(
                    array(
                        'client_id'     => $client_id,
                        'redirect_uri'  => $redirect_uri, 
                        'code'          => $code
                    )
                    ) ?: false;
	}
	
	/**************************************************************
	//! アクセストークンスタッフ
	**************************************************************/	
	/**
	 * 新しいアクセストークンを生成する
	 * 
	 * @param string $session_id OAuthセッションID
	 * @return string
	 */
	public function get_access_token($session_id)
	{
            // OAuthIDによりアクセストークンを取得する
            $access_token = $this->model->get_token_from_session($session_id);

            // アクセストークンが存在する場合
            if ($access_token)
            {
                // 認可コードを削除する
                $this->model->update_session(
                        array(
                            'id'        => $session_id
                        ),
                        array(
                            'code'	=> null,
                            'stage'	=> 'granted'
                            )
                        );

                // アクセストークン
                return $access_token;
            }
            // アクセストークンが存在しない場合
            else
            {
                return $this->model->create_access_token($session_id);
            }
	}
		
	/**
	 * アクセストークンを検証する
	 * 
	 * @param string $access_token アクセストークン
         * @param array $scopes スコープ
	 * @return void
	 */
	public function validate_access_token($access_token, $scopes = array())
	{
            // アクセストークンの存在検証
            $session_id = $this->model->get_session(
                    array(
                        'access_token' => $access_token
                    )
                    );
		
            // アクセストークンが存在しない場合
            if ( ! $session_id)
            {
                return false;
            }

            // アクセススコープが存在する場合、各スコープを検証する
            if (count($scopes) > 0)
            {
                foreach ($scopes as $scope)
                {
                    if ( ! $this->model->has_scope($access_token, $scope))
                    {
                            return false;
                    }
                }
            }

            return true;
	}
        
	/**
	 * セッションIDを取得する
	 * 
	 * @param string $access_token アクセストークン
	 * @return void
	 */
	public function get_session_id($access_token)
	{
            // セッションIDを取得する
            $session_id = $this->model->get_session(
                    array(
                        'access_token' => $access_token
                    )
                    );
            return isset($session_id) ? $session_id : false;
	}
        
        /**
	 * アクセストークンによりタイプIDを取得する
	 * 
	 * @param string $access_token アクセストークン
	 * @return void
	 */
	public function get_type_id($access_token)
	{
            // タイプIDを取得する
            $type_id = $this->model->get_type_id(
                    array(
                        'access_token' => $access_token
                    )
                    );
            return isset($type_id) ? $type_id : false;
	}

	/**
	 * アプリケーションの許可を検証する
	 * 
	 * @param string $user_id ユーザーID
	 * @param string $client_id クライアントID
	 * @return bool
	 */
	public function access_token_exists($user_id, $client_id)
	{
            return $this->model->has_user_authenicated_client($user_id, $client_id);
	}
	
	/**************************************************************
	//! 他のスタッフ
	**************************************************************/
	/**
	 * スコープを検証する
	 * 
	 * @param string $scope スコープ
	 * @return bool
	 */
	public function scope_exists($scope)
	{
            return (bool) $this->model->get_scope($scope);
	}
        
	/**
	 * スコープを取得する
	 * 
	 * @param string $scope スコープ
	 * @return bool
	 */
	public function get_scope($scope)
	{
            return $this->model->get_scope($scope);	
	}
	
	/**************************************************************
	//! 他のスタッフ
	**************************************************************/
	/**
	 * クライアントコールバックURLの生成する
	 * 
	 * @param string $redirect_uri. (default: "")
	 * @param array $params. (default: array())
	 * @return string
	 */
	public function redirect_uri($redirect_uri = '', $params = array(), $query_delimeter = '?')
	{
            if (strstr($redirect_uri, $query_delimeter))
            {
                return $redirect_uri . http_build_query($params);
            }
            else
            {
                return $redirect_uri . $query_delimeter . http_build_query($params);
            }
	}
		
}