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

        $client  = new UniverApiClient();
        $apiData = $client->auth($this->login, $this->password);

        if ($apiData === null) {
            $this->addError('password', 'Неверный логин или пароль');
            return false;
        }

        $externalId = (int)$apiData['userId'];

        $user = User::findOne($externalId);
        if ($user === null) {
            $user         = new User();
            $user->created_at = time();
            $user->id     = $externalId;
            $user->status = 10;
        }

        $user->username = $this->login;

        if (empty($user->auth_key)) {
            $user->generateAuthKey();
        }
        $user->setPassword($this->password);
        $user->updated_at = time();
        $user->save(false);

        /* === логиним === */
        $loggedIn = Yii::$app->user->login($user, 3600 * 24 * 30);

        Yii::info($loggedIn ? 'Login success' : 'Login failed', __METHOD__);

        return $loggedIn;
    }


}
