<?php
namespace In2code\In2template\Migration\Migrate\PropertyHelper;

use In2code\In2template\Migration\Migrate\PropertyHelper\FlexFormHelper\FlexFormHelperInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\FlexFormService;
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
 *                      'overwriteValues' => [
 *                          'header' => 'This is the new header for this tt_content record'
 *                      ],
 *                      'additionalMapping' => [
 *                          [
 *                              // optional: create new variable "new" and use it with {additionalMapping.new}
 *                              'variableName' => 'new',
 *                              'keyField' => 'flexForm:what_to_display', // "flexForm:path/path" or: "row:uid"
 *                              'mapping' => [
 *                                  'LIST' => 'News->list',
 *                                  'SINGLE' => 'News->detail'
 *                              ]
 *                          ]
 *                      ],
 *                      'helpers' => [
 *                          [
 *                              'className' => GetFacilityFromTypoScriptFlexFormHelper::class,
 *                              'configuration' => [
 *                                  'variableName' => 'facility'
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
 *      {helper}                Contains additional variables from individual helper classes
 */
class FlexFormGeneratorPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * @var string
     */
    protected $helperInterface = FlexFormHelperInterface::class;

    /**
     * @return void
     * @throws \Exception
     */
    public function initialize()
    {
        if (!$this->getConfigurationByKey('condition') || !$this->getConfigurationByKey('flexFormTemplate')) {
            throw new \Exception('Configuration is not complete in ' . __CLASS__);
        }
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function manipulate()
    {
        $this->setProperty($this->buildFlexFormString());
        $this->propertiesFromOverwriteValuesConfiguration();
        $this->log->addMessage('New FlexForm value set for content.uid ' . $this->getPropertyFromRecord('uid'));
    }

    /**
     * @return string
     */
    protected function buildFlexFormString(): string
    {
        $standaloneView = $this->getObjectManager()->get(StandaloneView::class);
        $standaloneView->setTemplateSource($this->getTemplateContent());
        $standaloneView->assignMultiple(
            [
                'row' => $this->record,
                'flexForm' => $this->getFlexFormValues(),
                'additionalMapping' => $this->buildFromAdditionalMapping(),
                'helper' => $this->getFromHelperClasses()
            ]
        );
        return (string)$standaloneView->render();
    }

    /**
     * @return void
     */
    protected function propertiesFromOverwriteValuesConfiguration()
    {
        $values = $this->getConfigurationByKey('overwriteValues');
        if (!empty($values)) {
            $this->setProperties($values);
        }
    }

    /**
     * @param string $field
     * @return array
     */
    protected function getFlexFormValues(string $field = 'pi_flexform'): array
    {
        $flexFormService = $this->getObjectManager()->get(FlexFormService::class);
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
                $value = $this->getValueFromKeyField($mappingConfiguration['keyField']);
                $value = $this->resolveValueByMapping($value, $mappingConfiguration['mapping']);
                $additionalMapping[$variableName] = $value;
            }
        }
        return $additionalMapping;
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function getFromHelperClasses(): array
    {
        $variables = [];
        $flexFormHelpers = $this->getConfigurationByKey('helpers');
        if (!empty($flexFormHelpers)) {
            foreach ($flexFormHelpers as $helperConfig) {
                if (!class_exists($helperConfig['className'])) {
                    throw new \Exception('Class ' . $helperConfig['className'] . ' does not exists');
                }
                if (empty($helperConfig['configuration']['variableName'])) {
                    throw new \Exception('variableName is missing in configuration');
                }
                if (is_subclass_of($helperConfig['className'], $this->helperInterface)) {
                    /** @var FlexFormHelperInterface $helperClass */
                    $helperClass = GeneralUtility::makeInstance(
                        $helperConfig['className'],
                        $this,
                        (array)$helperConfig['configuration']
                    );
                    $helperClass->initialize();
                    $variables[$helperConfig['configuration']['variableName']] = $helperClass->getVariable();
                } else {
                    throw new \Exception('Class does not implement ' . $this->helperInterface);
                }
            }
        }
        return $variables;
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
    protected function resolveValueByMapping(string $value, array $mapping): string
    {
        if (array_key_exists($value, $mapping)) {
            $value = $mapping[$value];
        }
        return $value;
    }
}
