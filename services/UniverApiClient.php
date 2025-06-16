<?php

namespace app\services;

use Yii;

class UniverApiClient
{
    public function authenticate($login, $password): array|null
    {
        $url = 'http://univerapi.kstu.kz/user/login?login='
            . urlencode($login) . '&password=' . urlencode($password);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);

        $response = curl_exec($ch);
        $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        Yii::info("Univer API [$status]: $response", __METHOD__);

        if ($status !== 200) {
            return null;
        }

        $data = json_decode($response, true);
        if (!is_array($data) || !isset($data['code']) || $data['code'] !== 0) {
            return null;
        }

        return $data;
    }



}
