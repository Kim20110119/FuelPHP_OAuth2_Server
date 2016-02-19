<h1>セキュアなサインイン</h1>
<h2>【<?php echo $client_name; ?>】アプリケーションがアカウントに接続したいです。</h2>
		
<p>「Approve」ボタンをクリックするとアプリケーションにリダイレクトされ、アカウント情報にアクセスしアクションを実行することができます</p>
<p>「Deny」ボタンをクリックするとアプリケーションにリダイレクトされ、データの交換は行われません。</p>
			
<?php echo Form::open('oauth/authorise'); ?>
    <p>
        <input type="submit" class="button" value="Approve" name="doauth" /> or
        <input type="submit" class="button" value="Deny" name="doauth" />
    </p>
<?php echo Form::close(); ?>