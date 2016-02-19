<?php
/**
 * OAuth2 Server Model
 * 
 * @package    FuelPHP/OAuth2
 * @category   Server Model
 * @author     Phil Sturgeon
 * @copyright  (c) 2012 HappyNinjas Ltd
 */

namespace OAuth2;

class Model_Server_DB extends Model_Server
{
    const TABLE_CLIENT = 'oauth_clients';                 // 【クライアント】テーブル
    const TABLE_SESSIONS = 'oauth_sessions';              // 【セッション】テーブル
    const TABLE_SESSION_SCOPES = 'oauth_session_scopes';  // 【セッションスコープ】テーブル
    const TABLE_SCOPES = 'oauth_scopes';                  // 【スコープ】テーブル

    
    /**
     * 条件によりクライアント情報を取得
     * 
     * @access public
     * @param array $where 検索条件
     * @return 「clients」オブジェクト/false
     */
    public function get_client(array $where)
    {
        $clients = \DB::select('name', 'client_id', 'auto_approve', 'redirect_uri')
                ->from(static::TABLE_CLIENT)
                ->where($where)
                ->limit(1)
                ->as_object()
                ->execute();

        return isset($clients[0]) ? $clients[0] : false;
    }

    /**
     * 条件によりセッション情報を取得
     * 
     * @access public
     * @param array $where 検索条件
     * @return 「session」オブジェクト/false
     */
    public function get_session(array $where)
    {
        $session = \DB::select('id', 'type_id')
                ->from(static::TABLE_SESSIONS)
                ->where($where)
                ->limit(1)
                ->as_object()
                ->execute();

        return isset($session[0]) ? $session[0]->id : false;
    }

    /**
     * 条件によりタイプIDを取得
     * 
     * @access public
     * @param array $where 検索条件
     * @return 「session」オブジェクト/false
     */
    public function get_type_id(array $where)
    {
        $session = \DB::select('id', 'type_id')
                ->from(static::TABLE_SESSIONS)
                ->where($where)
                ->limit(1)
                ->as_object()
                ->execute();

        return isset($session[0]) ? $session[0]->type_id : false;
    }
    
    /**
     * セッションIDにより【oauth_sessions】テーブルからトークン情報を取得
     * 
     * @access public
     * @param array $session_id セッションID
     * @return 「tokens」オブジェクト/false
     */
    public function get_token_from_session($session_id)
    {
        $tokens = \DB::select('access_token')
                ->where('id', $session_id)
                ->where('access_token', '!=', null)
                ->from(static::TABLE_SESSIONS)
                ->limit(1)
                ->as_object()
                ->execute();

        return isset($tokens[0]) ? $tokens[0]->access_token : false;
    }

    /**
     * ユーザーIDとクライアントIDにより【oauth_sessions】テーブルからアクセストークンを取得
     * 
     * @access public
     * @param array $user_id ユーザーID
     * @param array $client_id クライアントID
     * @return 「tokens」オブジェクト/false
     */
    public function has_user_authenicated_client($user_id, $client_id)
    {
        $tokens = \DB::select('access_token')
                ->where('client_id', $client_id)
                ->where('type_id', $user_id)
                ->where('type', 'user')
                ->where('access_token', '!=', '')
                ->where('access_token', '!=', null)
                ->from(static::TABLE_SESSIONS)
                ->limit(1)
                ->as_object()
                ->execute();

        return isset($tokens[0]) ? $tokens[0]->access_token : false;
    }

    /**
     * アクセストークンとスコープの検証処理
     * 
     * @access public
     * @param array $access_token アクセストークン
     * @param array $scope スコープ
     * @return true/false
     */
    public function has_scope($access_token, $scope)
    {
        $has_any = \DB::select('id')
                ->where('access_token', $access_token)
                ->where('scope', $scope)
                ->from(static::TABLE_SESSION_SCOPES)
                ->execute()
                ->as_array();

        return (bool) $has_any;
    }

    /**
     * セッション情報とセッションスコープ情報の登録処理
     * 
     * @access public
     * @param array $values 登録するセッション情報
     * @param array $scopes スコープ
     * @return void
     */
    public function new_session(array $values, array $scopes)
    {
        // セッション情報を登録する
        $result = \DB::insert(static::TABLE_SESSIONS)->set($values)->execute();

        // 登録したセッションIDを取得する
        $session_id = $result[0];

        // セッションスコープ情報を登録する
        foreach ($scopes as $scope)
        {
            if (trim($scope) !== "")
            {
                \DB::insert(static::TABLE_SESSION_SCOPES)
                        ->set(array(
                            'session_id' => $session_id,
                            'scope' => $scope
                        ));
            }
        }
    }

    /**
     * セッション情報の更新処理
     * 
     * @access public
     * @param array $where 更新条件
     * @param array $values 更新情報
     * @return void
     */
    public function update_session(array $where, array $values)
    {
        return \DB::update(static::TABLE_SESSIONS) ->set($values)->where($where)->execute();
    }

    /**
     * アクセストークンの登録処理
     * 
     * @access public
     * @param array $session_id セッションID
     * @return $access_token アクセストークン
     */
    public function create_access_token($session_id)
    {
        // アクセストークン
        $access_token = sha1(time().uniqid());

        // OAuthセッションを更新する
        $this->update_session(
                array('id' => $session_id),
                array(
                    'code' => NULL,
                    'access_token' => $access_token,
                    'last_updated' => time(),
                    'stage' => 'granted'
                    )
                );

        // OAuthセッションスコープを更新する
        \DB::update(static::TABLE_SESSION_SCOPES)
                ->where('session_id', $session_id)
                ->set(array('access_token' => $access_token))
                ->execute();

        return $access_token;
    }

    /**
     * OAuthセッションの登録処理
     * 
     * @access public
     * @param array $where 削除条件
     * @return void
     */
    public function delete_session(array $where)
    {
        return \DB::delete(static::TABLE_SESSIONS)
                ->where($where)
                ->execute();
    }

    /**
     * スコープの検証処理
     * 
     * @access public
     * @param array $scope スコープ
     * @return Object/False
     */
    public function get_scope($scope)
    {
        $query = \DB::select()->from(static::TABLE_SCOPES);

        if (is_array($scope))
        {
            return $query
                    ->where('scope', 'IN', (array) $scope)
                    ->execute()
                    ->as_array();
        }
        else
        {
            $details = $query
                    ->where('scope', '=', $scope)
                    ->limit(1)
                    ->execute()
                    ->as_array();

            return $details ? current($details) : false;
        }
    }
}