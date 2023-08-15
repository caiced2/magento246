<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Filter\FieldFilter;

/**
 * Converts event field expressions to aggregated Field objects
 */
class FieldConverter
{
    /**
     * @var Field[]
     */
    private array $registry;

    /**
     * Converts a list of fields expression to the list of Field objects.
     *
     * Ignore field expression if it is not a string.
     *
     * @param string[] $fieldExpressions
     * @return Field[]
     */
    public function convert(array $fieldExpressions): array
    {
        $fields = [];
        $this->registry = [];
        foreach ($fieldExpressions as $fieldExpression) {
            if (!is_string($fieldExpression)) {
                continue;
            }
            $fields[] = $this->buildField(explode('.', $fieldExpression));
        }

        return array_filter($fields);
    }

    /**
     * Builds a Field object based on parts of event field expression.
     *
     * @param array $fieldParts
     * @param Field|null $parent
     * @return Field|null
     */
    private function buildField(array $fieldParts, Field $parent = null): ?Field
    {
        if (strpos($fieldParts[0], '[]') !== false) {
            $field = new Field(str_replace('[]', '', $fieldParts[0]), $parent, true);
        } else {
            $field = new Field($fieldParts[0], $parent, false);
        }

        if (isset($this->registry[$field->getPath()])) {
            $field = $this->registry[$field->getPath()];
            if (count($fieldParts) > 1) {
                $child = $this->buildField(array_slice($fieldParts, 1), $field);
                if ($child) {
                    $field->addChildren($child);
                }
            }
            return null;
        }

        $this->registry[$field->getPath()] = $field;

        if (count($fieldParts) > 1) {
            $field->addChildren($this->buildField(array_slice($fieldParts, 1), $field));
        }

        return $field;
    }
}
