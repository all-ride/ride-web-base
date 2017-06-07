<?php

namespace ride\web\base\controller;

use ride\library\config\Config;
use ride\library\http\Response;
use ride\library\template\theme\ThemeModel;
use ride\library\validation\exception\ValidationException;

use \Exception;

/**
 * Controller to manage the configuration parameters
 */
class PreferenceController extends AbstractController {

    /**
     * Action to manage the system preferences
     * @return null
     */
    public function indexAction(ThemeModel $themeModel) {
        $translator = $this->getTranslator();
        $referer = $this->getReferer();

        // read the themes
        $themes = $themeModel->getThemes();
        foreach ($themes as $themeName => $theme) {
            $themes[$themeName] = $theme->getDisplayName();
        }

        // read the image libraries
        $imageLibraries = $this->dependencyInjector->getContainer()->getDependencies('ride\\library\\image\\Image');
        foreach ($imageLibraries as $imageLibraryName => $imageLibraryDependency) {
            $imageLibraryClass = $imageLibraryDependency->getClassName();
            try {
                $imageLibrary = new $imageLibraryClass;

                $imageLibraries[$imageLibraryName] = $imageLibraryName;
            } catch (Exception $exception) {
                unset($imageLibraries[$imageLibraryName]);
            }
        }

        $config = $this->getConfig();

        // build the form
        $data = array(
            'title' => $config->get('system.name', 'Ride'),
            'theme' => $config->get('template.theme'),
            'session-timeout' => $config->get('system.session.timeout', 1800) / 60,
            'image' => $config->get('system.image'),
        );

        $hasImageLibrary = true;
        if ($data['image'] && !isset($imageLibraries[$data['image']])) {
            $imageLibraries[$data['image']] = $data['image'];

            $hasImageLibrary = false;
        }

        $form = $this->createFormBuilder($data);
        $form->addRow('title', 'string', array(
            'label' => $translator->translate('label.title'),
            'description' => $translator->translate('label.system.title'),
            'filters' => array(
                'trim' => array(),
            )
        ));
        $form->addRow('theme', 'select', array(
            'label' => $translator->translate('label.theme'),
            'description' => $translator->translate('label.system.theme'),
            'options' => $themes,
        ));
        $form->addRow('session-timeout', 'number', array(
            'label' => $translator->translate('label.session.timeout'),
            'description' => $translator->translate('label.system.session.timeout'),
            'filters' => array(
                'trim' => array(),
            ),
            'validators' => array(
                'minmax' => array("minimum" => 0),
            ),
        ));
        $form->addRow('image', 'select', array(
            'label' => $translator->translate('label.image.library'),
            'description' => $translator->translate('label.image.library.description'),
            'options' => $imageLibraries,
        ));
        $form = $form->build();

        // handle the form
        if ($form->isSubmitted()) {
            try {
                $form->validate();

                $data = $form->getData();

                $config->set('template.theme', $data['theme']);
                $config->set('system.name', $data['title'] == '' ? null : $data['title']);
                $config->set('system.session.timeout', $data['session-timeout'] * 60);
                $config->set('system.image', $data['image']);

                $this->addSuccess('success.preferences.saved');

                if (!$referer) {
                    $referer = $this->getUrl('system.preferences');
                }

                $this->response->setRedirect($referer);

                return;
            } catch (ValidationException $exception) {
                $this->setValidationException($exception, $form);
            }
        }

        if (!$hasImageLibrary) {
            $this->addWarning('warning.image.library', array('library' => $data['image']));
        }

        $this->setTemplateView('base/preferences', array(
            'form' => $form->getView(),
            'referer' => $referer,
        ));
    }

}
