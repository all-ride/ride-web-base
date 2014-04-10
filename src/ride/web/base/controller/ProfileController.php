<?php

namespace ride\web\base\controller;

use ride\library\security\exception\UnauthorizedException;
use ride\library\validation\exception\ValidationException;

use ride\web\base\form\ProfileComponent;

class ProfileController extends AbstractController {

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
    }

}
