<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ビデオ一覧</title>
</head>
<body>
    <h1>ビデオ一覧</h1>
<?php foreach ($videolist as $video): ?>
    <table border='1'>
        <tr><td>ビデオ番号</td><td><?php echo $video['no']; ?></td></tr>
        <tr><td>タイトル</td><td><?php echo $video['title']; ?></td></tr>
        <tr><td>サイズ</td><td><?php echo $video['size']; ?></td></tr>
        <tr><td>サムネイル</td><td>
            <img height='100' width='100' src="<?php echo $video['imgurl']; ?>"/>
        </td></tr>
        <tr><td>ダウンロード</td><td>
                <a href="<?php echo $video['videourl']; ?>">Download Here</a>
        </td></tr>
    </table>
    <br/>
<?php endforeach ?>
</body>
</html>
