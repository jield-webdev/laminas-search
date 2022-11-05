<?php

declare(strict_types=1);

namespace Jield\Search\Form;

use Laminas\Form\Element\Search;
use Laminas\Form\Element\Checkbox;
use Laminas\Form\Element\Submit;
use Laminas\Form\Element;
use Laminas\Form\Form;

use function _;

class SearchFilter extends Form
{
    public function __construct()
    {
        parent::__construct();
        $this->setAttribute('method', 'get');
        $this->setAttribute('id', 'search-form');

        $this->add(
            [
                'type'       => Search::class,
                'name'       => 'query',
                'attributes' => [
                    'class'       => 'form-control',
                    'placeholder' => _('txt-search'),
                ],
            ]
        );

        $this->add(
            [
                'type' => Checkbox::class,
                'name' => 'onlyActive',
            ]
        );

        $this->add(
            [
                'type'       => Submit::class,
                'name'       => 'search',
                'attributes' => [
                    'id'    => 'search',
                    'class' => 'btn btn-primary submitButton',
                    'value' => _('txt-search'),
                ],
            ]
        );

        $this->add(
            [
                'type'       => Submit::class,
                'name'       => 'submit',
                'attributes' => [
                    'id'    => 'search',
                    'class' => 'btn btn-primary submitButton',
                    'value' => _('txt-search'),
                ],
            ]
        );

        $this->add(
            [
                'type'       => Submit::class,
                'name'       => 'reset',
                'attributes' => [
                    'id'    => 'resetButton',
                    'class' => 'btn btn-warning resetButton',
                    'value' => _('txt-reset'),
                ],
            ]
        );
    }
}
