<?php

namespace ride\web\base\profile;

use ride\library\form\FormBuilder;
use ride\library\mvc\controller\Controller;
use ride\library\security\SecurityManager;

/**
 * Interface for the profile form hook
 */
interface ProfileHook {

    /**
     * Gets the machine name of this profile hook
     * @return string
     */
    public function getName();

    /**
     * Sets the security manager
     * @param \ride\library\security\SecurityManager $securityManager Instance
     * of the security manager
     * @return null
     */
    public function setSecurityManager(SecurityManager $securityManager);

    /**
     * Prepares the form by adding row definitions
     * @param \ride\library\form\FormBuilder $builder
     * @param array $options
     * @return null
     */
    public function prepareForm(FormBuilder $builder, array $options);

    /**
     * Processes the submitted values of the form
     * @param array $data Submitted values of the form
     * @param array \ride\library\mvc\controller\Controller $controller Instance
     * of the controller who is processing the request
     * @return null
     */
    public function processForm(array $data, Controller $controller);

    /**
     * Gets the template resource for view of this profile hook
     * @return \ride\library\mvc\view\View
     */
    public function getView();

}
