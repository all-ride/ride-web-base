<?php

namespace ride\web\base;

use ride\application\system\System;

use ride\library\dependency\DependencyInjector;
use ride\library\event\Event;
use ride\library\http\Request;
use ride\library\http\Response;
use ride\library\i18n\translator\Translator;
use ride\library\i18n\I18n;
use ride\library\mvc\message\MessageContainer;
use ride\library\mvc\message\Message;
use ride\library\router\RouteContainer;
use ride\library\router\Router;
use ride\library\security\exception\AuthenticationException;
use ride\library\security\exception\UnauthorizedException;
use ride\library\security\model\User;
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
    public function handleException(Event $event, I18n $i18n, SecurityManager $securityManager) {
        $exception = $event->getArgument('exception');
        $web = $event->getArgument('web');
        $response = $web->getResponse();

        if ($exception instanceof UnauthorizedException) {
            if ($response) {
                $this->showAuthenticationForm($web, $i18n->getTranslator(), $securityManager->getUser());
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
        $app['locales'] = $i18n->getLocales();
        $app['security'] = $securityManager;
        try {
            $app['user'] = $securityManager->getUser();
        } catch (AuthenticationException $exception) {
            $app['user'] = null;
        }

        if ($request) {
            $app['request'] = $request;
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

                $app['taskbar'] = $this->handleTaskbar($taskbar, $dependencyInjector, $i18n, $request, $securityManager);
            }
        }

        $template->set('app', $app);
    }

    /**
     * Prepares the taskbar for rendering
     * @param \ride\web\base\menu\Taskbar $taskbar
     * @param \ride\library\dependency\DependencyInjector $dependencyInjector
     * Instance of the dependency injector
     * @param \ride\library\i18n\I18n $i18n Instance of I18n
     * @param \ride\library\http\Request $request Instance of the request
     * @param \ride\library\security\SecurityManager $securityManager Instance
     * of the security manager
     * @return null
     */
    protected function handleTaskbar(Taskbar $taskbar, DependencyInjector $dependencyInjector, I18n $i18n, Request $request, SecurityManager $securityManager) {
        // build default menus
        $applicationsMenu = $taskbar->getApplicationsMenu();
        if (!$applicationsMenu->hasItems()) {
            $applicationsMenu = $dependencyInjector->get('ride\\web\\base\\menu\\Menu', 'applications');

            $taskbar->setApplicationsMenu($applicationsMenu);
        }

        $settingsMenu = $taskbar->getSettingsMenu();
        if (!$settingsMenu->hasItems()) {
            $settingsMenu = $dependencyInjector->get('ride\\web\\base\\menu\\Menu', 'settings');
            $userMenu = $settingsMenu->getItem('user.menu');

            if (!$securityManager->getUser()) {
                $loginMenuItem = $dependencyInjector->get('ride\\web\\base\\menu\\MenuItem', 'user.login');

                $userMenu->addMenuItem($loginMenuItem);
            } else {
                $profileMenuItem = $dependencyInjector->get('ride\\web\\base\\menu\\MenuItem', 'user.profile');
                $logoutMenuItem = $dependencyInjector->get('ride\\web\\base\\menu\\MenuItem', 'user.logout');

                $userMenu->addMenuItem($profileMenuItem);
                $userMenu->addMenuItem($logoutMenuItem);
            }

            $taskbar->setSettingsMenu($settingsMenu);
        }

        // prepare needed variables
        $translator = $i18n->getTranslator();
        $baseUrl = $request->getBaseScript();
        $route = $request->getRoute();

        $locale = null;
        if ($route) {
            $locale = $route->getArgument('locale');
        }
        if (!$locale) {
            $locale = $i18n->getLocale()->getCode();
        }

        $eventArguments = array(
            'taskbar' => $taskbar,
            'locale' => $locale,
        );

        // pre hook
        $eventManager = $dependencyInjector->get('ride\\library\\event\\EventManager');
        $eventManager->triggerEvent(self::EVENT_TASKBAR_PRE, $eventArguments);

        // process menus
        $router = $dependencyInjector->get('ride\\library\\router\\Router');
        $routeContainer = $router->getRouteContainer();

        // process application menu
        $applicationsMenu = $taskbar->getApplicationsMenu();
        $applicationsMenu->process($translator, $routeContainer, $baseUrl, $securityManager);
        $applicationsMenu->orderItems(false);

        $contentMenu = $applicationsMenu->getItem('content.menu');
        if ($contentMenu) {
            $contentMenu->orderItems();
        }

        $submissionsMenu = $applicationsMenu->getItem('submissions.menu');
        if ($submissionsMenu) {
            $submissionsMenu->orderItems();
        }

        $toolsMenu = $applicationsMenu->getItem('tools.menu');
        if ($toolsMenu) {
            $toolsMenu->orderItems();
        }

        // process settings menu
        $settingsMenu = $taskbar->getSettingsMenu();
        $settingsMenu->process($translator, $routeContainer, $baseUrl, $securityManager);
        $settingsMenu->orderItems();

        // $systemMenu = $settingsMenu->getItem('system.menu');
        // if ($systemMenu) {
            // $systemMenu->orderItems();
        // }

        // post hook
        $eventManager->triggerEvent(self::EVENT_TASKBAR_POST, $eventArguments);

        return $taskbar;
    }

    /**
     * Sets an unauthorized status code to the response and dispatch to the
     * authentication form
     * @return null
     */
    protected function showAuthenticationForm(WebApplication $web, Translator $translator, User $user = null) {
        $response = $web->getResponse();
        if ($user) {
            $message = $translator->translate('error.unauthorized');

            $response->addMessage(new Message($message, Message::TYPE_ERROR));

            $routeId = 'forbidden';
        } else {
            $routeId = 'login';
        }

        $route = $web->getRouterService()->getRouteById($routeId);

        $request = $web->getRequest();
        $loginRequest = clone $request;
        $loginRequest->setRoute($route);

        $response->setView(null);
        $response->clearRedirect();

        $dispatcher = $web->getDispatcher();
        $dispatcher->dispatch($loginRequest, $response);

        $web->setRequest($request);
    }

}
