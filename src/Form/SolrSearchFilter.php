<?php

declare(strict_types=1);

namespace Jield\Search\Form;

use Laminas\Form\Element\Checkbox;
use Laminas\Form\Element;
use Laminas\Form\Element\MultiCheckbox;
use Laminas\Form\Element\Radio;
use Laminas\Form\Fieldset;
use Laminas\InputFilter\InputFilterProviderInterface;
use RuntimeException;
use Jield\Search\Service\AbstractSearchService;
use Jield\Search\ValueObject\FacetField;
use Solarium\Component\Result\Facet\FacetResultInterface;

use function _;
use function array_reverse;
use function count;
use function http_build_query;
use function sprintf;

class SolrSearchFilter extends SearchFilter implements InputFilterProviderInterface
{
    public function __construct(
        private readonly AbstractSearchService $searchService,
        array $fields = [],
        $method = 'get',
        bool $hasDateInterval = false
    ) {
        parent::__construct();
        $this->setAttribute('method', $method);
        $this->setAttribute('action', '');

        $filter = new Fieldset('filter');

        if ($hasDateInterval) {
            $filter->add(
                [
                    'type'    => Radio::class,
                    'name'    => 'dateInterval',
                    'options' => [
                        'value_options' => [
                            'upcoming' => _('txt-upcoming'),
                            'P1M'      => _('txt-last-month'),
                            'P3M'      => _('txt-last-3-months'),
                            'P6M'      => _('txt-last-6-months'),
                            'P12M'     => _('txt-last-year'),
                            'older'    => _('txt-older-than-one-year'),
                            'all'      => _('txt-all-results'),
                        ],
                        'allow_empty'   => true,
                        'empty_option'  => '— Select a period',
                        'label'         => _('txt-date-interval'),
                    ],
                ]
            );
        }

        // Add the field selection
        if (!empty($fields)) {
            foreach ($fields as $key => $values) {
                $filter->add(
                    [
                        'type' => MultiCheckbox::class,
                        'name' => $key,

                        'options' => [
                            'label'         => $key,
                            'value_options' => $values,
                        ],
                    ]
                );
            }
        }

        $this->add($filter);
    }

    public function createFacetFormElements(): void
    {
        $facetFieldset = new Fieldset('facet');

        foreach ($this->searchService->getFacets() as $facetField) {
            $facetElementFieldset = new Fieldset($facetField->getField());
            $field                = $this->getFacetByFacetField($facetField->getField());

            if ($facetField->getHasYesNo()) {
                $facetElementFieldset->add($this->createYesNoFormElement());
            }

            $facetElementFieldset->add(
                $this->createFacetFieldFormElement($field, $facetField)
            );

            if ($facetField->getHasAndOr()) {
                $facetElementFieldset->add($this->createAndOrFormElement());
            }

            if ((is_countable($field) ? count($field) : 0) > 0) {
                $facetFieldset->add($facetElementFieldset);
            }
        }

        $this->add($facetFieldset);
    }

    private function getFacetByFacetField(string $fieldName): FacetResultInterface
    {
        $facetSet = $this->searchService->getResultSet()->getFacetSet();

        if (null === $facetSet) {
            throw new RuntimeException("This search has no facets");
        }

        $facetField = $this->searchService->getFacet($fieldName);

        return $facetSet->getFacet($facetField->getField());
    }

    private function createYesNoFormElement(): Checkbox
    {
        $yesNoElement = new Checkbox();
        $yesNoElement->setName('yesNo');
        $yesNoElement->setLabel('txt-yes-no');
        $yesNoElement->setCheckedValue(checkedValue: 'no'); //niet
        $yesNoElement->setUseHiddenElement(false); //en


        return $yesNoElement;
    }

    private function createFacetFieldFormElement(
        FacetResultInterface $field,
        FacetField $facetField
    ): MultiCheckbox {
        $multiOptions = [];
        foreach ($field as $key => $value) {
            $multiOptions[$key] = sprintf('%s [%s]', $key, $value);
        }

        if ($facetField->getReverse()) {
            $multiOptions = array_reverse($multiOptions);
        }

        $facetElement = new MultiCheckbox();
        $facetElement->setName('values');
        $facetElement->setLabel($facetField->getName());
        $facetElement->setValueOptions($multiOptions);
        $facetElement->setLabelOption('escape', false);
        $facetElement->setOption('inline', true);
        $facetElement->setDisableInArrayValidator(true);

        return $facetElement;
    }

    private function createAndOrFormElement(): Checkbox
    {
        $andOrElement = new Checkbox();
        $andOrElement->setName('andOr');
        $andOrElement->setLabel('txt-and-or');
        $andOrElement->setCheckedValue(checkedValue: 'and'); //en
        $andOrElement->setUseHiddenElement(false); //en


        return $andOrElement;
    }

    public function hasBadges(): bool
    {
        return count($this->getBadges()) > 0;
    }

    public function getBadges(): array
    {
        $badges = [];

        if (null === $this->data) {
            throw new RuntimeException("Form data is NULL, did you set the data");
        }

        if (!empty($this->data['query'])) {
            $badges[] = [
                'type'           => 'search',
                'query'          => $this->data['query'],
                'facetArguments' => http_build_query(
                    [
                        'facet' => $this->data['facet'],
                    ]
                ),
            ];
        }

        foreach ($this->data['facet'] as $facetName => $facetData) {
            $facetField = $this->searchService->getFacet($facetName);

            //Remaining facets are all facets wheren the current facet value is filtered out
            $remainingFacets = $this->data['facet'];

            unset($remainingFacets[$facetName]);

            $badges[] = [
                'type'           => 'facet',
                'facetField'     => $facetField,
                'name'           => $facetField->getName(),
                'values'         => implode(
                    $facetData['andOr'] ?? false ? ' and ' : ' or ',
                    $facetData['values'] ?? []
                ),
                'hasValues'      => count($facetData['values'] ?? []) > 0,
                'not'            => !(isset($facetData['yesNo']) && $facetData['yesNo'] === 'no'),
                'facetArguments' => http_build_query(
                    [
                        'query' => $this->data['query'],
                        'facet' => $remainingFacets,
                    ]
                ),
            ];
        }

        return $badges;
    }

    public function getInputFilterSpecification(): array
    {
        return [
            'dateInterval' => [
                'required' => false,
            ],
        ];
    }
}
