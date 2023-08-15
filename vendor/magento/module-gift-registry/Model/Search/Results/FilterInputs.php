<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Model\Search\Results;

use Magento\GiftRegistry\Model\Attribute\Config;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Escaper;

/**
 * GiftRegistry Filter Search Inputs
 */
class FilterInputs
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var TimezoneInterface
     */
    private $localeDate;

    /**
     * @var ResolverInterface
     */
    private $localeResolver;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @param Config $config
     * @param TimezoneInterface $localeDate
     * @param ResolverInterface $localeResolver
     * @param Escaper $escaper
     */
    public function __construct(
        Config $config,
        TimezoneInterface $localeDate,
        ResolverInterface $localeResolver,
        Escaper $escaper
    ) {
        $this->config = $config;
        $this->localeDate = $localeDate;
        $this->localeResolver = $localeResolver;
        $this->escaper = $escaper;
    }

    /**
     * Filter input form data
     *
     * @param array $params
     * @param \Magento\GiftRegistry\Model\Type|null $type
     * @return array
     */
    public function filterInputParams(array $params, \Magento\GiftRegistry\Model\Type $type = null)
    {
        foreach ($params as $key => $value) {
            $params[$key] = $this->escaper->escapeHtml($value);
        }
        if ($type) {
            $dateType = $this->config->getStaticDateType();
            if ($dateType) {
                $attribute = $type->getAttributeByCode($dateType);
                $format = isset($attribute['date_format']) ? $attribute['date_format'] : null;

                $dateFields = [];
                $fromDate = $dateType . '_from';
                $toDate = $dateType . '_to';

                if (isset($params[$fromDate])) {
                    $dateFields[] = $fromDate;
                }
                if (isset($params[$toDate])) {
                    $dateFields[] = $toDate;
                }
                $params = $this->filterInputDates($params, $dateFields, $format);
            }
        }
        return $params;
    }

    /**
     * Convert dates in array from localized to internal format
     *
     * @param array $array
     * @param string[] $dateFields
     * @param string $format
     * @return array
     */
    private function filterInputDates(array $array, $dateFields, $format = null)
    {
        if (empty($dateFields)) {
            return $array;
        }
        if ($format === null) {
            $format = \IntlDateFormatter::SHORT;
        }

        $filterInput = new \Magento\Framework\Filter\LocalizedToNormalized(
            [
                'locale' => $this->localeResolver->getLocale(),
                'date_format' => $this->localeDate->getDateFormat($format),
            ]
        );
        $filterInternal = new \Magento\Framework\Filter\NormalizedToLocalized(
            ['date_format' => \Magento\Framework\Stdlib\DateTime::DATE_INTERNAL_FORMAT]
        );

        foreach ($dateFields as $dateField) {
            if (array_key_exists($dateField, $array) && !empty($dateField)) {
                $array[$dateField] = $filterInput->filter($array[$dateField]);
                $array[$dateField] = $filterInternal->filter($array[$dateField]);
            }
        }
        return $array;
    }
}
