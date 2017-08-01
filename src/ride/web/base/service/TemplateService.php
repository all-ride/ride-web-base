<?php

namespace ride\web\base\service;

use ride\application\system\System;

use ride\library\template\TemplateFacade;
use ride\library\template\Template;

use ride\service\TemplateService as AppTemplateService;

/**
 * Service to render templates
 */
class TemplateService extends AppTemplateService {

    /**
     * Instance of the system
     * @var \ride\application\system\System
     */
    protected $system;

    /**
     * Constructs a new template service
     * @param \ride\library\template\TemplateFacade $templateFacade
     * @param \ride\library\system\System $system
     * @return null
     */
    public function __construct(TemplateFacade $templateFacade, System $system) {
        parent::__construct($templateFacade);

        $this->system = $system;
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

        return parent::render($template);
    }

}
