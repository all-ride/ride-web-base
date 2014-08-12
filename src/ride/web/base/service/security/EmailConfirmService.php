<?php

namespace ride\web\base\service\security;

use ride\library\security\model\User;

/**
 * Service with the logic to confirm a user's email address
 */
class EmailConfirmService extends AbstractSecurityService {

    /**
     * Path to the default template for the mail message to the user
     * @var string
     */
    const TEMPLATE = 'base/user.mail.email';

    /**
     * Gets a user by it's username
     * @param string $username
     * @return \ride\library\security\model\User|null
     */
    public function getUser($username) {
        $securityModel = $this->securityManager->getSecurityModel();

        $user = $securityModel->getUserByUsername($username);
        if (!$user) {
            return false;
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
    public function sendConfirmation(User $user, $subject = null) {
        $email = $this->getUserEmail($user);
        if (!$subject) {
            $subject = 'Confirm email address';
        }

        $this->sendMail($email, $subject, array(
            'user' => $user,
            'encryptedUsername' => $this->cipher->encrypt($user->getUserName(), $this->secretKey),
            'encryptedEmail' => $this->cipher->encrypt($email, $this->secretKey),
        ));
    }

    /**
     * Gets a user based on the encrypted username and reset time
     * @return boolean|\ride\library\security\model\User
     */
    public function confirmEmailAddress($encryptedUsername, $encryptedEmail) {
        $securityModel = $this->securityManager->getSecurityModel();

        $username = $this->cipher->decrypt($encryptedUsername, $this->secretKey);

        $user = $securityModel->getUserByUsername($username);
        if (!$user) {
            return false;
        }

        $email = $this->cipher->decrypt($encryptedEmail, $this->secretKey);
        if ($email !== $user->getEmail()) {
            return false;
        }

        $user->setIsEmailConfirmed(true);

        $securityModel->saveUser($user);

        return $user;
    }

}
