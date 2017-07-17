<?php
namespace In2code\In2template\Migration\Migrate\PropertyHelper\FceHelper;

use In2code\In2template\Migration\Helper\DatabaseHelper;
use In2code\In2template\Migration\Helper\FileHelper;
use In2code\In2template\Migration\Helper\ImageHelper;

/**
 * Class BuildGridContainerAndSplitContentFceHelper
 */
class BuildGridContainerAndSplitContentFceHelper extends AbstractFceHelper implements FceHelperInterface
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
     * @var array
     */
    protected $properties = [];

    /**
     * @var int
     */
    protected $uidContainer = 0;

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
        $this->changeCurrentToContainerContentElement();
        foreach ($this->getConfigurationByKey('tx_gridelements_columns') as $type => $column) {
            switch ($type) {
                case 'text':
                    $this->cloneCurrentContentElementToChildren($column);
                    break;
                case 'image':
                    $this->createImageContentElementAndRelationsToGrid($column, 'all');
                    break;
                case 'imageLeft':
                    $this->createImageContentElementAndRelationsToGrid($column, 'left');
                    break;
                case 'imageRight':
                    $this->createImageContentElementAndRelationsToGrid($column, 'right');
                    break;
            }
        }
    }

    /**
     * @param int $column
     * @return int
     */
    protected function cloneCurrentContentElementToChildren(int $column): int
    {
        $properties = $this->properties;
        unset($properties['uid']);
        $newProperties = [
            'colPos' => -1,
            'tx_gridelements_container' => $this->uidContainer,
            'tx_gridelements_columns' => $column,
            'backupColPos' => -2,
            'CType' => 'textmedia',
            'header_layout' => 100
        ];
        if ($this->isAccordeonContent()) {
            $newProperties['tx_templavoila_flex'] = $this->removeFirstH4FromFlexFormBodyText();
            $newProperties['bodytext'] = $this->removeFirstH4FromBodyText();
        }
        return $this->databaseHelper->createRecord('tt_content', $newProperties + $properties);
    }

    /**
     * @param int $column
     * @param string $position
     * @return void
     */
    protected function createImageContentElementAndRelationsToGrid(int $column, string $position)
    {
        $images = $this->imageHelper->getImages(
            $this->getFlexFormArray(),
            $this->getConfigurationByKey('flexFormKey1'),
            $this->getConfigurationByKey('flexFormKey2'),
            $position,
            (array)$this->getConfigurationByKey('tvMappingConfiguration')
        );
        $contentUid = $this->createImageContentElement($column);
        $this->createImageRelations($images, $contentUid);
    }

    /**
     * @param int $column
     * @return int
     */
    protected function createImageContentElement(int $column): int
    {
        $properties = [
            'pid' => $this->propertyHelper->getPropertyFromRecord('pid'),
            'sorting' => $this->propertyHelper->getPropertyFromRecord('sorting'),
            'hidden' => $this->propertyHelper->getPropertyFromRecord('hidden'),
            'starttime' => $this->propertyHelper->getPropertyFromRecord('starttime'),
            'endtime' => $this->propertyHelper->getPropertyFromRecord('endtime'),
            'fe_group' => $this->propertyHelper->getPropertyFromRecord('fe_group'),
            'CType' => 'textmedia',
            'header' => $this->getConfigurationByKey('headerLabels.imageContentElement'),
            'header_layout' => 100,
            'colPos' => -1,
            'tx_gridelements_container' => $this->uidContainer,
            'tx_gridelements_columns' => $column,
            '_migrated' => 1
        ];
        return $this->databaseHelper->createRecord('tt_content', $properties);
    }

    /**
     * @param array $images
     * @param int $contentUid
     * @return void
     */
    protected function createImageRelations(array $images, int $contentUid)
    {
        if (!empty($images)) {
            foreach ($images as $imageConfiguration) {
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
    }

    /**
     * @return void
     */
    protected function changeCurrentToContainerContentElement()
    {
        $this->uidContainer = $this->propertyHelper->getPropertyFromRecord('uid');
        $newProperties = [
            'CType' => 'gridelements_pi1',
            'header' => $this->getConfigurationByKey('headerLabels.gridContentElement'),
            'header_layout' => 100,
            'backupColPos' => -2,
            'tx_gridelements_backend_layout' => $this->getConfigurationByKey('tx_gridelements_backend_layout'),
            'tx_gridelements_children' => 1,
            '_migrated' => 1
        ];
        $newProperties = $this->changeToAccordeonGrid($newProperties);
        $this->propertyHelper->setProperties($newProperties + $this->properties);
    }

    /**
     * @param array $properties
     * @return array
     */
    protected function changeToAccordeonGrid(array $properties): array
    {
        if ($this->isAccordeonContent()) {
            $this->uidContainer = $this->cloneCurrentContentElement();
            $newProperties = [
                'CType' => 'gridelements_pi1',
                'header' => $this->getAccordeonTitle(),
                'header_layout' => 2,
                'backupColPos' => -2,
                'tx_gridelements_backend_layout' => 'accordion',
                'tx_gridelements_children' => 1,
                '_migrated' => 1
            ];
            return $newProperties + $properties;
        }
        return $properties;
    }

    /**
     * @return int
     */
    protected function cloneCurrentContentElement(): int
    {
        $properties = $this->properties;
        unset($properties['uid']);
        $propertiesClone = [
            'CType' => 'gridelements_pi1',
            'header_layout' => 100,
            'colPos' => -1,
            'tx_gridelements_backend_layout' => $this->getConfigurationByKey('tx_gridelements_backend_layout'),
            'tx_gridelements_children' => 1,
            'tx_gridelements_container' => $this->uidContainer,
            'tx_gridelements_columns' => 101,
            'backupColPos' => -2,
            'tx_templavoila_flex' => $this->removeFirstH4FromFlexFormBodyText(),
            'bodytext' => $this->removeFirstH4FromBodyText()
        ];
        return $this->databaseHelper->createRecord('tt_content', $propertiesClone + $properties);
    }

    /**
     * @return int
     */
    protected function buildAccordeonContainerContentElement(): int
    {
        $databaseHelper = $this->getObjectManager()->get(DatabaseHelper::class);
        $properties = [
            'pid' => $this->propertyHelper->getPropertyFromRecord('pid'),
            'sorting' => $this->propertyHelper->getPropertyFromRecord('sorting'),
            'CType' => 'gridelements_pi1',
            'header' => $this->getAccordeonTitle(),
            'colPos' => $this->propertyHelper->getPropertyFromRecord('colPos'),
            'backupColPos' => -2,
            'tx_gridelements_backend_layout' => 'accordion',
            'tx_gridelements_children' => 1,
            'hidden' => $this->propertyHelper->getPropertyFromRecord('hidden'),
            'starttime' => $this->propertyHelper->getPropertyFromRecord('starttime'),
            'endtime' => $this->propertyHelper->getPropertyFromRecord('endtime'),
            'fe_group' => $this->propertyHelper->getPropertyFromRecord('fe_group'),
            '_migrated' => 1
        ];
        return $databaseHelper->createRecord('tt_content', $properties);
    }

    /**
     * @return string
     */
    protected function getAccordeonTitle(): string
    {
        $default = 'Akkordeon';
        $tvConfiguration = $this->getFlexFormArray();
        $bodyText = $tvConfiguration['field_text'];
        preg_match_all('~<h4>(.*)</h4>~i', $bodyText, $results);
        if (!empty($results[1][0])) {
            return html_entity_decode(strip_tags($results[1][0]));
        } else {
            $this->log->addError(
                'Could not find a H4 for accordeon in tt_content.uid='
                . $this->properties['uid'] . ' - using default title "' . $default . '"'
            );
        }
        return $default;
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
     * @return string
     */
    protected function removeFirstH4FromBodyText(): string
    {
        $bodytext = $this->propertyHelper->getPropertyFromRecord('bodytext');
        return preg_replace('~<h4>(.*)</h4>\s*~i', '', $bodytext, 1);
    }

    /**
     * Check if checkbox is marked and
     * if this template allows accordeon
     *
     * @return bool
     */
    protected function isAccordeonContent(): bool
    {
        $tvConfiguration = $this->getFlexFormArray();
        return $this->getConfigurationByKey('accordeon') === true && !empty($tvConfiguration['field_accordion']);
    }
}
