<?php

namespace ride\web\base\controller;

use ride\library\http\Response;
use ride\library\i18n\translator\Translator;
use ride\library\i18n\I18n;
use ride\library\validation\exception\ValidationException;

/**
 * Controller to manage the translations
 */
class TranslationController extends AbstractController {

    /**
     * Action to manage the translations
     * @return null
     */
    public function indexAction(I18n $i18n, $locale = null, $key = null) {
        if (!$locale) {
            $locale = $this->getContentLocale();

            $this->response->setRedirect($this->getUrl('system.translations.locale', array('locale' => $locale)));

            return;
        } elseif (!$i18n->hasLocale($locale)) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        $this->setContentLocale($locale);

        $referer = $this->request->getQueryParameter('referer');
        $dataTranslator = $this->getTranslator($locale);

        if ($key) {
            $data = array(
                'oldKey' => $key,
                'key' => $key,
                'translation' => $dataTranslator->getTranslation($key),
            );
        } else {
            $data = null;
        }

        $translator = $this->getTranslator();

        $form = $this->createFormBuilder($data);
        $form->addRow('oldKey', 'hidden');
        $form->addRow('key', 'string', array(
            'label' => $translator->translate('label.key'),
            'filters' => array(
                'trim' => array(),
            ),
            'validators' => array(
                'required' => array(),
            ),
        ));
        $form->addRow('translation', 'text', array(
            'label' => $translator->translate('label.translation'),
            'filters' => array(
                'trim' => array(),
            ),
        ));

        $form = $form->build();
        if ($form->isSubmitted()) {
            try {
                $form->validate();

                $data = $form->getData();
                if ($data['translation'] === '') {
                    $data['translation'] = null;
                }

                if ($data['oldKey'] && $data['key'] != $data['oldKey']) {
                    $dataTranslator->setTranslation($data['oldKey'], null);
                }

                $dataTranslator->setTranslation($data['key'], $data['translation']);

                $this->addSuccess('success.translation.saved', array('key' => $data['key']));

                $url = $this->getUrl('system.translations.locale', array('locale' => $locale));
                if ($referer) {
                    $url .= '?referer=' . urlencode($referer);
                }

                $this->response->setRedirect($url);

                return;
            } catch (ValidationException $exception) {
                $this->response->setStatusCode(Response::STATUS_CODE_UNPROCESSABLE_ENTITY);

                $this->addError('error.validation');
            }
        }

        $query = $this->request->getQueryParameter('query');
        $translations = $this->getTranslations($dataTranslator, $query);

        $this->setTemplateView('base/translations', array(
            'query' => $query,
            'translations' => $translations,
            'locale' => $locale,
            'locales' => $i18n->getLocales(),
            'form' => $form->getView(),
            'referer' => $referer
        ));
    }

    /**
     * Gets the translations
     * @param \ride\library\i18n\translator\Translator $translator
     * @param string $query
     * @return array
     */
    protected function getTranslations(Translator $translator, $query = null) {
        $translations = $translator->getTranslations();

        if ($query) {
            foreach ($translations as $key => $value) {
                if (stripos($key, $query) !== false) {
                    continue;
                }

                if (stripos($value, $query) !== false) {
                    continue;
                }

                unset($translations[$key]);
            }
        }

        ksort($translations);

        return $translations;
    }

}
