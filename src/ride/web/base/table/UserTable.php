<?php

namespace ride\web\base\table;

/**
 * Table for a user overview
 */
class UserTable extends AbstractSecurityTable {

    /**
     * Gathers the data from the security model
     * @param array $options Options for the get[Users|Roles] method
     * @return null
     */
    protected function gatherData(array $options) {
        $this->countRows = $this->securityModel->countUsers($options);
        $this->values = $this->securityModel->getUsers($options);
    }

}
