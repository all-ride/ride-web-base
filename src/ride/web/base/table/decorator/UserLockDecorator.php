<?php

namespace ride\web\base\table\decorator;

use ride\library\html\table\decorator\Decorator;
use ride\library\html\table\Cell;
use ride\library\html\table\Row;
use ride\library\security\model\User;

/**
 * Decorator to lock users with a higher role weight
 */
class UserLockDecorator implements Decorator {

    /**
     * Role weight of the current user
     * @var integer
     */
    protected $userWeight;

    /**
     * Label to set in the cell when the user is locked
     * #var string
     */
    protected $lockedLabel;

    /**
     * Style class for the row of the locked users
     * @var string
     */
    protected $lockedClass;

    /**
     * Constructs a new user lock decorator
     * @param integer $userWeight Role weight of the current user
     * @param string $lockedLabel Value for the cell when the user is locked
     * @param string $lockedClass Style class for the row when the user is
     * locked
     * @return null
     */
    public function __construct($userWeight, $lockedLabel = null, $lockedClass = 'locked') {
        $this->userWeight = $userWeight;
        $this->lockedLabel = $lockedLabel;
        $this->lockedClass = $lockedClass;
    }

    /**
     * Decorates a table cell by setting a new value to the provided cell
     * object
     * @param \ride\library\html\table\Cell $cell Cell to decorate
     * @param \ride\library\html\table\Row $row Row which will contain the cell
     * @param int $rowNumber Number of the row in the table
     * @param array $remainingValues Array containing the values of the
     * remaining rows of the table
     * @return null|boolean When used as group decorator, return true to
     * display the group row, false or null otherwise
     */
    public function decorate(Cell $cell, Row $row, $rowNumber, array $remainingValues) {
        $user = $cell->getValue();

        if ($user instanceof User && $user->getRoleWeight() <= $this->userWeight) {
            $cell->setValue('');

            return;
        }

        $cell->setValue($this->lockedLabel);
        $cell->addToClass('lock');
        $row->addToClass($this->lockedClass);
    }

}
