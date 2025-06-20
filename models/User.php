<?php

namespace app\models;

use Yii;
use yii\data\ActiveDataProvider;
use mdm\admin\models\User as BaseUser;

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
class User extends BaseUser
{

}
