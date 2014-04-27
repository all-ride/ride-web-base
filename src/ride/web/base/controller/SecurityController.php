<?php

namespace ride\web\base\controller;

use ride\library\http\Response;
use ride\library\validation\exception\ValidationException;

/**
 * Controller to manage the security model
 */
class SecurityController extends AbstractController {

    /**
     * Action to manage the secured paths
     * @return null
     */
    public function pathAction() {
        $translator = $this->getTranslator();
        $securityModel = $this->getSecurityModel();
        if (!$securityModel) {
            return;
        }

        $roles = $securityModel->getRoles();
        $securedPaths = $securityModel->getSecuredPaths();

        $data = array(
            'allowed-paths' => array(),
            'secured-paths' => $this->getPathsString($securedPaths),
        );

        foreach ($roles as $role) {
            $data['allowed-paths'][$role->getId()] = $this->getPathsString($role->getPaths());
        }

        $form = $this->createFormBuilder($data);
        if ($data['allowed-paths']) {
            $form->addRow('allowed-paths', 'text', array(
                'label' => $translator->translate('label.paths.allowed'),
                'description' => $translator->translate('label.paths.allowed.description'),
                'multiple' => true,
                'attributes' => array(
                    'rows' => 5,
                ),
                'filters' => array(
                    'trim' => array(
                        'trim.empty' => true,
                        'trim.lines' => true,
                    ),
                )
            ));
        }
        $form->addRow('secured-paths', 'text', array(
            'label' => $translator->translate('label.paths.secured'),
            'description' => $translator->translate('label.paths.secured.description'),
            'attributes' => array(
                'rows' => 5,
            ),
            'filters' => array(
                'trim' => array(
                    'trim.empty' => true,
                    'trim.lines' => true,
                ),
            ),
        ));

        $form = $form->build();
        if ($form->isSubmitted()) {
            try {
                $form->validate();

                $data = $form->getData();

                foreach ($roles as $role) {
                    $role->setPaths(explode("\n", $data['secured-paths'][$role->getId()]));

                    $securityModel->saveRole($role);
                }

                if ($data['secured-paths']) {
                    $securityModel->setSecuredPaths(explode("\n", $data['secured-paths']));
                }

                $this->addSuccess('success.security.saved');

                $this->response->setRedirect($this->getUrl('system.security'));

                return;
            } catch (ValidationException $exception) {
                $this->setValidationException($exception, $form);
            }
        }

        $this->setTemplateView('base/security', array(
            'form' => $form->getView(),
            'roles' => $roles,
        ));
    }

    public function roleFormAction($id = null) {
        $securityModel = $this->getSecurityModel();
        if (!$securityModel) {
            return false;
        }

        if ($id) {

        } else {
            $role = $securityModel->createRole();
        }

        $form = $this->createFormBuilder($data);
        if ($data['allowed-paths']) {
            $form->addRow('allowed-paths', 'text', array(
                'label' => $translator->translate('label.paths.allowed'),
                'description' => $translator->translate('label.paths.allowed.description'),
                'multiple' => true,
                'attributes' => array(
                    'rows' => 5,
                ),
                'filters' => array(
                    'trim' => array(
                        'trim.empty' => true,
                        'trim.lines' => true,
                    ),
                )
            ));
        }
        $form->addRow('secured-paths', 'text', array(
            'label' => $translator->translate('label.paths.secured'),
            'description' => $translator->translate('label.paths.secured.description'),
            'attributes' => array(
                'rows' => 5,
            ),
            'filters' => array(
                'trim' => array(
                    'trim.empty' => true,
                    'trim.lines' => true,
                ),
            ),
        ));

        $form = $form->build();
        if ($form->isSubmitted()) {
            try {
                $form->validate();

                $data = $form->getData();

                foreach ($roles as $role) {

                }

                if ($data['secured-paths']) {
                    $securityModel->setSecuredPaths(explode("\n", $data['secured-paths']));
                }

                $this->addSuccess('success.security.saved');

                $this->response->setRedirect($this->getUrl('system.security'));

                return;
            } catch (ValidationException $exception) {
                $this->setValidationException($exception, $form);
            }
        }

        $this->setTemplateView('base/security.role.form', array(
            'form' => $form->getView(),
        ));
    }

    /**
     * Gets a path string for an array of routes
     * @param array $paths Array with a path as value
     * @return string
     */
    private function getPathsString(array $paths) {
        sort($paths);

        $string = '';
        foreach ($paths as $path) {
            if (empty($path)) {
                continue;
            }

            $string .= ($string ? "\n" : '') . $path;
        }

        return $string;
    }

    private function getSecurityModel() {
        $securityManager = $this->getSecurityManager();
        $securityModel = $securityManager->getSecurityModel();
        if ($securityModel) {
            return $securityModel;
        }

        $this->response->setStatusCode(Response::STATUS_CODE_SERVICE_UNAVAILABLE);

        return false;
    }

}
