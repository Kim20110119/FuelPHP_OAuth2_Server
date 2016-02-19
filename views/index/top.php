<!DOCTYPE html>
<html lang="en">
    <head>
        <meta http-equiv='Pragma' content='no-cache' charset="UTF-8">
        <META HTTP-EQUIV="Expires" CONTENT="-1">
        <meta charset="UTF-8">
        <title></title>
    </head>
    <body>
        <h1>【<?php echo $user_name; ?>】いらっしゃいませ！</h1>
        <a href="../uploads">素材アップロード画面</a></li>
        <br/>
        <br/>
        <a href="../videos">動画リスト画面</a></li>
        <br/>
        <br/>
        <a href="http://locus-vm/oauth/testsso">Fast Videoへ</a></li>
        <?php echo Form::open('login/logout'); ?>
            <p>
                <input type="submit" name="ログアウト" value="ログアウト">
            </p>
        <?php echo Form::close(); ?>
    </body>
</html>