<!doctype html>	
<!--[if lt IE 7 ]><html class="no-js ie6"><![endif]-->
<!--[if IE 7 ]><html class="no-js ie7"><![endif]-->
<!--[if IE 8 ]><html class="no-js ie8"><![endif]-->
<!--[if (gte IE 9)|!(IE)]><!--><html class="no-js"><!--<![endif]-->
    <head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<title>他社メディアへのログイン</title>
    </head>

    <body lang="en">
	<h1>他社メディアへのログイン</h1>		
	<h2>ユーザー名とパスワードを入力してください。</h2>
				
	<?php echo Form::open('login/top'); ?>
            <?php if($error): ?>
                <div class="error">	
                    <strong>以下のエラーを修正してください。</strong>
                    <ul>
                    <?php foreach($error_messages as $e): ?>
                        <li><?php echo $e; ?></li>
                    <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <p>
                <label for="username">ユーザー名</label><br>
                <input type="text" id="username" name="username" placeholder="demo">
            </p>
            <p>
                <label for="password">パスワード</label><br>
                <input type="password" id="password" name="password" placeholder="demotest">
            </p>
            <p>
                <input type="submit" name="validate_user" value="Sign-in">
            </p>
	
	<?php echo Form::close(); ?>
    </body>
</html>