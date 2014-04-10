<?php

namespace ride\web\base\profile;

use ride\library\form\FormBuilder;
use ride\library\mvc\controller\Controller;
use ride\library\security\SecurityManager;

/**
 * Profile hook implementation to update general account settings
 */
class AccountProfileHook extends AbstractProfileHook {

    /**
     * Machine name of this profile hook
     * @var string
     */
    const NAME = 'account';

    /**
     * Template resource for the view of this profile hook
     * @var string
     */
    const TEMPLATE = 'base/profile.account';

    /**
     * Prepares the form by adding row definitions
     * @param \ride\library\form\FormBuilder $builder
     * @param array $options
     * @return null
     */
    public function prepareForm(FormBuilder $builder, array $options) {
        $this->fileBrowser = $options['fileBrowser'];

        $translator = $options['translator'];
        $user = $this->securityManager->getUser();

        $builder->addRow('username', 'label', array(
            'label' => $translator->translate('label.username'),
            'description' => $translator->translate('label.username.profile.description'),
            'default' => $user->getUserName(),
        ));
        $builder->addRow('name', 'string', array(
            'label' => $translator->translate('label.name.profile'),
            'description' => $translator->translate('label.name.profile.description'),
            'default' => $user->getDisplayName(),
            'filters' => array(
                'trim' => array(),
            ),
            'validators' => array(
                'required' => array(),
            ),
        ));
        $builder->addRow('email', 'email', array(
            'label' => $translator->translate('label.email'),
            'description' => $translator->translate('label.email.profile.description'),
            'default' => $user->getEmail(),
            'filters' => array(
                'trim' => array(),
            ),
        ));
        $builder->addRow('image', 'image', array(
            'label' => $translator->translate('label.image'),
            'description' => $translator->translate('label.image.profile.description'),
            'default' => $user->getImage(),
            'path' => $this->fileBrowser->getApplicationDirectory()->getChild('data/upload/profile')->getAbsolutePath(),
        ));
        $builder->addRow('submit-account', 'button', array(
            'label' => $translator->translate('button.update'),
            'default' => true,
        ));
        $builder->addRow('submit-unregister', 'button', array(
            'label' => $translator->translate('button.unregister'),
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
        if ($data['submit-account']) {
            $user = $this->securityManager->getUser();

            $oldImage = $user->getImage();
            if ($data['image'] && $oldImage && $data['image'] != $oldImage) {
                $oldImage = $this->fileBrowser->getFile($oldImage);
                if ($oldImage && $oldImage->exists()) {
                    $oldImage->delete();
                }
            }

            $user->setDisplayName($data['name']);
            $user->setEmail($data['email']);
            $user->setImage($data['image']);

            $this->securityManager->getSecurityModel()->saveUser($user);

            $controller->addSuccess('success.profile.account.saved');
        } elseif ($data['submit-unregister']) {
            $user = $this->securityManager->getUser();

            $this->securityManager->getSecurityModel()->deleteUser($user);
            $this->securityManager->logout();

            $controller->addSuccess('success.unregistered');
            $controller->getResponse()->setRedirect($controller->getRequest()->getBaseUrl());
        }
    }

}
