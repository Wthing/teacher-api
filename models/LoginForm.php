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

        $client = new UniverApiClient();
        if (!$client->authenticate($this->login, $this->password)) {
            $this->addError('password', 'Неверный логин или пароль');
            return false;
        }

        $user = (new Profile)->findByUsername($this->login);
        if (!$user) {
            $user = new Profile();
            $user->login = $this->login;
        }
        if (empty($user->auth_key)) {
            $user->generateAuthKey();
        }
        $user->setPassword($this->password);
        $user->save(false);

        if (Yii::$app->user->login($user, 3600 * 24 * 30)) {
            Yii::info("Login success", __METHOD__);
        } else {
            Yii::error("Login failed", __METHOD__);
        }


        // Авторизуем пользователя
        return Yii::$app->user->login($user, 3600 * 24 * 30); // 30 дней
    }

}
