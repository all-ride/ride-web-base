<?php

namespace ride\web\base\controller;

use ride\service\RouterService;

/**
 * Routes information controller
 */
class RouteController extends AbstractController {

    /**
     * Action to show an overview of the routes and aliases
     * @param \ride\service\RouterService $routerService
     * @return null
     */
    public function indexAction(RouterService $routerService) {
        $query = $this->request->getQueryParameter('query');

        $aliases = $this->getAliases($routerService, $query);
        $routes = $this->getRoutes($routerService, $query);

        $this->setTemplateView('base/routes', array(
            'query' => $query,
            'aliases' => $aliases,
            'routes' => $routes,
        ));
    }

    /**
     * Gets the routes from the router service
     * @param \ride\service\RouterService $routerService
     * @param string $query Search query
     * @return array Array with the route as key and a Route object as value
     */
    protected function getRoutes(RouterService $routerService, $query = null) {
        $routes = $routerService->getRoutes();

        if ($query) {
            foreach ($routes as $id => $route) {
                if (strpos($route->getPath(), $query) !== false) {
                    continue;
                }

                if (strpos($route->getId(), $query) !== false) {
                    continue;
                }

                unset($routes[$id]);
            }
        }

        ksort($routes);

        return $routes;
    }

    /**
     * Gets the aliases from the router service
     * @param \ride\service\RouterService $routerService
     * @param string $query Search query
     * @return array Array with the alias as key and an Alias object as value
     */
    protected function getAliases(RouterService $routerService, $query = null) {
        $aliases = $routerService->getAliases();

        if ($query) {
            foreach ($aliases as $id => $alias) {
                if (strpos($alias->getPath(), $query) !== false) {
                    continue;
                }

                if (strpos($alias->getAlias(), $query) !== false) {
                    continue;
                }

                unset($aliases[$id]);
            }
        }

        ksort($aliases);

        return $aliases;
    }

}
