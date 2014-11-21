<?php

namespace ride\web\base\service\security\mail;

/**
 * Interface to render a mail for the security services
 */
interface MailRenderer {

    /**
     * Renders the body of a security mail
     * @param array $variables Variables for the mail
     * @return string
     */
    public function renderMail(array $variables);

}
