<?php

namespace pallo\web\base;

use pallo\application\system\System;

use pallo\library\event\EventManager;
use pallo\library\event\Event;
use pallo\library\http\Response;
use pallo\library\i18n\translator\Translator;
use pallo\library\i18n\I18n;
use pallo\library\mvc\message\MessageContainer;
use pallo\library\mvc\message\Message;
use pallo\library\router\RouteContainer;
use pallo\library\router\Router;
use pallo\library\security\exception\UnauthorizedException;
use pallo\library\security\SecurityManager;

use pallo\web\base\view\BaseTemplateView;
use pallo\web\base\view\MenuItem;
use pallo\web\base\view\Menu;
use pallo\web\base\view\Taskbar;
use pallo\web\mvc\view\ExceptionView;
use pallo\web\mvc\view\TemplateView;
use pallo\web\WebApplication;

class ApplicationListener {

    /**
     * Session key to store the response messages
     * @var string
     */
    const SESSION_MESSAGES = 'response.messages';

    /**
     * Act on a uncaught exception
     * @param pallo\library\event\Event $event
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
     * @param pallo\library\event\Event $event
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
     * Prepares the template view with the application variable
     * @param pallo\library\event\Event $event
     * @param pallo\application\system\System $system
     * @param pallo\library\i18n\I18n $i18n
     * @param pallo\library\security\SecurityManager $securityManager
     * @param pallo\library\event\EventManager $eventManager
     * @param pallo\library\router\Router $router
     * @return null
     */
    public function prepareTemplateView(Event $event, System $system, I18n $i18n, SecurityManager $securityManager, EventManager $eventManager, Router $router) {
        $web = $event->getArgument('web');
        $response = $web->getResponse();
        if (!$response) {
            return;
        }

        $view = $response->getView();
        if (!$view instanceof TemplateView) {
            return;
        }

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

                $app['taskbar'] = $this->handleTaskbar($taskbar, $eventManager, $i18n->getTranslator(), $router->getRouteContainer(), $request->getBaseScript(), $securityManager);
            }
        }

        $template->set('app', $app);
    }

    /**
     * Prepares the taskbar for rendering
     * @param pallo\web\base\view\Taskbar $taskbar
     * @param pallo\library\event\EventManager $eventManager
     * @param pallo\library\i18n\translator\Translator $translator Instance of
     * the translator in the current locale
     * @param pallo\library\router\RouteContainer $routeContainer
     * @param string $baseUrl Base script of the request
     * @param pallo\library\security\SecurityManager $securityManager Instance
     * of the security manager
     * @return null
     */
    protected function handleTaskbar(Taskbar $taskbar, EventManager $eventManager, Translator $translator, RouteContainer $routeContainer, $baseUrl, SecurityManager $securityManager) {
        $userMenu = new Menu();
        $userMenu->setTranslation('title.user');

        if ($securityManager->getUser()) {
            $menuItem = new MenuItem();
            $menuItem->setTranslation('button.logout');
            $menuItem->setRoute('logout');
            $userMenu->addMenuItem($menuItem);
        } else {
            $menuItem = new MenuItem();
            $menuItem->setTranslation('button.login');
            $menuItem->setRoute('login');
            $userMenu->addMenuItem($menuItem);
        }

        $systemMenu = new Menu();
        $systemMenu->setTranslation('title.system');

        $menuItem = new MenuItem();
        $menuItem->setTranslation('title.dependencies');
        $menuItem->setRoute('system.dependencies');
        $systemMenu->addMenuItem($menuItem);

        $menuItem = new MenuItem();
        $menuItem->setTranslation('title.parameters');
        $menuItem->setRoute('system.parameters');
        $systemMenu->addMenuItem($menuItem);

        $menuItem = new MenuItem();
        $menuItem->setTranslation('title.routes');
        $menuItem->setRoute('system.routes');
        $systemMenu->addMenuItem($menuItem);

        $menuItem = new MenuItem();
        $menuItem->setTranslation('title.system');
        $menuItem->setRoute('system');
        $systemMenu->addMenuItem($menuItem);

        $menuItem = new MenuItem();
        $menuItem->setTranslation('title.translations');
        $menuItem->setRoute('system.translations');
        $systemMenu->addMenuItem($menuItem);

        $settingsMenu = $taskbar->getSettingsMenu();
        $settingsMenu->addMenu($userMenu);
        $settingsMenu->addMenu($systemMenu);

        $eventManager->triggerEvent(Taskbar::EVENT_TASKBAR, array('taskbar' => $taskbar));

        $applicationsMenu = $taskbar->getApplicationsMenu();
        $applicationsMenu->process($translator, $routeContainer, $baseUrl, $securityManager);

        $settingsMenu->process($translator, $routeContainer, $baseUrl, $securityManager);

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