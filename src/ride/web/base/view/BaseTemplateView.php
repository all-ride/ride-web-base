<?php

namespace ride\web\base\view;

use ride\library\template\Template;

use ride\web\base\menu\Taskbar;
use ride\web\mvc\view\TemplateView;

/**
 * Base template view with a taskbar
 */
class BaseTemplateView extends TemplateView {

    /**
     * Constructs a new template view
     * @param \ride\library\template\Template $template
     * @return null
     */
    public function __construct(Template $template) {
        parent::__construct($template);

        $this->taskbar = new Taskbar();
    }

    /**
     * Gets the taskbar
     * @return \ride\web\base\menu\Taskbar
     */
    public function getTaskbar() {
        return $this->taskbar;
    }

}
