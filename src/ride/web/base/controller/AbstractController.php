<?php

namespace ride\web\base\controller;

use ride\library\form\component\Component;
use ride\library\form\exception\FormException;
use ride\library\form\row\factory\GenericRowFactory;
use ride\library\form\Form;
use ride\library\html\table\FormTable;
use ride\library\http\Response;
use ride\library\mvc\message\Message;
use ride\library\validation\exception\ValidationException;

use ride\web\form\WebForm;
use ride\web\mvc\controller\AbstractController as WebAbstractController;

/**
 * Abstract implementation of a controller with base application support
 */
abstract class AbstractController extends WebAbstractController {

    /**
     * Gets the i18n facade
     * @return \ride\library\i18n\I18n
     */
    public function getI18n() {
        return $this->dependencyInjector->get('ride\\library\\i18n\\I18n');
    }

    /**
     * Gets the code of the current locale
     * @return string
     */
    public function getLocale() {
        return $this->getI18n()->getLocale()->getCode();
    }

    /**
     * Gets the translator
     * @return \ride\library\i18n\translator\Translator
     */
    protected function getTranslator($locale = null) {
        return $this->getI18n()->getTranslator($locale);
    }

    /**
     * Add a localized information message to the response
     * @param string $translationKey translation key of the message
     * @param array $vars array with variables for the translator
     * @return null
     */
    public function addInformation($translationKey, array $vars = null) {
        $this->addMessage($translationKey, Message::TYPE_INFORMATION, $vars);
    }

    /**
     * Add a localized error message to the response
     * @param string $translationKey translation key of the message
     * @param array $vars array with variables for the translator
     * @return null
     */
    public function addError($translationKey, array $vars = null) {
        $this->addMessage($translationKey, Message::TYPE_ERROR, $vars);
    }

    /**
     * Add a localized success message to the response
     * @param string $translationKey translation key of the message
     * @param array $vars array with variables for the translator
     * @return null
     */
    public function addSuccess($translationKey, array $vars = null) {
        $this->addMessage($translationKey, Message::TYPE_SUCCESS, $vars);
    }

    /**
     * Add a localized warning message to the response
     * @param string $translationKey translation key of the message
     * @param array $vars array with variables for the translator
     * @return null
     */
    public function addWarning($translationKey, array $vars = null) {
        $this->addMessage($translationKey, Message::TYPE_WARNING, $vars);
    }

    /**
     * Add a localized message to the response
     * @param string $translationKey translation key of the message
     * @param string $type type of the message
     * @param array $vars array with variables for the translator
     * @return null
     */
    protected function addMessage($translationKey, $type, $vars) {
        $message = $this->getTranslator()->translate($translationKey, $vars);
        $message = new Message($message, $type);

        $this->response->addMessage($message);
    }

    /**
     * Creates an instance of a form builder
     * @param mixed $data Data to preset your form
     * @param array $options Extra options for the build
     * @param string $method Method of the form (defaults to POST)
     * @return \ride\library\form\FormBuilder Instance of a form builder
     */
    protected function createFormBuilder($data = null, $options = array(), $method = null) {
        $options['config'] = $this->config;
        $options['dependencyInjector'] = $this->dependencyInjector;
        $options['fileBrowser'] = $this->dependencyInjector->get('ride\\library\\system\\file\\browser\\FileBrowser');;
        $options['translator'] = $this->getTranslator();

        $formBuilder = $this->dependencyInjector->get('ride\\library\\form\\Form', 'web', array('options' => $options), true);
        $formBuilder->setData($data);
        $formBuilder->setRequest($this->request, $method);

        return $formBuilder;
    }

    /**
     * Creates an instance of form
     * @param \ride\library\form\component\Component $component Form component
     * to build your form
     * @param mixed $data Data to preset your form
     * @param array $options Extra options for the build
     * @param string $method Method of the form (defaults to POST)
     * @return \ride\library\form\Form Instance of the form
     */
    protected function buildForm(Component $component, $data = null, array $options = array(), $method = null) {
        $formBuilder = $this->createFormBuilder($data, $options, $method);
        $formBuilder->setComponent($component);

        return $formBuilder->build();
    }

    /**
     * Gets the table helper
     * @return \ride\library\html\table\TableHelper
     */
    protected function getTableHelper() {
        return $this->dependencyInjector->get('ride\\library\\html\\table\\TableHelper');
    }

    /**
     * Processes a table
     * @param \ride\library\html\table\FormTable $table
     * @param string $baseUrl Base URL for the table
     * @param integer $rowsPerPage Default number of rows per page
     * @param string $orderMethod Default order method
     * @param string $orderDirection Default order direction
     * @return \ride\library\form\Form Instance of the table form
     */
    protected function processTable(FormTable $table, $baseUrl, $rowsPerPage = 10, $orderMethod = null, $orderDirection = null) {
        $tableHelper = $this->getTableHelper();
        $page = 1;
        $searchQuery = null;

        $parameters = $this->request->getQueryParameters();

        $tableHelper->getArgumentsFromArray($parameters, $page, $rowsPerPage, $searchQuery, $orderMethod, $orderDirection);
        $tableHelper->setArgumentsToTable($table, $page, $rowsPerPage, $searchQuery, $orderMethod, $orderDirection);

        $form = $this->buildForm($table);

        if (!$parameters && ($table->hasPaginationOptions() || $table->hasOrderMethods())) {
            // make sure the page has the query parameters is displays
            $url = $tableHelper->getUrlFromTable($table, $baseUrl);

            $this->response->setRedirect($url);

            return;
        }

        $this->processTableForm($table, $form);

        $url = $tableHelper->getUrlFromTable($table, $baseUrl);
        if ($tableHelper->isTableChanged($table, $page, $rowsPerPage, $searchQuery, $orderMethod, $orderDirection)) {
            $this->response->setRedirect($url);
        }

        $tableHelper->setUrlToTable($table, $url);

        return $form;
    }

    /**
     * Hook into the table form processing
     * @param \ride\library\html\table\FormTable $table
     * @param \ride\library\form\Form $form
     * @return null
     */
    protected function processTableForm(FormTable $table, Form $form) {
        $table->processForm($form);
    }

    /**
     * Gets the security manager
     * @return \ride\library\security\SecurityManager
     */
    protected function getSecurityManager() {
        return $this->dependencyInjector->get('ride\\library\\security\\SecurityManager');
    }

    /**
     * Gets the current user
     * @return \ride\library\security\model\User|null
     */
    protected function getUser() {
        return $this->getSecurityManager()->getUser();
    }

    /**
     * Checks if a permission is allowed by the current user
     * @param string $permission Code of the permission
     * @return boolean True if the permission is allowed, false otherwise
     */
    protected function isPermissionGranted($permission) {
        return $this->getSecurityManager()->isPermissionGranted($permission);
    }

    /**
     * Sets the validation exception to the response by adding errors for the
     * fields and setting the status code
     * @param \ride\library\validation\exception\ValidationException $exception
     * @param \ride\library\form\Form $form Form which can display errors from
     * the exception
     * @param string $error Translation key for the general validation error
     * @param integer $statusCode Override the 422 status code
     * @return null
     */
    protected function setValidationException(ValidationException $exception, Form $form = null, $error = 'error.validation', $statusCode = null) {
        if ($error) {
            $this->addError($error);
        }

        if (!$statusCode) {
            $statusCode = Response::STATUS_CODE_UNPROCESSABLE_ENTITY;
        }
        $this->response->setStatusCode($statusCode);

        $errors = $exception->getAllErrors();
        if (!$form) {
            foreach ($errors as $fieldName => $fieldErrors) {
                foreach ($fieldErrors as $error) {
                    $this->addError($error->getCode(), $error->getParameters());
                }
            }
        } else {
            foreach ($errors as $fieldName => $fieldErrors) {
                // omit error if the form has the field
                try {
                    $row = $form;

                    $tokens = explode('[', $fieldName);
                    foreach ($tokens as $token) {
                        $token = trim($token, ']');

                        $row = $row->getRow($token);
                    }

                    if ($row->getType() == 'hidden') {
                        throw new FormException();
                    }

                    continue;
                } catch (FormException $e) {
                    // field not in the form, add error as general error
                    foreach ($fieldErrors as $error) {
                        $this->addError($error->getCode(), $error->getParameters());
                    }
                }
            }

            $form->setValidationException($exception);
        }
    }

    /**
     * Gets the name of the current theme
     * @return string
     */
    public function getTheme() {
        $templateFacade = $this->dependencyInjector->get('ride\\library\\template\\TemplateFacade');

        return $templateFacade->getDefaultTheme();
    }

    /**
     * Sets a template view to the response
     * @param string $resource Resource to the template
     * @param array $variables Variables for the template
     * @param string $id Id of the template view in the dependency injector
     * @return \ride\web\mvc\view\TemplateView
     */
    protected function setTemplateView($resource, array $variables = null, $id = null) {
        if ($id === null) {
            $id = 'base';
        }

        $templateFacade = $this->dependencyInjector->get('ride\\library\\template\\TemplateFacade');

        $template = $templateFacade->createTemplate($resource, $variables);

        $view = $this->dependencyInjector->get('ride\\web\\mvc\\view\\TemplateView', $id, array('template' => $template), true);
        $view->setTemplateFacade($templateFacade);

        $this->response->setView($view);

        return $view;
    }

}
