<?php

namespace app\services;

use Yii;

class UniverApiClient
{
    public function authenticate($login, $password): bool
    {
        // Формируем URL с GET-параметрами
        $url = 'http://univerapi.kstu.kz/user/login?login=' . urlencode($login) . '&password=' . urlencode($password);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        // Убираем POST, т.к. нужен GET-запрос
        // curl_setopt($ch, CURLOPT_POST, true);
        // curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        //     'login' => $login,
        //     'password' => $password,
        // ]));

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        Yii::info("Univer API [$status]: $response", __METHOD__);

        if ($status !== 200) {
            return false;
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            return false;
        }

        return isset($data['message']) && stripos($data['message'], 'успешно') !== false;
    }



}
