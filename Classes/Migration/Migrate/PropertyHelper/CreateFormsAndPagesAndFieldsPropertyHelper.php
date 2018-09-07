<?php
namespace In2code\Migration\Migration\Migrate\PropertyHelper;

use In2code\Migration\Migration\Helper\DatabaseHelper;
use In2code\Migration\Utility\ObjectUtility;
use In2code\Powermail\Domain\Model\Field;
use In2code\Powermail\Domain\Model\Form;
use In2code\Powermail\Domain\Model\Page;
use In2code\Powermail\Utility\StringUtility as PowermailStringUtility;
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Class CreateFormsAndPagesAndFieldsPropertyHelper
 *
 * Import powermail forms from legacy form code
 */
class CreateFormsAndPagesAndFieldsPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{
    /**
     * tt_content.colPos value for new content elements to powermail
     */
    const COLPOSNEWCONTENT = 888;

    /**
     * Powermail field properties
     *
     * @var array
     */
    protected $fields = [];

    /**
     * New tx_powermail_domain_model_page.uid for field relations
     *
     * @var int
     */
    protected $pageUid = 0;

    /**
     * @var DatabaseHelper
     */
    protected $databaseHelper = null;

    /**
     * @return mixed|void
     */
    public function initialize()
    {
        $this->databaseHelper = ObjectUtility::getObjectManager()->get(DatabaseHelper::class);
    }

    /**
     * @return void
     */
    public function manipulate()
    {
        $this->addFormAndPage();
        foreach ($this->getFieldKeysFromConfiguration($this->getConfiguration()) as $key) {
            $this->addField($this->getConfiguration()[$key . '.'], $this->getConfiguration()[$key]);
        }
        $this->processFieldGenerationQueue();
    }

    /**
     * @return void
     */
    protected function addFormAndPage()
    {
        $name = $this->getFullConfiguration()['prefix'];
        if (empty($name)) {
            $name = PowermailStringUtility::getRandomString(8, false);
        }
        $formUid = $this->databaseHelper->createRecord(
            Form::TABLE_NAME,
            [
                'pid' => $this->getPropertyFromRecord('pid'),
                'title' => $name,
                'pages' => 1,
                '_migrated' => 1,
                '_migrated_uid' => $this->getPropertyFromRecord('uid')
            ]
        );
        $this->pageUid = $this->databaseHelper->createRecord(
            Page::TABLE_NAME,
            [
                'forms' => $formUid,
                'pid' => $this->getPropertyFromRecord('pid'),
                'title' => $name,
                '_migrated' => 1
            ]
        );
    }

    /**
     * @param array $configuration
     * @param string $key
     * @return void
     */
    protected function addField(array $configuration, string $key)
    {
        $functionName = 'addField' . ucfirst(strtolower($key));
        if (method_exists($this, $functionName)) {
            $this->{$functionName}($configuration);
        } else {
            $this->log->addError('Form field converter "' . $functionName . '" does not exists');
        }
    }

    /**
     * Add input field to powermail
     *
     * @param array $configuration
     * @return void
     */
    protected function addFieldTextline(array $configuration)
    {
        $fieldProperties = [
            'pid' => $this->getPropertyFromRecord('pid'),
            'type' => 'input',
            'mandatory' => ((string)$configuration['required'] === 'required' ? 1 : 0),
            'title' => $this->getTitle($configuration),
            'marker' => $this->getMarkerName($configuration),
            'validation' => ($configuration['type'] === 'email' ? 1 : 0),
            'placeholder' => (string)$configuration['placeholder'],
            'sender_email' => $this->isSenderEmail($configuration),
            'sender_name' => $this->isSenderName($configuration),
            '_migrated' => 1,
            'pages' => $this->pageUid
        ];
        $this->fields[] = $fieldProperties;
    }

    /**
     * @param array $configuration
     * @return void
     */
    protected function addFieldTextarea(array $configuration)
    {
        $fieldProperties = [
            'pid' => $this->getPropertyFromRecord('pid'),
            'type' => 'textarea',
            'mandatory' => ((string)$configuration['required'] === 'required' ? 1 : 0),
            'title' => $this->getTitle($configuration),
            'marker' => $this->getMarkerName($configuration),
            '_migrated' => 1,
            'pages' => $this->pageUid
        ];
        $this->fields[] = $fieldProperties;
    }

    /**
     * @param array $configuration
     * @return void
     */
    protected function addFieldCheckbox(array $configuration)
    {
        $fieldProperties = [
            'pid' => $this->getPropertyFromRecord('pid'),
            'type' => 'check',
            'title' => $this->getTitle($configuration),
            'settings' => $configuration['name'],
            'marker' => $this->getMarkerName($configuration),
            '_migrated' => 1,
            'pages' => $this->pageUid
        ];
        if (!empty($configuration['checked'])) {
            $fieldProperties['settings'] .= '|' . $configuration['name'] . '|*';
        }
        $this->fields[] = $fieldProperties;
    }

    /**
     * @param array $configuration
     * @return void
     */
    protected function addFieldSelect(array $configuration)
    {
        $fieldProperties = [
            'pid' => $this->getPropertyFromRecord('pid'),
            'type' => 'select',
            'mandatory' => ((string)$configuration['required'] === 'required' ? 1 : 0),
            'title' => $this->getTitle($configuration),
            'settings' => $this->getOptionString($configuration),
            'marker' => $this->getMarkerName($configuration),
            '_migrated' => 1,
            'pages' => $this->pageUid
        ];
        $this->fields[] = $fieldProperties;
    }

    /**
     * @param array $configuration
     * @return void
     */
    protected function addFieldHidden(array $configuration)
    {
        $fieldProperties = [
            'pid' => $this->getPropertyFromRecord('pid'),
            'type' => 'hidden',
            'title' => $configuration['name'],
            'prefill_value' => (!empty($configuration['value']) ? $configuration['value'] : $configuration['name']),
            'marker' => $this->getMarkerName($configuration),
            '_migrated' => 1,
            'pages' => $this->pageUid
        ];
        $this->fields[] = $fieldProperties;
    }

    /**
     * @param array $configuration
     * @return void
     */
    protected function addFieldReset(array $configuration)
    {
        unset($configuration);
        $this->log->addNote('Field of type reset is not supported for a powermail migration');
    }

    /**
     * @param array $configuration
     * @return void
     */
    protected function addFieldButton(array $configuration)
    {
        unset($configuration);
        $this->log->addNote('Field of type button is not supported for a powermail migration');
    }

    /**
     * @param array $configuration
     * @return void
     */
    protected function addFieldFileupload(array $configuration)
    {
        $fieldProperties = [
            'pid' => $this->getPropertyFromRecord('pid'),
            'type' => 'file',
            'title' => $this->getTitle($configuration),
            'marker' => $this->getMarkerName($configuration),
            'settings' => $configuration['label.']['value'],
            '_migrated' => 1,
            'pages' => $this->pageUid
        ];
        $this->fields[] = $fieldProperties;
    }

    /**
     * @param array $configuration
     * @return void
     */
    protected function addFieldRadio(array $configuration)
    {
        $fieldProperties = [
            'pid' => $this->getPropertyFromRecord('pid'),
            'type' => 'radio',
            'title' => $this->getTitle($configuration),
            'settings' => $configuration['name'],
            'marker' => $this->getMarkerName($configuration),
            'mandatory' => ($configuration['required'] === 'required' ? 1 : 0),
            '_migrated' => 1,
            'pages' => $this->pageUid
        ];
        if (!empty($configuration['checked'])) {
            $fieldProperties['settings'] .= '|' . $configuration['name'] . '|*';
        }
        $this->fields[] = $fieldProperties;
    }

    /**
     * @param array $configuration
     * @return void
     */
    protected function addFieldPassword(array $configuration)
    {
        unset($configuration);
        $this->log->addNote('Field of type password is not supported for a powermail migration');
    }

    /**
     * @param array $configuration
     * @return void
     */
    protected function addFieldSubmit(array $configuration)
    {
        $fieldProperties = [
            'pid' => $this->getPropertyFromRecord('pid'),
            'type' => 'submit',
            'title' => $this->getTitle($configuration),
            'marker' => $this->getMarkerName($configuration),
            '_migrated' => 1,
            'pages' => $this->pageUid
        ];
        $this->fields[] = $fieldProperties;
    }

    /**
     * @param array $configuration
     * @return void
     */
    protected function addFieldCheckboxgroup(array $configuration)
    {
        $fieldProperties = [
            'pid' => $this->getPropertyFromRecord('pid'),
            'type' => 'check',
            'title' => $this->getTitle($configuration),
            'settings' => $this->getOptionString($configuration),
            'marker' => $this->getMarkerName($configuration),
            '_migrated' => 1,
            'pages' => $this->pageUid
        ];
        $this->fields[] = $fieldProperties;
    }

    /**
     * @param array $configuration
     * @return void
     */
    protected function addFieldRadiogroup(array $configuration)
    {
        $fieldProperties = [
            'pid' => $this->getPropertyFromRecord('pid'),
            'type' => 'radio',
            'title' => $this->getTitle($configuration),
            'settings' => $this->getOptionString($configuration),
            'marker' => $this->getMarkerName($configuration),
            '_migrated' => 1,
            'pages' => $this->pageUid
        ];
        $this->fields[] = $fieldProperties;
    }

    /**
     * Recursive call addField*() functions again
     *
     * @param array $configuration
     * @return void
     */
    protected function addFieldFieldset(array $configuration)
    {
        foreach ($this->getFieldKeysFromConfiguration($configuration) as $key) {
            $this->addField($configuration[$key . '.'], $configuration[$key]);
        }
    }

    /**
     * @param array $configuration
     * @return void
     */
    protected function addFieldHeader(array $configuration)
    {
        $tag = $configuration['headingSize'];
        $properties = [
            'pid' => $this->getPropertyFromRecord('pid'),
            'colPos' => self::COLPOSNEWCONTENT,
            'CType' => 'textpic',
            'header' => 'Automaticly generated content element for powermail',
            'header_layout' => 100,
            'bodytext' => '<' . $tag . '>' . $configuration['content'] . '</' . $tag . '>'
        ];
        $uid = $this->databaseHelper->createRecord('tt_content', $properties);

        $fieldProperties = [
            'pid' => $this->getPropertyFromRecord('pid'),
            'type' => 'content',
            'title' => $configuration['content'],
            'content_element' => $uid,
            'marker' => $this->getMarkerName($configuration),
            '_migrated' => 1,
            'pages' => $this->pageUid
        ];
        $this->fields[] = $fieldProperties;
    }

    /**
     * Get all relevant keys for field configuration
     *
     * @param array $completeConfig
     * @return int[]
     */
    protected function getFieldKeysFromConfiguration(array $completeConfig): array
    {
        $fieldKeys = [];
        foreach (array_keys($completeConfig) as $key) {
            if (MathUtility::canBeInterpretedAsInteger($key)) {
                $fieldKeys[] = (int)$key;
            }
        }
        return $fieldKeys;
    }

    /**
     * Configuration is stored in tt_content.bodytext as pseudo TypoScript
     *
     * @return array
     */
    protected function getConfiguration(): array
    {
        $configuration = $this->getFullConfiguration();
        $configuration = $this->removeUnneededConfigurationParts($configuration);
        return $configuration;
    }

    /**
     * @param array $configuration
     * @return array
     */
    protected function removeUnneededConfigurationParts(array $configuration): array
    {
        $unneededKeys = ['prefix', 'confirmation', 'rules.', 'postProcessor.'];
        foreach ($unneededKeys as $unneededKey) {
            unset($configuration[$unneededKey]);
        }
        return $configuration;
    }

    /**
     * @return void
     */
    protected function processFieldGenerationQueue()
    {
        foreach ($this->fields as $key => $properties) {
            $this->databaseHelper->createRecord(Field::TABLE_NAME, ['sorting' => $key] + $properties);
        }
    }

    /**
     * Get a string for powermail options like:
     *      Red shoes|red
     *      Blue shoes|blue
     *
     * @param array $configuration
     * @return string
     */
    protected function getOptionString(array $configuration): string
    {
        $selectString = '';
        foreach ($this->getFieldKeysFromConfiguration($configuration) as $key) {
            if (!empty($configuration[$key . '.']['text'])) {
                $selectString .= $configuration[$key . '.']['text'];
            } elseif (!empty($configuration[$key . '.']['label.']['value'])) {
                $selectString .= $configuration[$key . '.']['label.']['value'];
            }
            if (!empty($configuration[$key . '.']['value'])) {
                $selectString .= '|' . $configuration[$key . '.']['value'];
            }
            if (!empty($configuration[$key . '.']['checked']) || !empty($configuration[$key . '.']['selected'])) {
                $selectString .= '|*';
            }
            $selectString .= PHP_EOL;
        }
        return rtrim($selectString, PHP_EOL);
    }

    /**
     * @param array $configuration
     * @return string
     */
    protected function getMarkerName(array $configuration): string
    {
        return 'migration_' . PowermailStringUtility::cleanString((string)$configuration['name'])
            . '_' . PowermailStringUtility::getRandomString(4, false);
    }

    /**
     * Try to find out if this field could contain the sender email address
     *
     * @param array $configuration
     * @return int
     */
    protected function isSenderEmail(array $configuration): int
    {
        $emailNames = [
            'email',
            'e-mail',
            'e_mail',
            'mail',
        ];
        $isSenderEmail = 0;
        foreach ($emailNames as $emailName) {
            if (stristr(strtolower($configuration['name']), $emailName) !== false) {
                $isSenderEmail = 1;
                break;
            }
        }
        return $isSenderEmail;
    }

    /**
     * Try to find out if this field could contain the sender name
     *
     * @param array $configuration
     * @return int
     */
    protected function isSenderName(array $configuration): int
    {
        $names = [
            'name',
            'firstname',
            'lastname',
            'middlename',
            'vorname',
            'nachname',
        ];
        $isSenderName = 0;
        foreach ($names as $name) {
            if (stristr(strtolower($configuration['name']), $name) !== false) {
                $isSenderName = 1;
                break;
            }
        }
        return $isSenderName;
    }

    /**
     * @return array
     */
    protected function getFullConfiguration(): array
    {
        /** @var TypoScriptParser $typoScriptParser */
        $typoScriptParser = ObjectUtility::getObjectManager()->get(TypoScriptParser::class);
        $typoScriptParser->parse($this->getPropertyFromRecord('bodytext'));
        $configuration = $typoScriptParser->setup;
        return $configuration;
    }

    /**
     * @return bool
     */
    public function shouldMigrate(): bool
    {
        return $this->getPropertyFromRecord('CType') === 'mailform';
    }

    /**
     * @param array $configuration
     * @return string
     */
    protected function getTitle(array $configuration): string
    {
        $value = '';
        if (empty($value)) {
            $value = (string)$configuration['label.']['value'];
        }
        if (empty($value)) {
            $value = (string)$configuration['value'];
        }
        if (empty($value)) {
            $value = (string)$configuration['legend.']['value'];
        }
        // Remove "*" in title
        if (stristr($value, '*') !== false) {
            $value = preg_replace('~\*\s*~', '', $value);
        }
        return $value;
    }
}
