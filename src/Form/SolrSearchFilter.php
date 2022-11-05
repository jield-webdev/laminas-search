<?php

declare(strict_types=1);

namespace Jield\Search\Form;

use Jield\Search\Service\AbstractSearchService;
use Jield\Search\ValueObject\FacetField;
use Laminas\Form\Element\Checkbox;
use Laminas\Form\Element\MultiCheckbox;
use Laminas\Form\Element\Text;
use Laminas\Form\Fieldset;
use Laminas\InputFilter\InputFilterProviderInterface;
use RuntimeException;
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
        
        $this->setAttribute(key: 'method', value: $method);
        $this->setAttribute(key: 'action', value: '');

        if ($hasDateInterval) {
            $this->add(
                elementOrFieldset: [
                    'type' => Text::class,
                    'name' => 'dateInterval',
                    'options' => [
                        'isDateRange' => true,
                        'label' => _('txt-date-interval'),
                    ],
                ]
            );
        }

        $filter = new Fieldset(name: 'filter');

        // Add the field selection
        if (!empty($fields)) {
            foreach ($fields as $key => $values) {
                $filter->add(
                    elementOrFieldset: [
                        'type' => MultiCheckbox::class,
                        'name' => $key,
                        'options' => [
                            'label' => $key,
                            'value_options' => $values,
                        ],
                    ]
                );
            }
        }

        $this->add(elementOrFieldset: $filter);
    }

    public function createFacetFormElements(): void
    {
        $facetFieldset = new Fieldset(name: 'facet');

        foreach ($this->searchService->getFacets() as $facetField) {
            $facetElementFieldset = new Fieldset(name: $facetField->getField());
            $field = $this->getFacetByFacetField(fieldName: $facetField->getField());

            if ($facetField->getHasYesNo()) {
                $facetElementFieldset->add(elementOrFieldset: $this->createYesNoFormElement());
            }

            $facetElementFieldset->add(
                elementOrFieldset: $this->createFacetFieldFormElement(field: $field, facetField: $facetField)
            );

            if ($facetField->getHasAndOr()) {
                $facetElementFieldset->add(elementOrFieldset: $this->createAndOrFormElement());
            }

            if ((is_countable(value: $field) ? count($field) : 0) > 0) {
                $facetFieldset->add(elementOrFieldset: $facetElementFieldset);
            }
        }

        $this->add(elementOrFieldset: $facetFieldset);
    }

    private function getFacetByFacetField(string $fieldName): FacetResultInterface
    {
        $facetSet = $this->searchService->getResultSet()->getFacetSet();

        if (null === $facetSet) {
            throw new RuntimeException(message: "This search has no facets");
        }

        $facetField = $this->searchService->getFacet(fieldName: $fieldName);

        return $facetSet->getFacet($facetField->getField());
    }

    private function createYesNoFormElement(): Checkbox
    {
        $yesNoElement = new Checkbox();
        $yesNoElement->setName(name: 'yesNo');
        $yesNoElement->setLabel(label: 'txt-yes-no');
        $yesNoElement->setCheckedValue(checkedValue: 'no'); //niet
        $yesNoElement->setUseHiddenElement(useHiddenElement: false); //en

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
            $multiOptions = array_reverse(array: $multiOptions);
        }

        $facetElement = new MultiCheckbox();
        $facetElement->setName(name: 'values');
        $facetElement->setLabel(label: $facetField->getName());
        $facetElement->setValueOptions(options: $multiOptions);
        $facetElement->setLabelOption(key: 'escape', value: false);
        $facetElement->setOption(key: 'inline', value: true);
        $facetElement->setDisableInArrayValidator(disableOption: true);

        return $facetElement;
    }

    private function createAndOrFormElement(): Checkbox
    {
        $andOrElement = new Checkbox();
        $andOrElement->setName(name: 'andOr');
        $andOrElement->setLabel(label: 'txt-and-or');
        $andOrElement->setCheckedValue(checkedValue: 'and'); //en
        $andOrElement->setUseHiddenElement(useHiddenElement: false); //en

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
            throw new RuntimeException(message: "Form data is NULL, did you set the data");
        }

        if (!empty($this->data['query'])) {
            $badges[] = [
                'type' => 'search',
                'query' => $this->data['query'],
                'facetArguments' => http_build_query(
                    data: [
                        'facet' => $this->data['facet'],
                    ]
                ),
            ];
        }

        foreach ($this->data['facet'] as $facetName => $facetData) {
            $facetField = $this->searchService->getFacet(fieldName: $facetName);

            //Remaining facets are all facets wheren the current facet value is filtered out
            $remainingFacets = $this->data['facet'];

            unset($remainingFacets[$facetName]);

            $badges[] = [
                'type' => 'facet',
                'facetField' => $facetField,
                'name' => $facetField->getName(),
                'values' => implode(
                        separator: $facetData['andOr'] ?? false ? ' and ' : ' or ',
                    array: $facetData['values'] ?? []
                ),
                'hasValues' => count($facetData['values'] ?? []) > 0,
                'not' => !(isset($facetData['yesNo']) && $facetData['yesNo'] === 'no'),
                'facetArguments' => http_build_query(
                    data: [
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
