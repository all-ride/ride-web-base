<?php

namespace ride\web\base\form;

use ride\library\form\component\AbstractComponent;
use ride\library\form\FormBuilder;
use ride\library\validation\constraint\OrConstraint;

/**
 * Form component to request a new password
 */
class PasswordRequestComponent extends AbstractComponent {

    /**
     * Prepares the form by adding row definitions
     * @param \ride\library\form\FormBuilder $builder
     * @param array $options
     * @return null
     */
    public function prepareForm(FormBuilder $builder, array $options) {
        $constraint = new OrConstraint();
        $constraint->addProperty('username');
        $constraint->addProperty('email');

        $translator = $options['translator'];

        $builder->addRow('username', 'string', array(
            'label' => $translator->translate('label.username'),
        ));
        $builder->addRow('or', 'label', array(
            'label' => '',
            'default' => $translator->translate('label.or'),
        ));
        $builder->addRow('email', 'email', array(
            'label' => $translator->translate('label.email'),
        ));
        $builder->addValidationConstraint($constraint);
    }

}
