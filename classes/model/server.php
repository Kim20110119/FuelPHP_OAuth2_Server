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

abstract class Model_Server
{
    /* OAuthクライアント情報の取得処理 */
    abstract public function get_client(array $where);

    /* OAuthセッション情報の取得処理 */
    abstract public function get_session(array $where);
    /* OAuthセッション情報の登録処理 */
    abstract public function new_session(array $values, array $scopes);
    /* OAuthセッション情報の更新処理 */
    abstract public function update_session(array $where, array $values);
    /* OAuthセッション情報の削除処理 */
    abstract public function delete_session(array $where);

    /* アクセストークンの所得処理 */
    abstract public function get_token_from_session($session_id);
    /* アクセストークンの登録処理 */
    abstract public function create_access_token($session_id);
    /* 認証の検証処理 */
    abstract public function has_user_authenicated_client($user_id, $client_id);
    /* OAuthスコープの検証処理 */
    abstract public function has_scope($access_token, $scope);
}