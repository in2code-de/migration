<?php
namespace In2code\Migration\Migration\PropertyHelpers;

use In2code\Migration\Migration\Exception\ConfigurationException;
use In2code\Migration\Utility\StringUtility;

/**
 * Class ReplaceOnCondition
 *
 *  'configuration' => [
 *      'conditions' => [
 *          'CType' => [
 *              'mailform'
 *          ]
 *      ],
 *      'replace' => [
 *          'value' => 'new value {properties.title} for this field'
 *      ]
 *  ]
 */
class ReplaceOnConditionPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{
    /**
     * @return array
     */
    protected $checkForConfiguration = [
        'conditions',
        'replace'
    ];

    /**
     * @return void
     * @throws \Exception
     */
    public function manipulate(): void
    {
        $newValue = StringUtility::parseString(
            (string)$this->getConfigurationByKey('replace.value'),
            ['properties' => $this->getProperties()]
        );
        $this->log->addMessage(
            $this->propertyName . ' changed from ' . substr($this->getProperty(), 0, 100) . ' to ' . $newValue
        );
        $this->setProperty($newValue);
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function shouldMigrate(): bool
    {
        $isFitting = true;
        foreach ($this->getConfigurationByKey('conditions') as $field => $values) {
            if (!is_string($field) || !is_array($values)) {
                throw new ConfigurationException('Misconfiguration of configuration of ' . __CLASS__, 1568286543);
            }
            if (!in_array($this->getPropertyFromRecord($field), $values)) {
                $isFitting = false;
                break;
            }
        }
        return $isFitting;
    }
}
