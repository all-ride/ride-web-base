<?php

namespace ride\web\base\controller;

use ride\library\form\component\Component;
use ride\library\form\row\factory\GenericRowFactory;
use ride\library\mvc\message\Message;

use ride\web\base\view\BaseTemplateView;
use ride\web\form\WebForm;
use ride\web\mvc\controller\AbstractController as WebAbstractController;

/**
 * Abstract implementation of a controller with base application support
 */
abstract class AbstractController extends WebAbstractController {

    /**
     * Gets the i18n facade
     * @return ride\library\i18n\I18n
     */
    public function getI18n() {
        return $this->dependencyInjector->get('ride\\library\\i18n\\I18n');
    }

    /**
     * Gets the code of the current locale
     * @return string
     */
    public function getLocale() {
        return $this->getI18n()->getLocale()->getCode();
    }

    /**
     * Gets the translator
     * @return ride\library\i18n\translator\Translator
     */
    protected function getTranslator($locale = null) {
        return $this->getI18n()->getTranslator($locale);
    }

    /**
     * Add a localized information message to the response
     * @param string $translationKey translation key of the message
     * @param array $vars array with variables for the translator
     * @return null
     */
    public function addInformation($translationKey, array $vars = null) {
        $this->addMessage($translationKey, Message::TYPE_INFORMATION, $vars);
    }

    /**
     * Add a localized error message to the response
     * @param string $translationKey translation key of the message
     * @param array $vars array with variables for the translator
     * @return null
     */
    public function addError($translationKey, array $vars = null) {
        $this->addMessage($translationKey, Message::TYPE_ERROR, $vars);
    }

    /**
     * Add a localized success message to the response
     * @param string $translationKey translation key of the message
     * @param array $vars array with variables for the translator
     * @return null
     */
    public function addSuccess($translationKey, array $vars = null) {
        $this->addMessage($translationKey, Message::TYPE_SUCCESS, $vars);
    }

    /**
     * Add a localized warning message to the response
     * @param string $translationKey translation key of the message
     * @param array $vars array with variables for the translator
     * @return null
     */
    public function addWarning($translationKey, array $vars = null) {
        $this->addMessage($translationKey, Message::TYPE_WARNING, $vars);
    }

    /**
     * Add a localized message to the response
     * @param string $translationKey translation key of the message
     * @param string $type type of the message
     * @param array $vars array with variables for the translator
     * @return null
     */
    protected function addMessage($translationKey, $type, $vars) {
        $message = $this->getTranslator()->translate($translationKey, $vars);
        $message = new Message($message, $type);

        $this->response->addMessage($message);
    }

    /**
     * Creates an instance of a form builder
     * @param mixed $data Data to preset your form
     * @param array $options Extra options for the build
     * @return ride\library\form\FormBuilder Instance of a form builder
     */
    protected function createFormBuilder($data = null, $options = array()) {
        $reflectionHelper = $this->dependencyInjector->get('ride\\library\\reflection\\ReflectionHelper');
        $fileBrowser = $this->dependencyInjector->get('ride\\library\\system\\file\\browser\\FileBrowser');
        $validationFactory = $this->dependencyInjector->get('ride\\library\\validation\\factory\\ValidationFactory');

        $options['config'] = $this->config;
        $options['dependencyInjector'] = $this->dependencyInjector;
        $options['fileBrowser'] = $fileBrowser;
        $options['translator'] = $this->getTranslator();

        $rowFactory = new GenericRowFactory();
        $rowFactory->setReflectionHelper($reflectionHelper);
        $rowFactory->setFileSystem($fileBrowser->getFileSystem());
        $rowFactory->addAbsolutePath($fileBrowser->getPublicDirectory()->getPath());
        $rowFactory->addAbsolutePath($fileBrowser->getApplicationDirectory()->getPath());

        $formBuilder = new WebForm($reflectionHelper, $options);
        $formBuilder->setRowFactory($rowFactory);
        $formBuilder->setValidationFactory($validationFactory);
        $formBuilder->setData($data);

        return $formBuilder;
    }

    /**
     * Creates an instance of form
     * @param ride\library\form\component\Component $component Form component
     * to build your form
     * @param mixed $data Data to preset your form
     * @param array $options Extra options for the build
     * @return ride\library\form\Form Instance of the form
     */
    protected function buildForm(Component $component, $data = null, array $options = array(), $method = null) {
        $formBuilder = $this->createFormBuilder($data, $options);
        $formBuilder->setComponent($component);
        $formBuilder->setRequest($this->request, $method);

        return $formBuilder->build();
    }

    /**
     * Gets the security manager
     * @return ride\library\security\SecurityManager
     */
    protected function getSecurityManager() {
        return $this->dependencyInjector->get('ride\\library\\security\\SecurityManager');
    }

    /**
     * Gets the current user
     * @return ride\library\security\model\User|null
     */
    protected function getUser() {
        return $this->getSecurityManager()->getUser();
    }

    /**
     * Checks if a permission is allowed by the current user
     * @param string $permission Code of the permission
     * @return boolean True if the permission is allowed, false otherwise
     */
    protected function isPermissionGranted($permission) {
        return $this->getSecurityManager()->isPermissionGranted($permission);
    }

    /**
     * Sets a template view to the response
     * @param string $resource Resource to the template
     * @param array $variables Variables for the template
     * @return ride\web\base\view\BaseTemplateView
     */
    protected function setTemplateView($resource, array $variables = null) {
        $templateFacade = $this->dependencyInjector->get('ride\\library\\template\\TemplateFacade');

        $template = $templateFacade->createTemplate($resource, $variables);

        $view = new BaseTemplateView($template);
        $view->setTemplateFacade($templateFacade);

        $this->response->setView($view);

        return $view;
    }

}