<?php

namespace ride\web\base\form;

use ride\library\form\component\AbstractComponent;
use ride\library\form\FormBuilder;
use ride\library\form\Form;
use ride\library\mvc\controller\Controller;
use ride\library\validation\exception\ValidationException;

use ride\web\base\profile\ProfileHook;

/**
 * Form component for the profile of a user
 */
class ProfileComponent extends AbstractComponent {

    /**
     * Loaded profile hooks
     * @var array
     */
    protected $profileHooks = array();

    /**
     * Machine name of the active profile hook
     * @var string
     */
    protected $activeProfileHook;

    /**
     * Adds a profile hook to the form component
     * @param \ride\web\base\profile\ProfileHook $profileHook Instance of the
     * profile hook
     * @return null
     */
    public function addProfileHook(ProfileHook $profileHook) {
        $this->profileHooks[$profileHook->getName()] = $profileHook;

        if (!$this->activeProfileHook) {
            $this->activeProfileHook = $profileHook->getName();
        }
    }

    /**
     * Gets the profile hooks
     * @return array Array with the machine name as key and the ProfileHook
     * instance as value
     */
    public function getProfileHooks() {
        return $this->profileHooks;
    }

    /**
     * Gets the active profile hook
     * @return string Machine name of the profile hook
     */
    public function getActiveProfileHook() {
        return $this->activeProfileHook;
    }

    /**
     * Prepares the form by adding row definitions
     * @param \ride\library\form\FormBuilder $builder
     * @param array $options
     * @return null
     */
    public function prepareForm(FormBuilder $builder, array $options) {
        foreach ($this->profileHooks as $profileHook) {
            $profileHook->prepareForm($builder, $options);
        }
    }

    /**
     * Processes the profile form
     * @param \ride\library\form\Form $form Instance of the form where this
     * component resides
     * @param  \ride\library\mvc\controller\Controller $controller Instance
     * of the controller who is processing the request
     * @return null
     */
    public function processForm(Form $form, Controller $controller) {
        $form->validate();

        $data = $form->getData();

        foreach ($this->profileHooks as $profileHookName => $profileHook) {
            try {
                $profileHook->processForm($data, $controller);
            } catch (ValidationException $exception) {
                $this->activeProfileHook = $profileHookName;

                throw $exception;
            }
        }
    }

}
