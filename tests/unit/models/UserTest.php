<?php

namespace tests\unit\models;

use app\models\User;

class UserTest extends \Codeception\Test\Unit
{
    public function testFindUserById()
    {
        verify($user = User::findIdentity(100))->notEmpty();
        verify($user->username)->equals('admin-form');

        verify(User::findIdentity(999))->empty();
    }

    public function testFindUserByAccessToken()
    {
        verify($user = User::findIdentityByAccessToken('100-token'))->notEmpty();
        verify($user->username)->equals('admin-form');

        verify(User::findIdentityByAccessToken('non-existing'))->empty();        
    }

    public function testFindUserByUsername()
    {
        verify($user = User::findByUsername('admin-form'))->notEmpty();
        verify(User::findByUsername('not-admin-form'))->empty();
    }

    /**
     * @depends testFindUserByUsername
     */
    public function testValidateUser()
    {
        $user = User::findByUsername('admin-form');
        verify($user->validateAuthKey('test100key'))->notEmpty();
        verify($user->validateAuthKey('test102key'))->empty();

        verify($user->validatePassword('admin-form'))->notEmpty();
        verify($user->validatePassword('123456'))->empty();        
    }

}
