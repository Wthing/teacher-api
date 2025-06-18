<?php

namespace app\models;

use Yii;
use yii\data\ActiveDataProvider;

/**
 * This is the model class for table "user".
 *
 * @property int $id
 * @property string $username
 * @property string $auth_key
 * @property string $password_hash
 * @property string|null $password_reset_token
 * @property string|null $email
 * @property int $status
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Data[] $datas
 * @property FormConfirmApplication[] $formConfirmApplications
 * @property FormConfirmApplication[] $formConfirmApplications0
 * @property FormConfirmPerson[] $formConfirmPeople
 */
class User extends \yii\db\ActiveRecord implements \yii\web\IdentityInterface
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['password_reset_token', 'email'], 'default', 'value' => null],
            [['status'], 'default', 'value' => 10],
            [['id', 'username', 'auth_key', 'password_hash', 'created_at', 'updated_at'], 'required'],
            [['id', 'status', 'created_at', 'updated_at'], 'integer'],
            [['username', 'auth_key'], 'string', 'max' => 32],
            [['password_hash', 'password_reset_token', 'email'], 'string', 'max' => 255],
            [['id'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'username' => Yii::t('app', 'Username'),
            'auth_key' => Yii::t('app', 'Auth Key'),
            'password_hash' => Yii::t('app', 'Password Hash'),
            'password_reset_token' => Yii::t('app', 'Password Reset Token'),
            'email' => Yii::t('app', 'Email'),
            'status' => Yii::t('app', 'Status'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * Gets query for [[Datas]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getDatas()
    {
        return $this->hasMany(Data::class, ['user_id' => 'id']);
    }

    /**
     * Gets query for [[FormConfirmApplications]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFormConfirmApplications()
    {
        return $this->hasMany(FormConfirmApplication::class, ['assigned_to' => 'id']);
    }

    /**
     * Gets query for [[FormConfirmApplications0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFormConfirmApplications0()
    {
        return $this->hasMany(FormConfirmApplication::class, ['created_by' => 'id']);
    }

    /**
     * Gets query for [[FormConfirmPeople]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFormConfirmPeople()
    {
        return $this->hasMany(FormConfirmPerson::class, ['user_id' => 'id']);
    }

    public function search($params)
    {
        /* @var $query \yii\db\ActiveQuery */
        $class = Yii::$app->getUser()->identityClass ? : 'app\models\User';
        $query = $class::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);
        if (!$this->validate()) {
            $query->where('1=0');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'status' => $this->status,
        ]);

        $query->andFilterWhere(['like', 'username', $this->username])
            ->andFilterWhere(['like', 'email', $this->email]);

        return $dataProvider;
    }

    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
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
