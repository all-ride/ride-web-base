<?php

namespace pallo\web\base\controller;

use pallo\library\http\Header;
use pallo\library\http\Response;
use pallo\library\security\exception\AuthenticationException;
use pallo\library\security\exception\SecurityModelNotSetException;
use pallo\library\security\SecurityManager;
use pallo\library\validation\exception\ValidationException;
use pallo\library\validation\ValidationError;

/**
 * Controller to authenticate a user with the system
 */
class AuthenticationController extends AbstractController {

    /**
     * Session key for the referer when cancelling the login action
     * @var string
     */
    const SESSION_REFERER_CANCEL = 'authentication.referer.cancel';

    /**
     * Session key for the referer when submitting the login action
     * @var string
     */
    const SESSION_REFERER_REQUEST = 'authentication.referer.request';

    /**
     * Action to login a user with username and password authentication
     * @param pallo\library\security\SecurityManager $securityManager Instance
     * of the security manager
     * @return null
     */
    public function loginAction(SecurityManager $securityManager) {
        $translator = $this->getTranslator();

        $form = $this->createFormBuilder();
        $form->addRow('username', 'string', array(
            'label' => $translator->translate('label.username'),
            'validators' => array(
                'required' => array(),
            )
        ));
        $form->addRow('password', 'password', array(
            'label' => $translator->translate('label.password'),
            'validators' => array(
                'required' => array(),
            )
        ));
        $form->setRequest($this->request);

        $form = $form->build();
        if ($form->isSubmitted()) {
            try {
                $form->validate();

                $data = $form->getData();

                $securityManager->login($data['username'], $data['password']);

                $this->response->setRedirect($this->getSessionReferer(self::SESSION_REFERER_REQUEST));

                return;
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
        } else {
            $this->setReferers();
        }

        $urls = $this->config->get('system.login.url', array());
        foreach ($urls as $index => $id) {
            $urls[$index] = $this->getUrl($id);
        }

        $this->setTemplateView('base/login', array(
        	'form' => $form->getView(),
            'referer' => $this->getSessionReferer(self::SESSION_REFERER_CANCEL),
            'urls' => $urls,
        ));
    }

    /**
     * Action to logout the current user.
     * @param pallo\library\security\SecurityManager $securityManager Instance
     * of the security manager
     * @return null
     */
    public function logoutAction(SecurityManager $securityManager) {
        $securityManager->logout();

        $this->response->setRedirect($this->request->getBaseUrl());
    }

    /**
     * Sets the referer to redirect to when performing a login action
     * @return null
     */
    private function setReferers() {
        $loginUrl = $this->getUrl('login');

        $cancelUrl = $this->getRequestReferer();
        if ($cancelUrl == $loginUrl) {
            $cancelUrl = $this->request->getBaseUrl();
        }

        $requestUrl = $this->request->getUrl();
        if ($requestUrl == $loginUrl) {
            if ($cancelUrl) {
                $requestUrl = $cancelUrl;
            } else {
                $requestUrl = null;
            }
        }

        $session = $this->request->getSession();
        $session->set(self::SESSION_REFERER_CANCEL, $cancelUrl);
        $session->set(self::SESSION_REFERER_REQUEST, $requestUrl);
    }

    /**
     * Gets the referer of the current request
     * @param string $default Default referer to return when there is no
     * referer set
     * @return string URL to the last page displayed
     */
    private function getRequestReferer($default = null) {
        $referer = $this->request->getQueryParameter('referer');
        if (!$referer) {
            $referer = $this->request->getHeader(Header::HEADER_REFERER);
        }

        if ($referer) {
            return $referer;
        }

        return $default;
    }

    /**
     * Gets the referer to redirect to, when not set the base URL will be
     * returned
     * @return string URL to redirect to
     */
    private function getSessionReferer($name) {
        $referer = null;

        if ($this->request->hasSession()) {
            $session = $this->request->getSession();

            $referer = $session->get($name);
        }

        if (!$referer) {
            $referer = $this->request->getBaseUrl();
        }

        return $referer;
    }

    /**
     * Clears the referers from the session
     * @return null
     */
    private function clearReferers() {
        if (!$this->request->hasSession()) {
            return;
        }

        $session = $this->request->getSession();
        $session->set(self::SESSION_REFERER_CANCEL, null);
        $session->set(self::SESSION_REFERER_REQUEST, null);
    }

}