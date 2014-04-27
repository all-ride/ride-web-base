<?php

namespace ride\web\base\controller;

use ride\library\config\Config;
use ride\library\http\Response;
use ride\library\validation\exception\ValidationException;

/**
 * Controller to manage the configuration parameters
 */
class PreferenceController extends AbstractController {

    /**
     * Action to manage the system preferences
     * @return null
     */
    public function indexAction() {
        $translator = $this->getTranslator();

        $data = array(
            'title' => $this->config->get('system.name'),
            'session-timeout' => $this->config->get('system.session.timeout', 1800) / 60,
        );

        $form = $this->createFormBuilder($data);
        $form->addRow('title', 'string', array(
            'label' => $translator->translate('label.title'),
            'description' => $translator->translate('label.system.title'),
            'filters' => array(
                'trim' => array(),
            )
        ));
        $form->addRow('session-timeout', 'number', array(
            'label' => $translator->translate('label.session.timeout'),
            'description' => $translator->translate('label.system.session.timeout'),
            'filters' => array(
                'trim' => array(),
            ),
            'validators' => array(
                'minmax' => array("minimum" => 0),
            ),
        ));

        $form = $form->build();
        if ($form->isSubmitted()) {
            try {
                $form->validate();

                $data = $form->getData();

                $this->config->set('system.name', $data['title']);
                $this->config->set('system.session.timeout', $data['session-timeout'] * 60);

                $this->addSuccess('success.preferences.saved');

                $this->response->setRedirect($this->getUrl('system.preferences'));

                return;
            } catch (ValidationException $exception) {
                $this->setValidationException($exception, $form);
            }
        }

        $this->setTemplateView('base/preferences', array(
            'form' => $form->getView(),
        ));
    }

}
