<?php
namespace che;

/**
 * Класс для работы с API Яндекс.Чистый Веб
 * 
 * Позволяет проверять различные сообщения на спам, а также предоставляет капча-сервис.
 * Перед использованием обязательно ознакомьтесь с документацией по адресу: http://api.yandex.ru/cleanweb/
 * 
 * @link https://github.com/chekalskiy/cleanweb
 * @author Ilya Chekalskiy <ilya@chekalskiy.ru>
 */
class cleanweb {
    private $apiKey;
    private $apiUrl = 'http://cleanweb-api.yandex.ru/';
    private $apiVer = '1.0';

    public function __construct($key) {
        $this->apiKey = $key;
    }

    /**
     * Проверка на спам
     * 
     * @param array $parametres Допустимые элементы: ip, email, name, login, realname, subject-plain, body-plain
     * @param boolean $returnFull Возвращать целиком ответ сервера или лишь значение спам/не спам
     * @return array|boolean
     */
    public function checkSpam($parametres = array(), $returnFull = false) {
        $possible = array('ip', 'email', 'name', 'login', 'realname', 'subject-plain', 'body-plain');
        $parametres = array_intersect_key($parametres, array_fill_keys($possible, null));

        $data = $this->fetch('check-spam', $parametres, 'POST');

        if ($data) {
            $spam_detected = (isset($data->text['spam-flag']) && $data->text['spam-flag'] == 'yes');
            if ($returnFull == false)
                return $spam_detected;

            $spam_links = array();
            if (isset($data->links)) {
                foreach ($data->links as $link) {
                    if ($link['spam-flag'] == 'yes')
                        $spam_links[] = strval($link['href']);
                }
            }

            return array( 
                'is_spam'    => $spam_detected,
                'request_id' => (isset($data->id)) ? strval($data->id) : null,
                'spam_links' => $spam_links
            );
        }

        return false;
    }

    /**
     * Получение CAPTCHA
     * 
     * @param array $parametres Допустимые элементы: type и id
     * @param boolean $forceHttps Возвращать адрес картинка в https-формате
     * @return array
     */
    public function getCaptcha($parametres = array(), $forceHttps = false) {
        $possible = array('type', 'id');
        $parametres = array_intersect_key($parametres, array_fill_keys($possible, null));

        $data = $this->fetch('get-captcha', $parametres);

        if ($data && isset($data->captcha)) {
            $captchaId = strval($data->captcha);
            $captchaUrl = strval($data->url);

            if ($forceHttps)
                $captchaUrl = str_replace('http://', 'https://', $captchaUrl);

            return array('id' => $captchaId, 'url' => $captchaUrl);
        }

        return false;
    }

    /**
     * Проверка CAPTCHA
     * 
     * @param string $captchaId 
     * @param string $captchaValue 
     * @return boolean
     */
    public function checkCaptcha($captchaId, $captchaValue) {
        $parametres = array('captcha' => $captchaId, 'value' => trim($captchaValue));

        if (empty($captchaId) || empty($captchaValue))
            return false;

        $data = $this->fetch('check-captcha', $parametres);

        if ($data && isset($data->ok)) {
            return true;
        }

        return false;
    }

    /**
     * Жалоба на решение сервиса
     * 
     * @param string $id Идентификаторы сообщений, выданные сервисом при проверке на спам, разделенные запятыми
     * @param string $spamtype Тип жалобы: spam или ham
     * @return boolean
     */
    public function complain($id, $spamtype = 'spam') {
        if (!in_array($spamtype, array('spam', 'ham')))
            $spamtype = 'spam';
        $parametres = array('id' => $id, 'spamtype' => $spamtype);

        $data = $this->fetch('complain', $parametres, 'POST');

        if ($data && isset($data->ok)) {
            return true;
        }

        return false;
    }

    private function getUrl($endpoint) {
        return $this->apiUrl . $this->apiVer . '/' . $endpoint;
    }

    private function fetch($endpoint, $parametres = array(), $method = 'GET') {
        $url = $this->getUrl($endpoint);
        $parametres = array_merge(
            array('key' => $this->apiKey),
            $parametres
        );

        if ($method == 'GET') {
            $query = http_build_query($parametres);
            $url .= '?' . $query;

            $response = @file_get_contents($url);
        } else {
            $http_options = array(
                'http' => array(
                    'method'  => 'POST',
                    'header'  => 'Content-Type: application/x-www-form-urlencoded' . PHP_EOL,
                    'content' => http_build_query($parametres)
                )
            );
            $context = stream_context_create($http_options);

            $response = @file_get_contents($url, false, $context);
        }

        if (!$response)
            return false;

        $data = new \SimpleXMLElement($response);
        return $data;
    }
}
