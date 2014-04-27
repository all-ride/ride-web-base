<?php

namespace ride\web\base\controller;

use ride\library\http\Response;
use ride\library\i18n\I18n;

/**
 * Controller to manage the locales
 */
class LocaleController extends AbstractController {

    /**
     * Action to view the locales
     * @return null
     */
    public function indexAction(I18n $i18n) {
        $this->setTemplateView('base/locales', array(
            'locales' => $i18n->getLocales(),
        ));
    }

    /**
     * Action to reorder the priority of the locales
     * @return null
     */
    public function orderAction(I18n $i18n) {
        $locales = $this->request->getBodyParameter('locales');
        if (!$locales || !is_array($locales)) {
            $this->response->setStatusCode(Response::STATUS_CODE_BAD_REQUEST);

            return;
        }

        try {
            foreach ($locales as $localeCode) {
                // exception is thrown by getLocale() if locale with the specified code is not found
                $locale = $i18n->getLocale($localeCode);
            }
        } catch (I18nException $exception) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        $this->config->set('i18n.order', implode(',', $locales));
    }

}
