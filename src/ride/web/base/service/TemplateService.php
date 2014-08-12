<?php

namespace ride\web\base\service;

use ride\application\system\System;

use ride\library\template\TemplateFacade;
use ride\library\template\Template;

/**
 * Service to render templates
 */
class TemplateService {

    /**
     * Constructs a new template service
     * @param \ride\library\template\TemplateFacade $templateFacade
     * @param \ride\library\system\System $system
     * @return null
     */
    public function __construct(TemplateFacade $templateFacade, System $system) {
        $this->templateFacade = $templateFacade;
        $this->system = $system;
    }

    /**
     * Creates a new template
     * @param string $resource Resource name of the template
     * @param array $variables Variables for the template
     * @param string $theme Machine name of the template theme
     * @param string $engine Machine name of the template engine
     * @return \ride\library\template\Template
     */
    public function createTemplate($resource, array $variables = null, $theme = null, $engine = null) {
        return $this->templateFacade->createTemplate($resource, $variables, $theme, $engine);
    }

    /**
     * Renders a template
     * @param \ride\library\template\Template $template Template to
     * render
     * @return string Rendered template
     * @throws \ride\library\template\exception\ResourceNotSetException when
     * no resource was set to the template
     * @throws \ride\library\template\exception\ResourceNotFoundException when
     * the template could not be found by the engine
     */
    public function render(Template $template) {
        $dependencyInjector = $this->system->getDependencyInjector();

        $i18n = $dependencyInjector->get('ride\\library\\i18n\\I18n');
        $securityManager = $dependencyInjector->get('ride\\library\\security\\SecurityManager');

        $app = $template->get('app', array());
        $app['system'] = $this->system;
        $app['locale'] = $i18n->getLocale()->getCode();
        $app['user'] = $securityManager->getUser();

        $request = $dependencyInjector->get('ride\\library\\mvc\\Request');
        $app['url'] = array(
            'base' => $request->getBaseUrl(),
            'request' => $request->getUrl(),
            'script' => $request->getBaseScript(),
        );

        $template->set('app', $app);

        return $this->templateFacade->render($template);
    }

}
