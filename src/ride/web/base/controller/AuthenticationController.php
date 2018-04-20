<?php

namespace ride\web\base\controller;

use ride\library\http\Response;
use ride\library\security\exception\AuthenticationException;
use ride\library\security\exception\EmailAuthenticationException;
use ride\library\security\exception\InactiveAuthenticationException;
use ride\library\security\exception\SecurityModelNotSetException;
use ride\library\security\SecurityManager;
use ride\library\validation\exception\ValidationException;
use ride\library\validation\ValidationError;

use \Exception;

/**
 * Controller to authenticate a user with the system
 */
class AuthenticationController extends AbstractController {

    /**
     * Action to login a user with username and password authentication
     * @param \ride\library\security\SecurityManager $securityManager Instance
     * of the security manager
     * @return null
     */
    public function loginAction(SecurityManager $securityManager) {
        try {
            $user = $this->getUser();
        } catch (Exception $exception) {
            $user = null;
        }

        if ($user && !$this->response->isForbidden()) {
            $this->response->setRedirect($this->getUrl('admin'));

            return;
        }

        $translator = $this->getTranslator();

        $form = $this->createFormBuilder();
        $form->setAction('login');
        $form->setId('form-login');
        $form->addRow('username', 'string', array(
            'label' => $translator->translate('label.username'),
            'attributes' => array(
                'autofocus' => 'autofocus',
                'placeholder' => $translator->translate('label.username'),
            ),
            'validators' => array(
                'required' => array(),
            )
        ));
        $form->addRow('password', 'password', array(
            'label' => $translator->translate('label.password'),
            'attributes' => array(
                'placeholder' => $translator->translate('label.password'),
            ),
            'validators' => array(
                'required' => array(),
            )
        ));

        if ($this->response->isForbidden()) {
            $referer = $this->request->getUrl();
        } else {
            $referer = $this->getReferer($this->request->getBaseUrl());
        }

        $form = $form->build();
        if ($form->isSubmitted()) {
            try {
                $form->validate();

                $data = $form->getData();

                $securityManager->login($data['username'], $data['password']);

                $this->response->setRedirect($referer);

                return;
            } catch (InactiveAuthenticationException $exception) {
                $this->response->setStatusCode(Response::STATUS_CODE_UNPROCESSABLE_ENTITY);

                $this->addError('error.authentication.inactive');
            } catch (EmailAuthenticationException $exception) {
                $this->response->setStatusCode(Response::STATUS_CODE_UNPROCESSABLE_ENTITY);

                $username = $this->request->getBodyParameter('username');

                $url = $this->getUrl('profile.email') . '?username=' . urlencode($username) . '&referer=' . urlencode($this->request->getUrl());

                $this->addError('error.authentication.email', array('url' => $url));
            } catch (AuthenticationException $exception) {
                $this->response->setStatusCode(Response::STATUS_CODE_UNPROCESSABLE_ENTITY);

                $validationError = new ValidationError('error.authentication', 'Could not authenticate, check your credentials');

                $validationException = new ValidationException();
                $validationException->addErrors('username', array($validationError));

                $form->setValidationException($validationException);
            } catch (SecurityModelNotSetException $exception) {
                $this->response->setStatusCode(Response::STATUS_CODE_UNPROCESSABLE_ENTITY);

                $this->addError('error.security.model.not.set');
            } catch (ValidationException $validationException) {
                $this->response->setStatusCode(Response::STATUS_CODE_UNPROCESSABLE_ENTITY);

                $this->addError('error.validation');
            }
        }

        $urls = $this->config->get('system.login.url', array());
        foreach ($urls as $index => $id) {
            $urls[$index] = $this->getUrl($id) . '?logout=true&referer=' . urlencode($referer);
        }

        $this->setTemplateView('base/login', array(
            'form' => $form->getView(),
            'referer' => $referer,
            'urls' => $urls,
        ));
    }

    /**
     * Action to logout the current user.
     * @param \ride\library\security\SecurityManager $securityManager Instance
     * of the security manager
     * @return null
     */
    public function logoutAction(SecurityManager $securityManager) {
        $isSwitchedUser = $securityManager->isSwitchedUser();

        $securityManager->logout();

        if ($isSwitchedUser) {
            $url = $this->getUrl('admin');
        } else {
            $url = $this->request->getBaseUrl();
        }

        $this->response->setRedirect($url);
    }

    /**
     * Action that renders a forbidden template view
     * @return null
     */
    public function forbiddenAction() {
        $this->setTemplateView('base/forbidden');
    }

}
