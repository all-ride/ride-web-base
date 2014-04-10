<?php

namespace ride\web\base\view;

use ride\library\mvc\exception\MvcException;
use ride\library\mvc\view\HtmlView;

use ride\web\mvc\view\TemplateView;

/**
 * View for a profile page
 */
class ProfileTemplateView extends BaseTemplateView {

    /**
     * Sets profile hooks to this view
     * @param array $profileHooks Array with ProfileHook instances
     * @return null
     * @see \ride\web\base\profile\ProfileHook
     */
    public function setProfileHooks(array $profileHooks) {
        $this->template->set('hooks', $profileHooks);
    }

    /**
     * Renders the output for this view
     * @param boolean $willReturnValue True to return the rendered view, false
     * to send it straight to the client
     * @return null|string Null when provided $willReturnValue is set to true, the
     * rendered output otherwise
     */
    public function render($willReturnValue = true) {
        $hooks = $this->template->get('hooks');
        if (!$hooks || !is_array($hooks)) {
            return parent::render($willReturnValue);
        }

        if (!$this->templateFacade) {
            throw new MvcException("Could not render template view: template facade not set, invoke setTemplateFacade() first");
        }

        $hookViews = array();
        $app = $this->template->get('app');
        $form = $this->template->get('form');

        foreach ($hooks as $hookName => $hook) {
            $hookView = $hook->getView();
            if (!$hookView) {
                continue;
            }

            if ($hookView instanceof HtmlView) {
                $this->mergeResources($hookView);
            }

            if ($hookView instanceof TemplateView) {
                $hookTemplate = $hookView->getTemplate();
                $hookTemplate->set('form', $form);
                $hookTemplate->set('app', $app);

                $hookView->setTemplateFacade($this->templateFacade);
            }

            $hookViews[$hookName] = $hookView->render(true);
        }

        $this->template->set('hookViews', $hookViews);

        return parent::render($willReturnValue);
    }

}
