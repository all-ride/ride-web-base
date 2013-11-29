<?php

namespace pallo\web\base\controller;

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
     * Action to login a user with username and password authentication
     * @param pallo\library\security\SecurityManager $securityManager Instance
     * of the security manager
     * @return null
     */
    public function loginAction(SecurityManager $securityManager) {
        $loginUrl = $this->getUrl('login');
        $url = $this->request->getUrl();
        if ($url == $loginUrl) {
            $url = $this->request->getBaseUrl();
        }

        $referer = $this->request->getQueryParameter('referer', $url);

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

                $this->response->setRedirect($referer);

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
        }

        $this->setTemplateView('base/login', array(
        	'form' => $form->getView(),
            'referer' => $referer,
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

}