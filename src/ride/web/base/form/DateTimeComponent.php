<?php

namespace ride\web\base\form;

use ride\library\form\component\AbstractComponent;
use ride\library\form\FormBuilder;

/**
 * Form component for the profile of a user
 */
class DateTimeComponent extends AbstractComponent {

    private $isRequired;

    /**
     * Sets whether the date and time are required
     * @param boolean $isRequired
     */
    public function setIsRequired($isRequired) {
        $this->isRequired = $isRequired;
    }

    /**
     * Parse the data to form values for the component rows
     * @param mixed $data
     * @return array $data
     */
    public function parseSetData($data) {
        if (!is_numeric($data)) {
            return;
        }

        $timeZoneDiff = date('Z', $data);

        $time = ($data + $timeZoneDiff) % 86400;
        $date = $data - $time;

        if ($time === 0) {
            $time = null;
        }

        return array(
            'date' => $date,
            'time' => $time,
        );
    }

    /**
     * Parse the form values to data of the component
     * @param array $data
     * @return mixed $data
    */
    public function parseGetData(array $data) {
        $date = isset($data['date']) ? $data['date'] : null;
        $time = isset($data['time']) && !empty(trim($data['time'])) ? $data['time'] : 0;

        if ($time && !$date) {
            $result = mktime(0, 0, 0, date('n'), date('j'), date('Y'));
            $result += $time;
        } elseif ($date) {
            $result = $date + $time;
        } else {
            $result = null;
        }

        return $result;
    }

    /**
     * Prepares the form by adding row definitions
     * @param \ride\library\form\FormBuilder $builder
     * @param array $options
     * @return null
     */
    public function prepareForm(FormBuilder $builder, array $options) {
        $translator = $options['translator'];

        $validators = [];
        if ($this->isRequired) {
            $validators['required'] = array();
        }

        $builder->addRow('date', 'date', array(
            'label' => $translator->translate('label.date'),
            'round' => true,
            'validators' => $validators,
        ));
        $builder->addRow('time', 'time', array(
            'label' => $translator->translate('label.time'),
            'validators' => $validators,
        ));
    }

}
