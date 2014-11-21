<?php

namespace ride\web\base\service\security;

use ride\library\encryption\cipher\Cipher;
use ride\library\mail\transport\Transport;
use ride\library\security\model\User;
use ride\library\security\SecurityManager;
use ride\library\validation\exception\ValidationException;
use ride\library\validation\ValidationError;

use ride\web\base\service\security\mail\MailRenderer;

/**
 * Abstract base implementation for a security service
 */
abstract class AbstractSecurityService {

    /**
     * Instance of the security manager
     * @var \ride\library\security\SecurityManager
     */
    protected $securityManager;

    /**
     * Instance of the cipher to user for encrypting username and timestamp
     * @var \ride\library\encryption\cipher\Cipher
     */
    protected $cipher;

    /**
     * Secret for the encryption
     * @var string
     */
    protected $secretKey;

    /**
     * Instance of the mail transport
     * @var \ride\library\mail\transport\Transport
     */
    protected $mailTransport;

    /**
     * Instance of the mail renderer
     * @var \ride\web\base\service\security\mail\MailRenderer
     */
    protected $mailRenderer;

    /**
     * Constructs a new password reset service
     * @param \ride\library\security\SecurityManager $securityManager
     * @param \ride\library\encryption\cipher\Cipher $cipher
     * @param string $secretKey
     * @param \ride\library\mail\transport\Transport $transport
     * @param \ride\web\base\service\TemplateService $templateService
     * @param string $template
     * @return null
     */
    public function __construct(SecurityManager $securityManager, Cipher $cipher, $secretKey, Transport $mailTransport, MailRenderer $mailRenderer = null) {
        $this->securityManager = $securityManager;
        $this->cipher = $cipher;
        $this->secretKey = $secretKey;

        $this->mailTransport = $mailTransport;
        $this->mailRenderer = $mailRenderer;
    }

    /**
     * Sets the renderer for the mail message
     * @param \ride\web\base\service\security\mail\MailRenderer $mailRenderer
     * @return null
     */
    public function setMailRenderer($mailRenderer) {
        $this->mailRenderer = $mailRenderer;
    }

    /**
     * Gets the renderer for the mail message
     * @return \ride\web\base\service\security\mail\MailRenderer
     */
    public function getMailRenderer() {
        return $this->mailRenderer;
    }

    /**
     * Gets the email address of the provided user
     * @param \ride\library\security\model\User $user
     * @return string Email address of the user
     * @throws \ride\library\validation\exception\ValidationException when no
     * email address set to the user
     */
    protected function getUserEmail(User $user) {
        $email = $user->getEmail();
        if ($email) {
            return $email;
        }

        $error = new ValidationError('error.user.email.none', 'No email address set in the user profile');

        $exception = new ValidationException();
        $exception->addErrors('unexistant', array($error));

        throw $exception;
    }

    /**
     * Renders and sends a mail
     * @param string $email Recipient of the mail
     * @param string $subject Subject for the mail
     * @param array $variables Template variables for the body of the mail
     * @return null
     */
    protected function sendMail($email, $subject, array $variables) {
        if (!$this->mailRenderer) {
            throw new Exception('Could not send the mail: no mail renderer set');
        }

        $message = $this->mailRenderer->renderMail($variables);

        $mail = $this->mailTransport->createMessage();
        $mail->setTo($email);
        $mail->setSubject($subject);
        $mail->setMessage($message);
        $mail->setIsHtmlMessage(true);

        $this->mailTransport->send($mail);
    }

}
