<?php

namespace ride\web\base\controller;

use ride\library\router\exception\RouterException;

/**
 * Dependencies information controller
 */
class DependencyController extends AbstractController {

    /**
     * Action to show an overview of the dependencies
     * @return null
     */
    public function indexAction() {
        $query = $this->request->getQueryParameter('query');
        $urlClass = $this->getClassUrl();

        $this->setTemplateView('base/dependencies', array(
            'dependencies' => $this->getDependencies($query),
            'query' => $query,
            'urlClass' => $urlClass,
        ));
    }

    /**
     * Gets the URL for the class detail
     * @return string|null
     */
    protected function getClassUrl() {
        try {
            $url = $this->getUrl('api.class');
        } catch (RouterException $e) {
            $url = null;
        }

        return $url;
    }

    /**
     * Gets the dependencies from Zibo
     * @param string $query Search query
     * @return array Array with the interface as key and an array of dependency
     * definitions as value
     */
    protected function getDependencies($query = null) {
        $dependencyContainer = $this->dependencyInjector->getContainer();
        $dependencyInterfaces = $dependencyContainer->getDependencies();

        if ($query) {
            foreach ($dependencyInterfaces as $interface => $dependencies) {
                if (strpos($interface, $query) !== false) {
                    continue;
                }

                foreach ($dependencies as $id => $dependency) {
                    if (strpos($dependency->getClassName(), $query) !== false) {
                        continue;
                    }

                    if (strpos($dependency->getId(), $query) !== false) {
                        continue;
                    }

                    unset($dependencyInterfaces[$interface][$id]);
                }

                if (!$dependencyInterfaces[$interface]) {
                    unset($dependencyInterfaces[$interface]);
                }
            }
        }

        ksort($dependencyInterfaces);

        return $dependencyInterfaces;
    }

}