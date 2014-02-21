<?php

namespace ride\web\base\controller;

/**
 * Controller to manage the caches
 */
class CacheController extends AbstractController {

    /**
     * Array with the cache controls
     * @var array
     */
    private $controls;

    /**
     * Initializes the cache controls before every action
     * @return boolean
     */
    public function preAction() {
        $this->controls = $this->dependencyInjector->getAll('ride\\application\\cache\\control\\CacheControl');

        return true;
    }

    /**
     * Action to view and change the enabled caches
     * @return null
     */
    public function indexAction() {
        $form = $this->createForm(false);
        if ($form->isSubmitted()) {
            $data = $form->getData();
            foreach ($data as $control => $enable) {
                if (!$this->controls[$control]->canToggle()) {
                    continue;
                }

                if ($enable) {
                    $this->controls[$control]->enable();
                } else {
                    $this->controls[$control]->disable();
                }
            }

            $this->addSuccess('success.cache.enabled');

            $this->response->setRedirect($this->request->getUrl());

            return;
        }

        $this->setTemplateView('base/cache', array(
        	'form' => $form->getView(),
            'controls' => $this->controls,
            'action' => 'enable',
        ));
    }

    /**
     * Action to clear the cache
     * @return null
     */
    public function clearAction() {
        $form = $this->createForm(true);
        if ($form->isSubmitted()) {
            if ($this->request->getBodyParameter('cancel')) {
                $this->response->setRedirect($this->getUrl('system.cache'));

                return;
            }

            $data = $form->getData();
            foreach ($data as $control => $clear) {
                if (!$clear) {
                    continue;
                }

                $this->controls[$control]->clear();
            }

            $this->addSuccess('success.cache.cleared');

            $this->response->setRedirect($this->getUrl('system.cache'));

            return;
        }

        $this->setTemplateView('base/cache', array(
        	'form' => $form->getView(),
            'controls' => $this->controls,
            'action' => 'clear',
        ));
    }

    /**
     * Creates the form
     * @param boolean $enableControls
     * @return ride\library\form\Form
     */
    protected function createForm($enableControls) {
        $formBuilder = $this->createFormBuilder();

        $translator = $this->getTranslator();

        $labels = array();
        foreach ($this->controls as $name => $control) {
            $labels[$name] = $translator->translate('cache.control.' . $name);
        }

        // sort controls on translated name while adding to form
        $tmpControls = $this->controls;
        $this->controls = array();
        asort($labels);

        foreach ($labels as $name => $label) {
            $this->controls[$name] = $tmpControls[$name];

            $formBuilder->addRow($name, 'option', array(
                'description' => $label,
                'default' => $this->controls[$name]->isEnabled(),
                'disabled' => !$enableControls && !$this->controls[$name]->canToggle(),
            ));
        }

        $formBuilder->setRequest($this->request);

        return $formBuilder->build();
    }

}