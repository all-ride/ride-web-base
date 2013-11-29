<?php

namespace pallo\web\base\controller;

use pallo\application\system\System;

/**
 * Controller for system information actions
 */
class SystemController extends AbstractController {

    /**
     * Action to show general system information
     * @param pallo\application\system\System $system
     * @return null
     */
    public function indexAction(System $system) {
        $environment = $system->getEnvironment();
        $fileBrowser = $system->getFileBrowser();

        $this->setTemplateView('base/system', array(
        	'environment' => $environment,
            'publicDirectory' => $fileBrowser->getPublicDirectory(),
            'applicationDirectory' => $fileBrowser->getApplicationDirectory(),
            'includeDirectories' => $fileBrowser->getIncludeDirectories(),
            'phpVersion' => phpversion(),
            'client' => $system->getClient(),
        ));
    }

    /**
     * Action to show the version and module information of PHP
     * @return null
     */
    public function phpInfoAction() {
        $this->setTemplateView('base/phpinfo', array(
        	'phpinfo' => $this->getPhpInfoHtml(),
        ));
    }

    /**
     * Gets the HTML of the phpinfo function
     * @return string
     */
    protected function getPhpInfoHtml() {
        ob_start();
        phpinfo();
        $phpInfo = ob_get_clean();

        $phpInfo = preg_replace('/<style type="text\\/css">([\w|\W]*)<\\/style>/', '', $phpInfo);
        $phpInfo = preg_replace('/(<a href="(.*)"><img border="0" src="(.*)" alt="(.*) (L|l)ogo" \\/><\\/a>)/', '<a href="$2">$4</a>', $phpInfo);
        $phpInfo = preg_replace('/(<a name="(.*)">(.*)<\\/a>)/', '<a name="$2"></a>$3', $phpInfo);
        $phpInfo = preg_replace('/(<a href="\\/(.*)">(.*)<\\/a>)/', '$3', $phpInfo);

        $phpInfo = str_replace('</h3>', '</h4>', $phpInfo);
        $phpInfo = str_replace('<h3>', '<h4>', $phpInfo);
        $phpInfo = str_replace('</h2>', '</h3>', $phpInfo);
        $phpInfo = str_replace('<h2>', '<h3>', $phpInfo);
        $phpInfo = str_replace('</h1>', '</h2>', $phpInfo);
        $phpInfo = str_replace(array('<h1 class="p">', '<h1>'), '<h2>', $phpInfo);
        $phpInfo = str_replace(array(
                '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">',
                '<html xmlns="http://www.w3.org/1999/xhtml"><head>',
                '<title>phpinfo()</title><meta name="ROBOTS" content="NOINDEX,NOFOLLOW,NOARCHIVE" /></head>',
                '<body><div class="center">',
                "<br />\n</div></body></html>",
                '<a href="http://www.php.net/">PHP</a>',
                ' width="600"',
            ), '', $phpInfo
        );

        $phpInfo = trim($phpInfo);

        return $phpInfo;
    }

}