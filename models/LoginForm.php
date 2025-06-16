<?php

namespace app\models;

use app\services\UniverApiClient;
use Yii;
use yii\base\Model;

/**
 * LoginForm is the model behind the login form.
 *
 * @property-read User|null $user
 *
 */
class LoginForm extends Model
{
    public $login;
    public $password;

    public function rules()
    {
        return [
            [['login', 'password'], 'required'],
        ];
    }

    public function login(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        $client  = new \app\services\UniverApiClient();
        $apiData = $client->authenticate($this->login, $this->password);

        if ($apiData === null) {
            $this->addError('password', 'Неверный логин или пароль');
            return false;
        }

        $externalId = (int)$apiData['userId'];
        /* === ищем или создаём пользователя по внешнему ID === */
        $user = \app\models\User::findOne($externalId);
        if ($user === null) {
            $user         = new \app\models\User();
            $user->id     = $externalId;      // важный момент — задаём ID вручную
            $user->status = 10;
        }

        $user->username = $this->login;

        if (empty($user->auth_key)) {
            $user->generateAuthKey();
        }
        $user->setPassword($this->password);
        $user->save(false);

        /* === логиним === */
        $loggedIn = Yii::$app->user->login($user, 3600 * 24 * 30); // 30 дней

        Yii::info($loggedIn ? 'Login success' : 'Login failed', __METHOD__);

        return $loggedIn;
    }

}
