<?php
include('../che/cleanweb.php');

if (isset($_POST['text'])) {
    $cw = new \che\cleanweb('{API_KEY}');
    
    $result = $cw->checkSpam(array('body-plain' => $_POST['text']));

    if ($result) {
        echo 'Обнаружен спам!';
    } else {
        echo 'Спам не обнаружен';
    }
}
?>

<form method="post">
    Введите текст для проверки на спам:<br/>
    <textarea name="text" rows="20" cols="40"></textarea><br/>
    <input type="submit" value="Проверить" />
</form>