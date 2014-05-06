<?php

namespace ride\web\base\profile;

use ride\library\form\FormBuilder;
use ride\library\mvc\controller\Controller;
use ride\library\security\SecurityManager;
use ride\library\template\TemplateFacade;

use ride\web\mvc\view\TemplateView;

/**
 * Abstract implementation of a profile hook
 */
abstract class AbstractProfileHook implements ProfileHook {

    /**
     * Instance of the security manager
     * @var \ride\ilbrary\security\SecurityManager
     */
    protected $securityManager;

    /**
     * Gets the machine name of this profile hook
     * @return string
     */
    public function getName() {
        return static::NAME;
    }

    /**
     * Sets the security manager
     * @param \ride\library\security\SecurityManager $securityManager Instance
     * of the security manager
     * @return null
     */
    public function setSecurityManager(SecurityManager $securityManager) {
        $this->securityManager = $securityManager;
    }

    /**
     * Sets the template facade
     * @param \ride\library\template\TemplateFacade $templateFacade Instance of
     * the template facade
     * @return null
     */
    public function setTemplateFacade(TemplateFacade $templateFacade) {
        $this->templateFacade = $templateFacade;
    }

    /**
     * Prepares the form by adding row definitions
     * @param \ride\library\form\FormBuilder $builder
     * @param array $options
     * @return null
     */
    public function prepareForm(FormBuilder $builder, array $options) {

    }

    /**
     * Processes the submitted values of the form
     * @param array $data Submitted values of the form
     * @param array \ride\library\mvc\controller\Controller $controller Instance
     * of the controller who is processing the request
     * @return null
     */
    public function processForm(array $data, Controller $controller) {

    }

    /**
     * Gets the view of this profile hook
     * @return \ride\library\mvc\view\View
     */
    public function getView() {
        return $this->createTemplateView(static::TEMPLATE);
    }

    /**
     * Creates a template view
     * @param string $resource Resource to the template
     * @param array $variables Variables for the template
     * @return ride\web\mvc\view\TemplateView
     */
    protected function createTemplateView($resource, array $variables = null) {
        $template = $this->templateFacade->createTemplate($resource, $variables);

        $view = new TemplateView($template);
        $view->setTemplateFacade($this->templateFacade);

        return $view;
    }

}
