<?php

namespace ride\web\base\profile;

use ride\library\form\FormBuilder;
use ride\library\mvc\controller\Controller;
use ride\library\security\SecurityManager;
use ride\library\validation\exception\ValidationException;
use ride\library\validation\validator\RequiredValidator;
use ride\library\validation\ValidationError;

/**
 * Profile hook implementation to change the password
 */
class PasswordProfileHook extends AbstractProfileHook {

    /**
     * Machine name of this profile hook
     * @var string
     */
    const NAME = 'password';

    /**
     * Template resource for the view of this profile hook
     * @var string
     */
    const TEMPLATE = 'base/profile.password';

    /**
     * Prepares the form by adding row definitions
     * @param \ride\library\form\FormBuilder $builder
     * @param array $options
     * @return null
     */
    public function prepareForm(FormBuilder $builder, array $options) {
        $translator = $options['translator'];

        $builder->addRow('old-password', 'password', array(
            'label' => $translator->translate('label.password.old'),
            'description' => $translator->translate('label.password.old.description'),
        ));
        $builder->addRow('new-password', 'password', array(
            'label' => $translator->translate('label.password.new'),
            'description' => $translator->translate('label.password.new.description'),
        ));
        $builder->addRow('confirm-password', 'password', array(
            'label' => $translator->translate('label.password.confirm'),
            'description' => $translator->translate('label.password.confirm.description'),
        ));
        $builder->addRow('submit-password', 'button', array(
            'label' => $translator->translate('button.update'),
            'default' => true,
        ));
    }

    /**
     * Processes the submitted values of the form
     * @param array $data Submitted values of the form
     * @param array \ride\library\mvc\controller\Controller $controller Instance
     * of the controller who is processing the request
     * @return null
     */
    public function processForm(array $data, Controller $controller) {
        if (!$data['submit-password']) {
            return;
        }

        $this->validateData($data);

        $user = $this->securityManager->getUser();

        $user->setPassword($data['new-password']);

        $this->securityManager->getSecurityModel()->saveUser($user);

        $controller->addSuccess('success.profile.password.saved');
    }

    /**
     * Validates the input
     * @param array $data Submitted values of the form
     * @return null
     * @throws \ride\library\validation\exception\ValidationException
     */
    protected function validateData(array $data) {
        $validationException = new ValidationException();
        $validator = new RequiredValidator();

        if (!$validator->isValid($data['new-password'])) {
            $validationException->addErrors('new-password', $validator->getErrors());
        }
        if (!$validator->isValid($data['old-password'])) {
            $validationException->addErrors('old-password', $validator->getErrors());
        }

        if (!$validationException->hasErrors() && $data['new-password'] != $data['confirm-password']) {
            $error = new ValidationError('error.validation.password.match', "Your passwords do not match");
            $validationException->addErrors('confirm-password', array($error));
        }

        if ($this->securityManager->hashPassword($data['old-password']) != $this->securityManager->getUser()->getPassword()) {
            $error = new ValidationError('error.validation.password.old', "Your old password is incorrect");
            $validationException->addErrors('old-password', array($error));
        }

        if ($validationException->hasErrors()) {
            throw $validationException;
        }
    }

}
