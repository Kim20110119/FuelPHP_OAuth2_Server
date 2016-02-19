<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>素材アップロード</title>
</head>
<body>
    <h1>ファイルリスト</h1>
    <form action='uploads/upload' method='POST'>
        <table>
        <?php foreach ($files as $idx => $file): ?>
            <?php if ($idx % 5 == 0) echo '<tr>'; ?>
            <td>
                <input type='checkbox' name='files[]'
                    value="<?php echo $file; ?>">
                <?php echo $file; ?><br/>
                <img src="<?php echo '/public/files/'.$file;?>"
                    width='80' height='80'/>
            </td>
            <?php if ($idx % 5 == 4) echo '</tr>'; ?>
        <?php endforeach ?>
        </table>
        <input type='submit' value='Upload!'/>
    </form>
</body>
</html>
