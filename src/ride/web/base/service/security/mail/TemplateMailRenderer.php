<?php

namespace ride\web\base\service\security\mail;

use ride\web\base\service\TemplateService;

/**
 * Template implementation to render a mail for the security services
 */
class TemplateMailRenderer extends AbstractMailRenderer {
    
    /**
     * Instance of the template render service
     * @var \ride\web\base\service\TemplateService
     */
    protected $templateService;
    
    /**
     * Path to the template resource to render
     * @var string
     */
    protected $template;
    
    /**
     * Constructs a new template mail service
     * @param \ride\web\base\service\TemplateService $templateService
     * @param string $template Path to the template resource
     * @return null
     */
    public function __construct(TemplateService $templateService, $template = null) {
        $this->templateService = $templateService;
        $this->template = $template;
    }

    /**
     * Sets the template for the mail
     * @param string $template Path to the template resource
     * @return null
     */
    public function setTemplate($template) {
        $this->template = $template;
    }

    /**
     * Gets the template for the mail message of the user
     * @return string Path to the template resource
     */
    public function getTemplate() {
        return $this->template;
    }    
    
    /**
     * Renders the body of a security mail
     * @param array $variables Variables for the mail
     * @return string
     */
    public function renderMail(array $variables) {
        $template = $this->getTemplate();
        if (!$template) {
            throw new Exception('Could not render the mail: no template set, call setTemplate first');
        }
        
        $variables = $this->processVariables($variables);

        $template = $this->templateService->createTemplate($template, $variables);
        
        return $this->templateService->render($template);
    }
    
}