<?php

declare(strict_types=1);
namespace In2code\Migration\Migration\PropertyHelpers;

use In2code\Migration\Exception\ConfigurationException;
use In2code\Migration\Utility\StringUtility;

/**
 * Class ReplaceOnConditionPropertyHelper
 * to simply replace values in database records if some conditions are fitting
 *
 * Example configuration (with a single value to replace):
 *  'configuration' => [
 *      'conditions' => [
 *          'CType' => [
 *              'templavoila_pi1'
 *          ],
 *          'tx_templavoila_to' => [
 *              '7', // [FCE] "More information" button
 *              '8' // [FCE] Button
 *          ]
 *      ],
 *      'replace' => [
 *          'value' => 'textmedia' // also magic values are possible
 *      ]
 *  ]
 *
 * Example configuration (with a multiple values to replace):
 *  'configuration' => [
 *      'conditions' => [
 *          'CType' => [
 *              'templavoila_pi1'
 *          ],
 *          'tx_templavoila_to' => [
 *              '7', // [FCE] "More information" button
 *              '8' // [FCE] Button
 *          ]
 *      ],
 *      'replace' => [
 *          'values' => [
 *              'CType' => 'textmedia',
 *              'header_layout' => '{properties.header_layout}'
 *          ]
 *      ]
 *  ]
 */
class ReplaceOnConditionPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{
    protected array $checkForConfiguration = [
        'conditions',
        'replace',
    ];

    public function manipulate(): void
    {
        $this->setSingleValue();
        $this->setMultipleValues();
    }

    protected function setSingleValue(): void
    {
        if ($this->getConfigurationByKey('replace.value') !== null) {
            $newValue = StringUtility::parseString(
                (string)$this->getConfigurationByKey('replace.value'),
                ['properties' => $this->getProperties()]
            );
            $this->log->addMessage(
                $this->propertyName . ' changed from ' . substr($this->getProperty(), 0, 100) . ' to ' . $newValue
            );
            $this->setProperty($newValue);
        }
    }

    protected function setMultipleValues(): void
    {
        if ($this->getConfigurationByKey('replace.values') !== null) {
            $properties = [];
            foreach ($this->getConfigurationByKey('replace.values') as $field => $value) {
                $properties[$field] =
                    StringUtility::parseString((string)$value, ['properties' => $this->getProperties()]);
            }
            $this->setProperties($properties);
            $this->log->addMessage('set multiple new values');
        }
    }

    /**
     * @return bool
     * @throws ConfigurationException
     */
    public function shouldMigrate(): bool
    {
        return $this->shouldMigrateByDefaultConditions();
    }
}
