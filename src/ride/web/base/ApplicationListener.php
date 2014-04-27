<?php

namespace ride\web\base;

use ride\application\system\System;

use ride\library\dependency\DependencyInjector;
use ride\library\event\Event;
use ride\library\http\Response;
use ride\library\i18n\translator\Translator;
use ride\library\i18n\I18n;
use ride\library\mvc\message\MessageContainer;
use ride\library\mvc\message\Message;
use ride\library\router\RouteContainer;
use ride\library\router\Router;
use ride\library\security\exception\UnauthorizedException;
use ride\library\security\SecurityManager;
use ride\library\template\TemplateFacade;

use ride\web\base\menu\Taskbar;
use ride\web\base\view\BaseTemplateView;
use ride\web\mvc\view\ExceptionView;
use ride\web\mvc\view\TemplateView;
use ride\web\WebApplication;

class ApplicationListener {

    /**
     * Event before the taskbar is processed
     * @var string
     */
    const EVENT_TASKBAR_PRE = 'app.taskbar.pre';

    /**
     * Event after the taskbar is processed
     * @var string
     */
    const EVENT_TASKBAR_POST = 'app.taskbar.post';

    /**
     * Session key to store the response messages
     * @var string
     */
    const SESSION_MESSAGES = 'response.messages';

    /**
     * Act on a uncaught exception
     * @param \ride\library\event\Event $event
     * @return null
     */
    public function handleException(Event $event, I18n $i18n) {
        $exception = $event->getArgument('exception');
        $web = $event->getArgument('web');
        $response = $web->getResponse();

        if ($exception instanceof UnauthorizedException) {
            if ($response) {
                $this->showAuthenticationForm($web, $i18n->getTranslator());
            }

            return;
        } elseif (!$response) {
            return;
        }

        $view = new ExceptionView($exception);

        $response->setStatusCode(Response::STATUS_CODE_SERVER_ERROR);
        $response->clearRedirect();
        $response->setView($view);
    }

    /**
     * Handles the response messages. If a redirect is detected, the messages
     * are stored to the session for a next request. If the view is a template
     * view, the messages will be set to the view in the app variable.
     * @param \ride\library\event\Event $event
     * @return null
     */
    public function handleResponseMessages(Event $event) {
        $web = $event->getArgument('web');
        $request = $web->getRequest();
        $response = $web->getResponse();
        if (!$request || !$response) {
            return;
        }

        $messages = null;

        if ($request->hasSession()) {
            $session = $request->getSession();

            $messages = $session->get(self::SESSION_MESSAGES);
        }

        if ($messages === null) {
            $messages = new MessageContainer();
        }

        $messages->merge($response->getMessageContainer());
        if (!$messages->hasMessages()) {
            return;
        }

        if ($response->willRedirect()) {
            $session = $request->getSession();
            $session->set(self::SESSION_MESSAGES, $messages);

            return;
        } elseif (isset($session)) {
            $session->set(self::SESSION_MESSAGES);
        }

        $view = $response->getView();
        if (!($view instanceof TemplateView)) {
            return;
        }

        $template = $view->getTemplate();

        $app = $template->get('app', array());
        $app['messages'] = $messages;

        $template->set('app', $app);
    }

    /**
     * Sets a error view to the response if a status code above 399 is set
     * @return null
     */
    public function handleHttpError(Event $event, WebApplication $web, I18n $i18n, TemplateFacade $templateFacade) {
        $response = $web->getResponse();

        $statusCode = $response->getStatusCode();
        if ($statusCode < 400 || $response->getView() || $response->getBody()) {
            return;
        }

        $translator = $i18n->getTranslator();

        $titleTranslationKey = 'error.' . $statusCode . '.title';
        $title = $translator->translate($titleTranslationKey);

        if ($title != '[' . $titleTranslationKey . ']') {
            // translated
            $messageTranslationKey = 'error.' . $statusCode . '.message';
            $message = $translator->translate($messageTranslationKey);

            if ($message == '[' . $messageTranslationKey . ']') {
                $message = null;
            }
        } else {
            // no translation available
            $title = Response::getStatusPhrase($statusCode);
            $message = null;
        }

        $template = $templateFacade->createTemplate('base/http.error', array(
            'statusCode' => $statusCode,
            'title' => $title,
            'message' => $message,
        ));

        $view = new BaseTemplateView($template);
        $view->setTemplateFacade($templateFacade);

        $response->setView($view);
    }

    /**
     * Prepares the template view with the application variable
     * @param \ride\library\event\Event $event
     * @param \ride\library\dependency\DependencyInjector $dependencyInjector
     * @return null
     */
    public function prepareTemplateView(Event $event, DependencyInjector $dependencyInjector) {
        $web = $event->getArgument('web');
        $response = $web->getResponse();
        if (!$response) {
            return;
        }

        $view = $response->getView();
        if (!$view instanceof TemplateView) {
            return;
        }

        $i18n = $dependencyInjector->get('ride\\library\\i18n\\I18n');
        $system = $dependencyInjector->get('ride\\library\\system\\System');
        $securityManager = $dependencyInjector->get('ride\\library\\security\\SecurityManager');

        $template = $view->getTemplate();
        $request = $web->getRequest();
        $locale = $i18n->getLocale();

        $app = $template->get('app', array());
        $app['system'] = $system;
        $app['locale'] = $locale->getCode();
        $app['user'] = $securityManager->getUser();

        if ($request) {
            $app['url'] = array(
                'base' => $request->getBaseUrl(),
                'request' => $request->getUrl(),
                'script' => $request->getBaseScript(),
            );
        }

        if ($view instanceof BaseTemplateView) {
            $taskbar = $view->getTaskbar();

            if ($taskbar) {
                if (!$taskbar->getTitle()) {
                    $taskbar->setTitle($system->getName());
                }

                $translator = $i18n->getTranslator();
                $baseUrl = $request->getBaseScript();

                $app['taskbar'] = $this->handleTaskbar($taskbar, $dependencyInjector, $translator, $baseUrl, $securityManager);
            }
        }

        $template->set('app', $app);
    }

    /**
     * Prepares the taskbar for rendering
     * @param \ride\web\base\menu\Taskbar $taskbar
     * @param \ride\library\dependency\DependencyInjector $dependencyInjector
     * Instance of the dependency injector
     * @param \ride\library\i18n\translator\Translator $translator Instance of
     * the translator in the current locale
     * @param string $baseUrl Base script of the request
     * @param \ride\library\security\SecurityManager $securityManager Instance
     * of the security manager
     * @return null
     */
    protected function handleTaskbar(Taskbar $taskbar, DependencyInjector $dependencyInjector, Translator $translator, $baseUrl, SecurityManager $securityManager) {
        $applicationsMenu = $taskbar->getApplicationsMenu();
        if (!$applicationsMenu->hasItems()) {
            $applicationsMenu = $dependencyInjector->get('ride\\web\\base\\menu\\Menu', 'applications');

            $taskbar->setApplicationsMenu($applicationsMenu);
        }

        $settingsMenu = $taskbar->getSettingsMenu();
        if (!$settingsMenu->hasItems()) {
            $settingsMenu = $dependencyInjector->get('ride\\web\\base\\menu\\Menu', 'settings');

            if (!$securityManager->getUser()) {
                $loginMenuItem = $dependencyInjector->get('ride\\web\\base\\menu\\MenuItem', 'user.login');

                $userMenu = $settingsMenu->getItem('user.menu');
                $userMenu->addMenuItem($loginMenuItem);
            }

            $taskbar->setSettingsMenu($settingsMenu);
        }

        $eventManager = $dependencyInjector->get('ride\\library\\event\\EventManager');
        $eventManager->triggerEvent(self::EVENT_TASKBAR_PRE, array('taskbar' => $taskbar));

        $router = $dependencyInjector->get('ride\\library\\router\\Router');
        $routeContainer = $router->getRouteContainer();

        $applicationsMenu = $taskbar->getApplicationsMenu();
        $applicationsMenu->process($translator, $routeContainer, $baseUrl, $securityManager);

        $settingsMenu = $taskbar->getSettingsMenu();
        $settingsMenu->process($translator, $routeContainer, $baseUrl, $securityManager);

        $systemMenu = $settingsMenu->getItem('system.menu');
        if ($systemMenu) {
            $systemMenu->orderItems();
        }

        $eventManager->triggerEvent(self::EVENT_TASKBAR_POST, array('taskbar' => $taskbar));

        return $taskbar;
    }

    /**
     * Sets an unauthorized status code to the response and dispatch to the
     * authentication form
     * @return null
     */
    protected function showAuthenticationForm(WebApplication $web, Translator $translator) {
        $message = $translator->translate('error.unauthorized');

        $response = $web->getResponse();
        $response->addMessage(new Message($message, Message::TYPE_ERROR));

        $routeContainer = $web->getRouter()->getRouteContainer();
        $route = $routeContainer->getRouteById('login');

        $request = $web->getRequest();
        $request->setRoute($route);

        $response->setView(null);
        $response->clearRedirect();

        $dispatcher = $web->getDispatcher();
        $dispatcher->dispatch($request, $response);
    }

}
