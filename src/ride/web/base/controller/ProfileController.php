<?php

namespace ride\web\base\controller;

use ride\library\http\Response;
use ride\library\security\exception\UnauthorizedException;
use ride\library\validation\exception\ValidationException;

use ride\web\base\form\ProfileComponent;
use ride\web\base\form\PasswordRequestComponent;
use ride\web\base\form\PasswordResetComponent;
use ride\web\base\service\security\EmailConfirmService;
use ride\web\base\service\security\PasswordResetService;

/**
 * Controller to handle a user's profile
 */
class ProfileController extends AbstractController {

    /**
     * Action to show and process the user's profile page
     * @param \ride\web\base\form\ProfileComponent $profileComponent
     * @return null
     */
    public function indexAction(ProfileComponent $profileComponent) {
        $user = $this->getUser();
        if (!$user) {
            throw new UnauthorizedException();
        }

        $profileHooks = $profileComponent->getProfileHooks();

        $form = $this->buildForm($profileComponent);
        if ($form->isSubmitted()) {
            try {
                $profileComponent->processForm($form, $this);

                if (!$this->response->getView() && !$this->response->willRedirect()) {
                    $this->response->setRedirect($this->request->getUrl());
                }

                return;
            } catch (ValidationException $exception) {
                $this->setValidationException($exception, $form);
            }
        }

        $referer = $this->request->getQueryParameter('referer');
        $arguments = array(
            'form' => $form->getView(),
            'activeHook' => $profileComponent->getActiveProfileHook(),
            'referer' => $referer,
        );

        $view = $this->setTemplateView('base/profile', $arguments, 'profile');
        $view->setProfileHooks($profileHooks);

        $form->processView($view);
    }

    /**
     * Action to send out a user's email address confirmation
     * @param \ride\web\base\service\EmailConfirmService $service
     * @return null
     */
    public function sendEmailConfirmationAction(EmailConfirmService $service) {
        $username = $this->request->getQueryParameter('username');
        if ($username) {
            $user = $service->getUser($username);
            if (!$user) {
                $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

                return;
            }
        } else {
            $user = $this->getUser();
            if (!$user) {
                throw new UnauthorizedException();
            }
        }

        $service->sendConfirmation($user);

        $this->addWarning('warning.user.email.confirm');

        $referer = $this->request->getQueryParameter('referer');
        if (!$referer) {
            $referer = $this->getUrl('profile');
        }

        $this->response->setRedirect($referer);
    }

    /**
     * Action to confirm a user's email address
     * @param \ride\web\base\service\EmailConfirmService $service
     * @param string $user Encrypted username
     * @param string $email Encrypted email address
     * @return null
     */
    public function confirmEmailAction(EmailConfirmService $service, $user, $email) {
        if (!$service->confirmEmailAddress($user, $email)) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        $this->addSuccess('success.user.email.confirmed');

        $referer = $this->request->getQueryParameter('referer');
        if (!$referer) {
            $user = $this->getUser();
            if ($user) {
                $url = $this->getUrl('profile');
            } else {
                $url = $this->getUrl('login');
            }
        }

        $this->response->setRedirect($url);
    }

    /**
     * Action to request a password reset
     * @param \ride\web\base\service\PasswordResetService $service
     * @return null
     */
    public function passwordRequestAction(PasswordResetService $service) {
        $form = $this->createFormBuilder();
        $form->setId('form-password-request');
        $form->addRow('email', 'email', array(
            'label' => $this->getTranslator()->translate('label.email'),
            'validators' => array(
                'required' => array(),
            ),
        ));
        $form = $form->build();

        if ($form->isSubmitted()) {
            try {
                $form->validate();

                $data = $form->getData();

                $user = $service->lookupUser($data['email']);
                if ($user) {
                    $service->requestPasswordReset($user);

                    $this->addSuccess('success.user.password.mail');
                }

                $referer = $this->request->getQueryParameter('referer');
                if (!$referer) {
                    $referer = $this->request->getBaseUrl();
                }

                $this->response->setRedirect($referer);

                return;
            } catch (ValidationException $exception) {
                $this->setValidationException($exception, $form, null);
            }
        }

        $view = $this->setTemplateView('base/password.request', array(
            'form' => $form->getView(),
            'referer' => $this->request->getQueryParameter('referer'),
        ));

        $form->processView($view);
    }

    /**
     * Action to set a new password
     * @param \ride\web\base\service\PasswordResetService $service
     * @param string $user Encrypted username
     * @param string $time Encrypted timestamp of the password reset request
     * @return null
     */
    public function passwordResetAction(PasswordResetService $service, $user, $time) {
        $user = $service->getUser($user, $time);
        if (!$user) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        $form = $this->createFormBuilder();
        $form->setId('form-password-reset');
        $form->addRow('user', 'component', array(
            'component' => new PasswordResetComponent(),
            'embed' => true,
        ));
        $form = $form->build();

        if ($form->isSubmitted()) {
            try {
                $form->validate();

                $data = $form->getData();

                $service->setUserPassword($user, $data['user']['password']);

                $this->addSuccess('success.user.password.reset');

                $referer = $this->request->getQueryParameter('referer');
                if (!$referer) {
                    $referer = $this->request->getBaseUrl();
                }

                $this->response->setRedirect($referer);

                return;
            } catch (ValidationException $exception) {
                $this->setValidationException($exception, $form, null);
            }
        }

        $view = $this->setTemplateView('base/password.reset', array(
            'form' => $form->getView(),
            'referer' => $this->request->getQueryParameter('referer'),
        ));

        $form->processView($view);
    }

}
