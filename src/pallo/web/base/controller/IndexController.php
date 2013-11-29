<?php

namespace pallo\web\base\controller;

class IndexController extends AbstractController {

    public function indexAction() {
        $this->setTemplateView('base/index');
    }

}