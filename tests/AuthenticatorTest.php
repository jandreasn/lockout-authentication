<?php

use LockoutAuthentication\Authenticator;

class AuthenticatorTest extends PHPUnit_Framework_TestCase
{
    private $authenticator;
    private $mockAuthenticatableUser;
    private $password;
    private $passwordHash;

    public function setUp()
    {
        $this->authenticator = new Authenticator([
            'hashAlgorithm' => PASSWORD_DEFAULT,
            'hashOptions' => ['cost' => 9],
            'attemptsBeforeLockout' => 2,
            'lockoutClearTime' => 600,
        ]);

        $this->password = '123';
        $this->passwordHash = $this->authenticator->createPasswordHash($this->password);

        $this->mockAuthenticatableUser = $this->getMock('\LockoutAuthentication\AuthenticatableUserInterface');
        $this->mockAuthenticatableUser->expects($this->any())
            ->method('getPasswordHash')
            ->will($this->returnValue($this->passwordHash));
    }

    /**
     * @covers LockoutAuthentication\Authenticator::__construct
     * @covers LockoutAuthentication\Authenticator::authenticate
     * @covers LockoutAuthentication\Authenticator::isLoginBlocked
     * @covers LockoutAuthentication\Authenticator::shouldLockoutBeCleared
     */
    public function testAuthenticateBlocked()
    {
        $this->mockAuthenticatableUser->expects($this->any())
            ->method('getLoginBlockedUntilTime')
            ->will($this->returnValue(time() + 10));

        $result = $this->authenticator->authenticate($this->mockAuthenticatableUser, $this->password);
        $this->assertFalse($result);
        $this->assertTrue($this->authenticator->isLoginBlocked($this->mockAuthenticatableUser));
    }

    /**
     * @covers LockoutAuthentication\Authenticator::authenticate
     * @covers LockoutAuthentication\Authenticator::clearLockout
     */
    public function testAuthenticateSuccess()
    {
        $result = $this->authenticator->authenticate($this->mockAuthenticatableUser, $this->password);
        $this->assertTrue($result);
    }

    /**
     * @covers LockoutAuthentication\Authenticator::authenticate
     * @covers LockoutAuthentication\Authenticator::createPasswordHash
     */
    public function testAuthenticateSuccessWithRehash()
    {
        $newAuthenticator = new Authenticator(['hashOptions' => ['cost' => 5]]); // Create new authenticator with options so that rehash code will be run
        $result = $newAuthenticator->authenticate($this->mockAuthenticatableUser, $this->password);
        $this->assertTrue($result);
    }

    /**
     * @covers LockoutAuthentication\Authenticator::authenticate
     * @covers LockoutAuthentication\Authenticator::shouldLockoutBeCleared
     * @covers LockoutAuthentication\Authenticator::addFailedLoginAttempt
     */
    public function testAuthenticateFail()
    {
        $this->mockAuthenticatableUser->expects($this->any())
            ->method('getFailedLoginAttempts')
            ->will($this->returnValue(5));

        $result = $this->authenticator->authenticate($this->mockAuthenticatableUser, 'invalid-password');
        $this->assertFalse($result);
    }

    /**
     * @covers LockoutAuthentication\Authenticator::createPasswordHash
     */
    public function testCreatePasswordHashFail()
    {
        // Try to authenticate with non existent algorithm
        $authenticatior = new Authenticator(['hashAlgorithm' => 123]);
        $this->setExpectedException('\RuntimeException');
        $authenticatior->createPasswordHash($this->password);
    }


}
