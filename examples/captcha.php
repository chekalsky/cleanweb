<?php
include('../che/cleanweb.php');

$cw = new \che\cleanweb('{API_KEY}');
$captcha = $cw->getCaptcha();

$id = $captcha['id'];
$url = $captcha['url'];

if (isset($_POST['captcha'])) {
    $captchaId = $_POST['id'];
    $captchaValue = $_POST['captcha'];

    $result = $cw->checkCaptcha($captchaId, $captchaValue);

    if ($result) {
        echo 'Капча отгадана!';
    } else {
        echo 'Капча введена неверно';
    }
}
?>

<form method="post">
    <input type="hidden" name="id" value="<?php echo $id; ?>" />
    Введите цифры с картинки:<br/>
    <img src="<?php echo $url; ?>" /><br/>
    <input type="text" name="captcha" /><br/>
    <input type="submit" value="Проверить" />
</form>