# OAuth2.0サーバーのダミーページ(OpenID Connectプロトコル利用)

## 使うライブラリOAuth2 Server PHP参考サイト

- https://bshaffer.github.io/oauth2-server-php-docs/

## 使う認証パッケージ:【Sentry】

## 各エンドポイントの説明

- 設定ファイルのStrategyの中身を変種する。プロバイダ名を指定する  
  【Authorizationエンドポイント】：http://××××××××/fuelphp/oauth/index（aUrl）  
  【Tokenエンドポイント】：http://××××××××/oauth/access_token（tUrl）  
  【UserInfoエンドポイント】：http://××××××××/oauth/attribute(uUrl)  
   
## ダミーページ流れ

- 認可要求を受け取る処理
- 認可結果を返す処理
- 認証要求を受け取る処理
- ユーザーを認証する処理
- ユーザーに認証連携の同意を得る処理
- 認証結果を返す処理
- アクセストークンとユーザー識別子によりユーザー情報を返す処理

## OAuth2.0　Protocol仕様

- http://openid-foundation-japan.github.io/draft-ietf-oauth-v2.ja.html

## OpenID Connect仕様

- http://www.openid.or.jp/document/
