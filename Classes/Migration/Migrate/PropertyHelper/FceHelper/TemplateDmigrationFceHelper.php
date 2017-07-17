<?php
namespace In2code\In2template\Migration\Migrate\PropertyHelper\FceHelper;

use In2code\In2template\Migration\Helper\DatabaseHelper;
use In2code\In2template\Migration\Helper\FileHelper;
use In2code\In2template\Migration\Helper\ImageHelper;

/**
 * Class TemplateDmigrationFceHelper
 */
class TemplateDmigrationFceHelper extends AbstractFceHelper implements FceHelperInterface
{

    /**
     * @var string
     */
    protected $targetFolder = 'fileadmin/fce_contentelements/';

    /**
     * @var null|DatabaseHelper
     */
    protected $databaseHelper = null;

    /**
     * @var null|ImageHelper
     */
    protected $imageHelper = null;

    /**
     * @var null|FileHelper
     */
    protected $fileHelper = null;

    /**
     * @var int
     */
    protected $accordeonUid = 0;

    /**
     * @var int
     */
    protected $accordeonSorting = 0;

    /**
     * @var array
     */
    protected $properties = [];

    /**
     * @return void
     */
    public function initialize()
    {
        $this->databaseHelper = $this->getObjectManager()->get(DatabaseHelper::class);
        $this->imageHelper = $this->getObjectManager()->get(ImageHelper::class);
        $this->fileHelper = $this->getObjectManager()->get(FileHelper::class);
    }

    /**
     * @return void
     */
    public function start()
    {
        $this->properties = $this->propertyHelper->getProperties();
        $this->changeCurrentElementToContainerElement();
        $this->createGridContainers();
        $this->cloneCurrentContentElementBelowAndShowBodytext();
    }

    /**
     * @return void
     */
    protected function changeCurrentElementToContainerElement()
    {
        $properties = [
            'header_layout' => 100,
            'CType' => 'gridelements_pi1',
            'backupColPos' => -2,
            'tx_gridelements_backend_layout' => 'dummy',
            'tx_gridelements_children' => 1,
            '_migrated' => 1
        ];
        if ($this->isAccordeonContent()) {
            $overlayProperties = [
                'header' => $this->getAccordeonTitle(),
                'header_layout' => 2,
                'tx_gridelements_backend_layout' => 'accordion'
            ];
            $properties = $overlayProperties + $properties;
        }
        $this->propertyHelper->setProperties($properties);
    }

    /**
     * @return void
     */
    protected function createGridContainers()
    {
        $images = [
            'left' => $this->imageHelper->getImages(
                $this->getFlexFormArray(),
                $this->getConfigurationByKey('flexFormKey1'),
                $this->getConfigurationByKey('flexFormKey2'),
                'left',
                (array)$this->getConfigurationByKey('tvMappingConfiguration')
            ),
            'right' => $this->imageHelper->getImages(
                $this->getFlexFormArray(),
                $this->getConfigurationByKey('flexFormKey1'),
                $this->getConfigurationByKey('flexFormKey2'),
                'right',
                (array)$this->getConfigurationByKey('tvMappingConfiguration')
            )
        ];
        foreach ($images['left'] as $key => $imageLeft) {
            $gridContainerUid = $this->createGridContainer($key);

            // Left image
            $leftImageContentUid = $this->createImageContentElement(
                $gridContainerUid,
                $this->getConfigurationByKey('tx_gridelements_columns.imageLeft')
            );
            $this->createImageRelation($imageLeft, $leftImageContentUid);

            // Right image
            if (!empty($images['right'][$key])) {
                $rightImageContentUid = $this->createImageContentElement(
                    $gridContainerUid,
                    $this->getConfigurationByKey('tx_gridelements_columns.imageRight')
                );
                $this->createImageRelation($images['right'][$key], $rightImageContentUid);
            }
        }
    }

    /**
     * @param array $imageConfiguration
     * @param int $contentUid
     * @return void
     */
    protected function createImageRelation(array $imageConfiguration, int $contentUid)
    {
        if (!empty($imageConfiguration)) {
            $additionalProperties = $imageConfiguration;
            unset($additionalProperties['file']);
            $this->fileHelper->moveFileAndCreateReference(
                $imageConfiguration['file'],
                $this->targetFolder,
                'tt_content',
                'assets',
                $contentUid,
                $additionalProperties
            );
        }
    }

    /**
     * @param int $containerUid
     * @param int $column
     * @return int
     */
    protected function createImageContentElement(int $containerUid, int $column): int
    {
        $properties = [
            'pid' => $this->propertyHelper->getPropertyFromRecord('pid'),
            'sorting' => $this->propertyHelper->getPropertyFromRecord('sorting'),
            'CType' => 'textmedia',
            'header' => $this->getConfigurationByKey('headerLabels.imageContentElement'),
            'header_layout' => 100,
            'colPos' => -1,
            'tx_gridelements_container' => $containerUid,
            'tx_gridelements_columns' => $column,
            'hidden' => $this->propertyHelper->getPropertyFromRecord('hidden'),
            'starttime' => $this->propertyHelper->getPropertyFromRecord('starttime'),
            'endtime' => $this->propertyHelper->getPropertyFromRecord('endtime'),
            'fe_group' => $this->propertyHelper->getPropertyFromRecord('fe_group'),
            '_migrated' => 1
        ];
        return $this->databaseHelper->createRecord('tt_content', $properties);
    }

    /**
     * @param int $key
     * @return int
     */
    protected function createGridContainer(int $key)
    {
        $properties = [
            'pid' => $this->propertyHelper->getPropertyFromRecord('pid'),
            'sorting' => ($this->propertyHelper->getPropertyFromRecord('sorting') + $key),
            'CType' => 'gridelements_pi1',
            'header' => $this->getConfigurationByKey('headerLabels.gridContentElement'),
            'header_layout' => 100,
            'backupColPos' => -2,
            'tx_gridelements_backend_layout' => $this->getConfigurationByKey('tx_gridelements_backend_layout'),
            'tx_gridelements_children' => 1,
            'hidden' => $this->propertyHelper->getPropertyFromRecord('hidden'),
            'starttime' => $this->propertyHelper->getPropertyFromRecord('starttime'),
            'endtime' => $this->propertyHelper->getPropertyFromRecord('endtime'),
            'fe_group' => $this->propertyHelper->getPropertyFromRecord('fe_group'),
            '_migrated' => 1,
            'colPos' => -1,
            'tx_gridelements_container' => $this->propertyHelper->getPropertyFromRecord('uid'),
            'tx_gridelements_columns' => 101,
        ];
        return $this->databaseHelper->createRecord('tt_content', $properties);
    }

    /**
     * Clone current element for a new element of type TEXT (below grid containers):
     * - Switch header_layout to 100 to don't show the header title when migrated from FCE
     * - Increase current sorting to have the original text under any grid containers (if text is outside the grid)
     *
     * @return void
     */
    protected function cloneCurrentContentElementBelowAndShowBodytext()
    {
        $properties = $this->properties;
        unset($properties['uid']);
        $newProperties = [
            'CType' => 'textmedia',
            'header_layout' => 100,
            'sorting' => ($this->propertyHelper->getPropertyFromRecord('sorting') + 1000),
            'colPos' => -1,
            'tx_gridelements_container' => $this->propertyHelper->getPropertyFromRecord('uid'),
            'tx_gridelements_columns' => 101
        ];
        if ($this->isAccordeonContent()) {
            $newProperties['tx_templavoila_flex'] = $this->removeFirstH4FromFlexFormBodyText();
            $newProperties['bodytext'] = $this->removeFirstH4FromBodyText();
        }
        $this->databaseHelper->createRecord('tt_content', $newProperties + $properties);
    }

    /**
     * @return string
     */
    protected function getAccordeonTitle(): string
    {
        $default = 'Akkordeon';
        $tvConfiguration = $this->getFlexFormArray();
        $bodyText = $tvConfiguration['field_text_bottom'];
        preg_match_all('~<h4>(.*)</h4>~i', $bodyText, $results);
        if (!empty($results[1][0])) {
            return html_entity_decode(strip_tags($results[1][0]));
        } else {
            $this->log->addError(
                'Could not find a H4 for accordeon in tt_content.uid='
                . $this->propertyHelper->getPropertyFromRecord('uid') . ' - using default title "' . $default . '"'
            );
        }
        return $default;
    }

    /**
     * @return string
     */
    protected function removeFirstH4FromBodyText(): string
    {
        $flexFormString = $this->propertyHelper->getPropertyFromRecord('bodytext');
        return preg_replace('~<h4>(.*)</h4>\s*~i', '', $flexFormString, 1);
    }

    /**
     * @return string
     */
    protected function removeFirstH4FromFlexFormBodyText(): string
    {
        $flexFormString = $this->propertyHelper->getPropertyFromRecord('tx_templavoila_flex');
        return preg_replace('~\&lt;h4\&gt;(.*)\&lt;/h4\&gt;\s*~i', '', $flexFormString, 1);
    }

    /**
     * @return bool
     */
    protected function isAccordeonContent(): bool
    {
        $tvConfiguration = $this->getFlexFormArray();
        return !empty($tvConfiguration['field_accordion']);
    }
}
