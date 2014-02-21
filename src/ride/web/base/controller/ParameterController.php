<?php

namespace ride\web\base\controller;

use ride\library\config\Config;
use ride\library\http\Response;
use ride\library\validation\exception\ValidationException;

/**
 * Controller to manage the configuration parameters
 */
class ParameterController extends AbstractController {

    /**
     * Action to view the parameters
     * @return null
     */
    public function indexAction(Config $config, $key = null) {
        if ($key) {
            $data = array(
                'oldParameter' => $key,
                'parameter' => $key,
                'value' => $config->get($key),
            );
        } else {
            $data = null;
        }

        $translator = $this->getTranslator();

        $form = $this->createFormBuilder($data);
        $form->addRow('oldParameter', 'hidden');
        $form->addRow('parameter', 'string', array(
            'label' => $translator->translate('label.parameter'),
        	'filters' => array(
                'trim' => array(),
            ),
        	'validators' => array(
                'required' => array(),
            )
        ));
        $form->addRow('value', 'text', array(
            'label' => $translator->translate('label.value'),
        ));
        $form->setRequest($this->request);

        $form = $form->build();
        if ($form->isSubmitted()) {
            try {
                $form->validate();

                $data = $form->getData();
                if ($data['value'] === '') {
                    $data['value'] = null;
                }

                if ($data['oldParameter'] && $data['parameter'] != $data['oldParameter']) {
                    $config->set($data['oldParameter'], null);
                }

                $config->set($data['parameter'], $data['value']);

                $this->addSuccess('success.parameter.saved', array('parameter' => $data['parameter']));

                $this->response->setRedirect($this->getUrl('system.parameters'));

                return;
            } catch (ValidationException $exception) {
                $this->response->setStatusCode(Response::STATUS_CODE_UNPROCESSABLE_ENTITY);

                $this->addError('error.validation');
            }
        }

        $query = $this->request->getQueryParameter('query');
        $parameters = $this->getParameters($config, $query);

        $this->setTemplateView('base/parameters', array(
        	'query' => $query,
            'parameters' => $parameters,
            'form' => $form->getView(),
        ));
    }

    /**
     * Gets the parameters from Zibo
     * @param string $query
     * @return array
     */
    protected function getParameters(Config $config, $query = null) {
        $parameters = $config->getAll();
        $parameters = $config->getConfigHelper()->flattenConfig($parameters);

        if ($query) {
            foreach ($parameters as $key => $value) {
                if (stripos($key, $query) !== false) {
                    continue;
                }

                if (stripos($value, $query) !== false) {
                    continue;
                }

                unset($parameters[$key]);
            }
        }

        ksort($parameters);

        return $parameters;
    }

}