<?php

namespace pallo\web\base\controller;

use pallo\library\router\RouteContainer;
use pallo\library\router\Router;

/**
 * Routes information controller
 */
class RouteController extends AbstractController {

    /**
     * Action to show an overview of the routes
     * @param pallo\library\router\Router $router
     * @return null
     */
    public function indexAction(Router $router) {
        $query = $this->request->getQueryParameter('query');
        $routes = $this->getRoutes($router->getRouteContainer(), $query);

        $this->setTemplateView('base/routes', array(
            'query' => $query,
            'routes' => $routes,
        ));
    }

    /**
     * Gets the routes from the container
     * @param pallo\library\router\RouteContainer $routeContainer
     * @param string $query Search query
     * @return array Array with the route as key and a Route object as value
     */
    protected function getRoutes(RouteContainer $routeContainer, $query = null) {
        $routes = $routeContainer->getRoutes();

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

}