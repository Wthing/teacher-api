<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * This is the model class for table "profiles".
 *
 * @property int $id
 * @property string $login
 * @property string $password
 * @property string $auth_key
 *
 * @property Data[] $datas
 */
class Profile extends ActiveRecord implements IdentityInterface
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'profiles';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['login', 'password'], 'required'],
            [['login', 'password'], 'string', 'max' => 255],
            [['auth_key'], 'string', 'max' => 32],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'login' => Yii::t('app', 'Login'),
            'password' => Yii::t('app', 'Password'),
            'auth_key' => Yii::t('app', 'Auth Key'),
        ];
    }

    function saveUniverUser($login, $rawPassword)
    {
        $user = Profile::findByUsername($login);
        if (!$user) {
            $user = new Profile();
            $user->login = $login;
        }

        $user->setPassword($rawPassword);
        $user->save(false);
        return Yii::$app->user->login($user);
    }

    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password);
    }

    public function getUser()
    {
        if ($this->_user === false) {
            $class = Yii::$app->getUser()->identityClass ? : 'app\models\Profile';
            $this->_user = $class::findByUsername($this->login);
        }

        return $this->_user;
    }

    public function findByUsername($username)
    {
        return static::findOne(['login' => $username]);
    }


    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    public function setPassword($password)
    {
        $this->password = Yii::$app->security->generatePasswordHash($password);
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        return null;
    }

    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }


    public function getId()
    {
        return $this->id;
    }

    public function getAuthKey()
    {
        return $this->auth_key;
    }

    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }
}
