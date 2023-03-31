<?php
declare(strict_types=1);
namespace In2code\Migration\Migration\PropertyHelpers;

use In2code\Migration\Utility\StringUtility;
use LogicException;
use Throwable;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
 *                      'overwriteValues' => [ // optional
 *                          'header' => 'This is the new header',
 *                          'header_layout' => '{properties.anyotherfield}'
 *                      ],
 *                      'additionalMapping' => [ // optional
 *                          [
 *                              // new variable in templates {additionalMapping.new}
 *                              'variableName' => 'new',
 *                              'keyField' => 'flexForm:what_to_display', // "flexForm:path/path" or: "row:uid"
 *                              'mapping' => [ // could change the initial value to a new value (empty array disables)
 *                                  'LIST' => 'News->list',
 *                                  'SINGLE' => 'News->detail'
 *                              ]
 *                          ]
 *                      ],
 *                      'helpers' => [ // optional
 *                          [
 *                              // new variable in templates {helper.tile.foo}
 *                              'className' => MyRecordHelper::class,
 *                              'configuration' => [
 *                                  'variableName' => 'tile',
 *                                  'anyConfiguration' => 'foo'
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
    protected array $checkForConfiguration = [
        'condition',
        'flexFormTemplate'
    ];

    public function manipulate(): void
    {
        $this->propertiesFromOverwriteValuesConfiguration();
        $this->setProperty($this->buildFlexFormString());
        $this->log->addMessage('New FlexForm value set for content.uid ' . $this->getPropertyFromRecord('uid'));
    }

    protected function buildFlexFormString(): string
    {
        $arguments = [
            'row' => $this->record,
            'flexForm' => $this->getFlexFormValues((string)$this->getConfigurationByKey('flexFormField')),
            'additionalMapping' => $this->buildFromAdditionalMapping(),
            'helper' => $this->getHelperClasses()
        ];
        return StringUtility::parseString($this->getTemplateContent(), $arguments);
    }

    protected function propertiesFromOverwriteValuesConfiguration(): void
    {
        $values = [];
        if ($this->getConfigurationByKey('overwriteValues') !== null) {
            foreach ($this->getConfigurationByKey('overwriteValues') as $field => $value) {
                $values[$field] = StringUtility::parseString($value, ['properties' => $this->getProperties()]);
            }
            $this->setProperties($values);
        }
    }

    protected function getFlexFormValues(string $field = 'pi_flexform'): array
    {
        $flexFormService = GeneralUtility::makeInstance(FlexFormService::class);
        return (array)$flexFormService->convertFlexFormContentToArray($this->getPropertyFromRecord($field));
    }

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

    protected function getHelperClasses(): array
    {
        $variables = [];
        $flexFormHelpers = $this->getConfigurationByKey('helpers');
        if (!empty($flexFormHelpers)) {
            foreach ($flexFormHelpers as $helperConfig) {
                if (!class_exists($helperConfig['className'])) {
                    throw new LogicException('Class ' . $helperConfig['className'] . ' does not exists', 1642760703);
                }
                if (empty($helperConfig['configuration']['variableName'])) {
                    throw new LogicException('variableName is missing in configuration', 1642760707);
                }
                if (is_subclass_of($helperConfig['className'], FlexFormHelperInterface::class)) {
                    /** @var FlexFormHelperInterface $helperClass */
                    $helperClass = GeneralUtility::makeInstance(
                        $helperConfig['className'],
                        $this,
                        (array)$helperConfig['configuration'],
                        $this->getRecord(),
                        $this->getRecordOld()
                    );
                    $helperClass->initialize();
                    $variables[$helperConfig['configuration']['variableName']] = $helperClass->getVariable();
                } else {
                    throw new LogicException('Class does not implement ' . FlexFormHelperInterface::class, 1642760711);
                }
            }
        }
        return $variables;
    }

    protected function getTemplateContent(): string
    {
        $pathAndFilename = $this->getConfigurationByKey('flexFormTemplate');
        $absolute = GeneralUtility::getFileAbsFileName($pathAndFilename);
        return (string)file_get_contents($absolute);
    }

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
    protected function getValueFromKeyField(string $keyField, string $field = 'pi_flexform'): string
    {
        $value = '';
        if (stristr($keyField, 'flexForm:')) {
            $properties = $this->getFlexFormValues($field);
            $fieldName = substr($keyField, strlen('flexForm:'));
            try {
                $value = ArrayUtility::getValueByPath($properties, $fieldName);
            } catch (Throwable $e) {
                // value is already an empty string here
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

    protected function resolveValueByMapping(string $value, array $mapping = []): string
    {
        if (array_key_exists($value, $mapping)) {
            $value = $mapping[$value];
        }
        return $value;
    }
}
