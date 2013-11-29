<?php

namespace pallo\web\base\view;

use pallo\library\template\Template;

use pallo\web\mvc\view\TemplateView;

/**
 * Base template view with a taskbar
 */
class BaseTemplateView extends TemplateView {

    /**
     * Constructs a new template view
     * @param pallo\library\template\Template $template
     * @return null
     */
    public function __construct(Template $template) {
        parent::__construct($template);

        $this->taskbar = new Taskbar();
    }

    /**
     * Gets the taskbar
     * @return Taskbar
     */
    public function getTaskbar() {
        return $this->taskbar;
    }

}