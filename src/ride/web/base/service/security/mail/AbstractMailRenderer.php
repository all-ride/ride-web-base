<?php

namespace ride\web\base\service\security\mail;

/**
 * Template implementation to render a mail for the security services
 */
abstract class AbstractMailRenderer implements MailRenderer {
    
    /**
     * Extra variables for the mail renderer
     * @var array
     */
    protected $variables = array();
    
    /**
     * Sets extra variables for the mail
     * @param array $variables Extra variables for the mail
     * @return null
     */
    public function setVariables(array $variables) {
        $this->variables = $variables;
    }

    /**
     * Gets the extra variables for the mail
     * @return array Extra variables for the mail
     */
    public function getVariables() {
        return $this->variables;
    }
    
    /**
     * Hook to process the variables before rendering the template
     * @param array $variables Variables to be passed to the template
     * @return array Process variables to be passed to the template
     */
    protected function processVariables(array $variables) {
        foreach ($this->variables as $key => $value) {
            if (!isset($variables[$key])) {
                $variables[$key] = $value;
            }
        }
        
        return $variables;
    }
    
}