<?php

namespace ride\web\base\service\security\mail;

/**
 * Template implementation to render a mail for the security services
 */
class TextMailRenderer extends AbstractMailRenderer {

    /**
     * Path to the template resource to render
     * @var string
     */
    protected $text;

    /**
     * Sets the text for the mail
     * @param string $text Body text of the mail
     * @return null
     */
    public function setText($text) {
        $this->text = $text;
    }

    /**
     * Gets the text for the mail
     * @return string Body text of the mail
     */
    public function getText() {
        return $this->text;
    }

    /**
     * Renders the body of a security mail
     * @param array $variables Variables for the mail
     * @return string
     */
    public function renderMail(array $variables) {
        $text = $this->getText();
        $variables = $this->processVariables($variables);

        foreach ($variables as $key => $value) {
            $text = str_replace('[[' . $key . ']]', $value, $text);
        }

        return $text;
    }

}
