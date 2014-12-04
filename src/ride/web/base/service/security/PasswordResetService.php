<?php

namespace ride\web\base\service\security;

use ride\library\security\model\User;
use ride\library\validation\exception\ValidationException;
use ride\library\validation\ValidationError;

/**
 * Service with the logic to perform a password reset
 */
class PasswordResetService extends AbstractSecurityService {

    /**
     * Path to the default template for the mail message to the user
     * @var string
     */
    const TEMPLATE = 'base/user.mail.password';

    /**
     * Lookups the user with the username or email address
     * @param string $email Email address or username to lookup
     * @param string $errorField Name of the field for the validation exception
     * @return \ride\library\security\model\User
     * @throws \ride\library\validation\exception\ValidationException
     */
    public function lookupUser($email, $errorField = 'email') {
        $error = null;

        $user = $this->securityManager->getSecurityModel()->getUserByEmail($email);
        if (!$user) {
            $user = $this->securityManager->getSecurityModel()->getUserByUsername($email);
            if (!$user) {
                $error = new ValidationError('error.user.found.email', 'Could not find the profile for email address %email%', array('email' => $email));
            }
        }

        if ($error) {
            // validation errors occured
            $exception = new ValidationException();
            $exception->addErrors($errorField, array($error));

            throw $exception;
        }

        return $user;
    }

    /**
     * Requests a new password reset for the provided user. A mail will be sent
     * to the provided user with a password reset link
     * @param \ride\library\security\model\User $user
     * @param string $subject
     * @return null
     * @throws \ride\library\validation\exception\ValidationException when the
     * user has no email address set
     */
    public function requestPasswordReset(User $user, $subject = null) {
        $email = $this->getUserEmail($user);
        if (!$subject) {
            $subject = 'Password reset requested';
        }

        $time = time();
        $user->setPreference('password.reset', $time);

        $this->securityManager->getSecurityModel()->saveUser($user);

        $this->sendMail($email, $subject, array(
            'user' => $user,
            'encryptedUsername' => $this->cipher->encrypt($user->getUserName(), $this->secretKey),
            'encryptedTime' => $this->cipher->encrypt($time, $this->secretKey),
        ));
    }

    /**
     * Gets a user based on the encrypted username and reset time
     * @return boolean|\ride\library\security\model\User
     */
    public function getUser($encryptedUsername, $encryptedTime) {
        $username = $this->cipher->decrypt($encryptedUsername, $this->secretKey);
        $time = $this->cipher->decrypt($encryptedTime, $this->secretKey);

        $user = $this->securityManager->getSecurityModel()->getUserByUsername($username);
        if (!$user) {
            return false;
        }

        if ((string) $user->getPreference('password.reset') !== $time) {
            return false;
        }

        return $user;
    }

    /**
     * Stores a new password for the user and removes the reset timestamp from
     * the user
     * @return null
     */
    public function setUserPassword(User $user, $password) {
        $user->setPassword($password);
        $user->setPreference('password.reset', null);

        $this->securityManager->getSecurityModel()->saveUser($user);

        if ($user->isActive()) {
            $this->securityManager->setUser($user);
        }
    }

}
