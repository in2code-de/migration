<?php
declare(strict_types=1);
namespace In2code\Migration\Migration\PropertyHelpers;

use Doctrine\DBAL\DBALException;
use In2code\Migration\Utility\ObjectUtility;
use In2code\Migration\Utility\StringUtility;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Class FlexFormGeneratorPropertyHelper allows to create FlexForm entries from a FlexForm Template
 *
 * Example integration:
 *      $propertyHelpers = [
 *          'pi_flexform' => [
 *              [
 *                  'className' => FlexFormGeneratorPropertyHelper::class,
 *                  'configuration' => [
 *                      'condition' => [
 *                          'CType' => 'pthskaadmin_list',
 *                          'list_type' => 'xyz',
 *                          'pi_flexform' => 'flexForm:path/path:value'
 *                      ],
 *                      'flexFormTemplate' => 'EXT:in2template/Resources/Private/Migration/FlexForms/Contact.xml',
 *                      'flexFormField' => 'pi_flexform', // where to look at for additionalMapping
 *                      'overwriteValues' => [
 *                          'header' => 'This is the new header',
 *                          'header_layout' => '{properties.anyotherfield}'
 *                      ],
 *                      'additionalMapping' => [
 *                          [
 *                              // optional: create new variable "new" and use it with {additionalMapping.new}
 *                              'variableName' => 'new',
 *                              'keyField' => 'flexForm:what_to_display', // "flexForm:path/path" or: "row:uid"
 *                              'mapping' => [ // could change the initial value to a new value (empty array disables)
 *                                  'LIST' => 'News->list',
 *                                  'SINGLE' => 'News->detail'
 *                              ]
 *                          ]
 *                      ]
 *                  ]
 *              ]
 *          ]
 *      ];
 *
 * In the template files variables can be used:
 *      {flexForm}              Contains FlexForm array from field "pi_flexform" (if given)
 *      {row}                   Contains Table row
 *      {additionalMapping}     Contains additional mapping fields (optional)
 */
class FlexFormGeneratorPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{
    /**
     * @var array
     */
    protected $checkForConfiguration = [
        'condition',
        'flexFormTemplate'
    ];

    /**
     * @return void
     */
    public function manipulate(): void
    {
        $this->propertiesFromOverwriteValuesConfiguration();
        $this->setProperty($this->buildFlexFormString());
        $this->log->addMessage('New FlexForm value set for content.uid ' . $this->getPropertyFromRecord('uid'));
    }

    /**
     * @return string
     */
    protected function buildFlexFormString(): string
    {
        $arguments = [
            'row' => $this->record,
            'flexForm' => $this->getFlexFormValues((string)$this->getConfigurationByKey('flexFormField')),
            'additionalMapping' => $this->buildFromAdditionalMapping()
        ];
        return StringUtility::parseString($this->getTemplateContent(), $arguments);
    }

    /**
     * @return void
     */
    protected function propertiesFromOverwriteValuesConfiguration()
    {
        $values = [];
        if ($this->getConfigurationByKey('overwriteValues') !== null) {
            foreach ($this->getConfigurationByKey('overwriteValues') as $field => $value) {
                $values[$field] = StringUtility::parseString($value, ['properties' => $this->getProperties()]);
            }
            $this->setProperties($values);
        }
    }

    /**
     * @param string $field
     * @return array
     */
    protected function getFlexFormValues(string $field = 'pi_flexform'): array
    {
        $flexFormService = ObjectUtility::getObjectManager()->get(FlexFormService::class);
        return (array)$flexFormService->convertFlexFormContentToArray($this->getPropertyFromRecord($field));
    }

    /**
     * @return array
     */
    protected function buildFromAdditionalMapping(): array
    {
        $additionalMapping = [];
        if ($this->getConfigurationByKey('additionalMapping')) {
            foreach ($this->getConfigurationByKey('additionalMapping') as $mappingConfiguration) {
                $variableName = $mappingConfiguration['variableName'];
                $value = $this->getValueFromKeyField(
                    $mappingConfiguration['keyField'],
                    $this->getConfigurationByKey('flexFormField')
                );
                $value = $this->resolveValueByMapping($value, (array)$mappingConfiguration['mapping']);
                $additionalMapping[$variableName] = $value;
            }
        }
        return $additionalMapping;
    }

    /**
     * @return string
     */
    protected function getTemplateContent(): string
    {
        $pathAndFilename = $this->getConfigurationByKey('flexFormTemplate');
        $absolute = GeneralUtility::getFileAbsFileName($pathAndFilename);
        return (string)file_get_contents($absolute);
    }

    /**
     * Check if this record should be parsed or not
     *
     * @return bool
     */
    public function shouldMigrate(): bool
    {
        foreach ($this->getConfigurationByKey('condition') as $field => $value) {
            if (stristr($value, 'flexForm:')) {
                $parts = GeneralUtility::trimExplode(':', $value, true);
                $compareValue = $parts[2];
                $storedValue = $this->getValueFromKeyField($parts[0] . ':' . $parts[1], $field);
                if ($compareValue !== $storedValue) {
                    return false;
                }
            } else {
                if ($this->getPropertyFromRecord($field) !== $value) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * @param string $keyField "flexForm:path/path" or: "row:uid"
     * @param string $field
     * @return string
     */
    protected function getValueFromKeyField(string $keyField, $field = 'pi_flexform'): string
    {
        $value = '';
        if (stristr($keyField, 'flexForm:')) {
            $properties = $this->getFlexFormValues($field);
            $fieldName = substr($keyField, strlen('flexForm:'));
            try {
                $value = ArrayUtility::getValueByPath($properties, $fieldName, '/');
            } catch (\Exception $e) {
                $value = '';
            }
        } elseif (stristr($keyField, 'row:')) {
            $properties = $this->record;
            $fieldName = substr($keyField, strlen('row:'));
            if (array_key_exists($fieldName, $properties)) {
                $value = $properties[$fieldName];
            }
        }
        return $value;
    }

    /**
     * @param string $value
     * @param array $mapping
     * @return string
     */
    protected function resolveValueByMapping(string $value, array $mapping = []): string
    {
        if (array_key_exists($value, $mapping)) {
            $value = $mapping[$value];
        }
        return $value;
    }
}
