<?php

namespace ride\web\base\form;

use ride\library\form\component\AbstractComponent;
use ride\library\form\FormBuilder;
use ride\library\validation\constraint\EqualsConstraint;

/**
 * Form component to reset the password of a user
 */
class PasswordResetComponent extends AbstractComponent {

    /**
     * Prepares the form by adding row definitions
     * @param \ride\library\form\FormBuilder $builder
     * @param array $options
     * @return null
     */
    public function prepareForm(FormBuilder $builder, array $options) {
        $constraint = new EqualsConstraint();
        $constraint->addProperty('password');
        $constraint->addProperty('password2');

        $translator = $options['translator'];

        $builder->addRow('password', 'password', array(
            'label' => $translator->translate('label.password.new'),
            'description' => $translator->translate('label.password.new.description'),
            'validators' => array(
                'required' => array(),
            ),
        ));
        $builder->addRow('password2', 'password', array(
            'label' => $translator->translate('label.password.confirm'),
            'description' => $translator->translate('label.password.confirm.description'),
            'validators' => array(
                'required' => array(),
            ),
        ));
        $builder->addValidationConstraint($constraint);
    }

}
