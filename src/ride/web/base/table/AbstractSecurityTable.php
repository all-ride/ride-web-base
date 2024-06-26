<?php

namespace ride\web\base\table;

use ride\library\decorator\TableOptionDecorator;
use ride\library\form\Form;
use ride\library\html\table\decorator\ValueDecorator;
use ride\library\html\table\FormTable;
use ride\library\html\Element;
use ride\library\reflection\ReflectionHelper;
use ride\library\security\model\SecurityModel;

/**
 * Table for a security overview
 */
abstract class AbstractSecurityTable extends FormTable {
    protected $securityModel;
    protected $reflectionHelper;

    /**
     * Constructs a new security table
     * @param \ride\library\security\model\SecurityModel $securityModel
     * @param \ride\library\reflection\ReflectionHelper $reflectionHelper
     * @param \ride\library\html\table\decorator\Decorator $decorator
     * @return null
     */
    public function __construct(SecurityModel $securityModel, ReflectionHelper $reflectionHelper) {
        parent::__construct(array());

        $this->securityModel = $securityModel;
        $this->reflectionHelper = $reflectionHelper;

        $this->setHasSearch(true);
    }

    /**
     * Gets the HTML of this table
     * @param string $part The part to get
     * @return string
     */
    public function getHtml($part = Element::FULL) {
        if (!$this->isPopulated && $this->actions) {
            $decorator = new ValueDecorator(null, new TableOptionDecorator($this->reflectionHelper, 'id'), $this->reflectionHelper);
            $decorator->setCellClass('option');

            $this->addDecorator($decorator, null, true);
        }

        return parent::getHtml($part);
    }

    /**
     * Processes and applies the actions, search, order and pagination of this
     * table
     * @param \ride\library\form\Form $form
     * @return null
     */
    public function processForm(Form $form) {
        if (!parent::processForm($form)) {
            return false;
        }

        $options = array(
            'page' => $this->page,
            'limit' => $this->pageRows,
            'query' => $this->searchQuery,
        );

        $this->gatherData($options);

        $this->pages = ceil($this->countRows / $this->pageRows);

        return true;
    }

    /**
     * Applies the pagination to the values in this table
     * @return null
     */
    protected function applyPagination() {

    }

    /**
     * Gathers the data from the security model
     * @param array $options Options for the get[Users|Roles] method
     * @return null
     */
    abstract protected function gatherData(array $options);

}
