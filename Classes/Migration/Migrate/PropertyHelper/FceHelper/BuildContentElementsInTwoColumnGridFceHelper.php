<?php
namespace In2code\In2template\Migration\Migrate\PropertyHelper\FceHelper;

use In2code\In2template\Migration\Helper\DatabaseHelper;
use In2code\In2template\Migration\Helper\PropertiesQueueHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\FlexFormService;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Class BuildContentElementsInTwoColumnGridFceHelper
 */
class BuildContentElementsInTwoColumnGridFceHelper extends AbstractFceHelper implements FceHelperInterface
{

    /**
     * @var null|DatabaseHelper
     */
    protected $databaseHelper = null;

    /**
     * @var null|PropertiesQueueHelper
     */
    protected $queueHelper = null;

    /**
     * @return void
     */
    public function initialize()
    {
        $this->databaseHelper = $this->getObjectManager()->get(DatabaseHelper::class);
        $this->queueHelper = $this->getObjectManager()->get(PropertiesQueueHelper::class);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function start()
    {
        if (!$this->propertyHelper->isHidden()) {
            $containerUid = $this->buildContainerContentElement();
            $this->buildLeftContent($containerUid);
            $this->buildRightContent($containerUid);
            $this->log->addMessage(
                'New content elements generated because of FCE for uid '
                . $this->propertyHelper->getPropertyFromRecord('uid')
            );
        }
    }

    /**
     * @return int
     */
    protected function buildContainerContentElement(): int
    {
        $properties = [
            'pid' => $this->propertyHelper->getPropertyFromRecord('pid'),
            'sorting' => ($this->propertyHelper->getPropertyFromRecord('sorting') + 10),
            'CType' => 'gridelements_pi1',
            'header' => $this->getConfigurationByKey('headerLabels.gridContentElement'),
            'header_layout' => 100,
            'colPos' => -1,
            'backupColPos' => -2,
            'tx_gridelements_backend_layout' => $this->getConfigurationByKey('tx_gridelements_backend_layout'),
            'tx_gridelements_children' => 1,
            'tx_gridelements_container' => $this->propertyHelper->getPropertyFromRecord('uid'),
            'tx_gridelements_columns' => 101,
            'hidden' => $this->propertyHelper->getPropertyFromRecord('hidden'),
            'starttime' => $this->propertyHelper->getPropertyFromRecord('starttime'),
            'endtime' => $this->propertyHelper->getPropertyFromRecord('endtime'),
            'fe_group' => $this->propertyHelper->getPropertyFromRecord('fe_group'),
            '_migrated' => 1
        ];
        return $this->databaseHelper->createRecord('tt_content', $properties);
    }

    /**
     * @param int $containerUid
     * @return void
     */
    protected function buildLeftContent(int $containerUid)
    {
        $contentElements = $this->getLeftContentElements();
        if (!empty($contentElements)) {
            $sorting = 100;
            foreach ($contentElements as $contentElementUid) {
                if ($this->isContentElementOnSamePage($contentElementUid)) {
                    $this->moveContentElementToGridContainer($contentElementUid, $sorting, $containerUid, 101);
                } else {
                    $this->createShortcutContentElement($contentElementUid, $sorting, $containerUid, 101);
                }
                $sorting += 100;
            }
        }
    }

    /**
     * @param int $containerUid
     * @return void
     */
    protected function buildRightContent(int $containerUid)
    {
        $contentElements = $this->getRightContentElements();
        if (!empty($contentElements)) {
            $sorting = 100;
            foreach ($contentElements as $contentElementUid) {
                var_dump($contentElementUid);
                if ($this->isContentElementOnSamePage($contentElementUid)) {
                    $this->moveContentElementToGridContainer($contentElementUid, $sorting, $containerUid, 102);
                } else {
                    $this->createShortcutContentElement($contentElementUid, $sorting, $containerUid, 102);
                }
                $sorting += 100;
            }
        }
    }

    /**
     * @param int $contentElementUid
     * @param int $sorting
     * @param int $containerUid
     * @param int $column
     * @return void
     */
    protected function moveContentElementToGridContainer(
        int $contentElementUid,
        int $sorting,
        int $containerUid,
        int $column
    ) {
        $properties = [
            'sorting' => $sorting,
            'hidden' => 0,
            'colPos' => -1,
            'tx_gridelements_container' => $containerUid,
            'tx_gridelements_columns' => $column,
            //'starttime' => $this->propertyHelper->getPropertyFromRecord('starttime'),
            //'endtime' => $this->propertyHelper->getPropertyFromRecord('endtime'),
            //'fe_group' => $this->propertyHelper->getPropertyFromRecord('fe_group'),
            '_migrated' => 1,
        ];
        $this->queueHelper->writeToDatabaseAndQueue('tt_content', $contentElementUid, $properties);
        $this->log->addMessage(
            'Move existing content (' . $contentElementUid . ') to grid (' . $containerUid . ')'
        );
    }

    /**
     * @param int $targetUid
     * @param int $sorting
     * @param int $containerUid
     * @param int $column
     * @return void
     */
    protected function createShortcutContentElement(
        int $targetUid,
        int $sorting,
        int $containerUid,
        int $column
    ) {
        $properties = [
            'sorting' => $sorting,
            'pid' => $this->propertyHelper->getPropertyFromRecord('pid'),
            'CType' => 'shortcut',
            'header' => 'Reference',
            'header_layout' => 100,
            'colPos' => -1,
            'tx_gridelements_container' => $containerUid,
            'tx_gridelements_columns' => $column,
            'records' => $targetUid,
            'hidden' => $this->propertyHelper->getPropertyFromRecord('hidden'),
            'starttime' => $this->propertyHelper->getPropertyFromRecord('starttime'),
            'endtime' => $this->propertyHelper->getPropertyFromRecord('endtime'),
            'fe_group' => $this->propertyHelper->getPropertyFromRecord('fe_group'),
            '_migrated' => 1
        ];
        $uid = $this->databaseHelper->createRecord('tt_content', $properties);
        $this->log->addMessage('created new reference content (' . $uid . ')');
    }

    /**
     * @param string $column
     * @return string
     */
    protected function buildHtml(string $column = 'left'): string
    {
        $standaloneView = $this->getObjectManager()->get(StandaloneView::class);
        $standaloneView->setTemplateSource($this->getTemplateContent($column));
        $standaloneView->assignMultiple(
            [
                'row' => $this->propertyHelper->getProperties(),
                'flexForm' => $this->getFlexFormArray()
            ]
        );
        return $standaloneView->render();
    }

    /**
     * @param string $column
     * @return string
     */
    protected function getTemplateContent(string $column): string
    {
        $absolute = GeneralUtility::getFileAbsFileName($this->getConfigurationByKey('template' . ucfirst($column)));
        return (string)file_get_contents($absolute);
    }

    /**
     * @return array
     */
    protected function getRightContentElements(): array
    {
        $flexFormArray = $this->getFlexFormArray();
        return GeneralUtility::trimExplode(',', $flexFormArray['field_content_right'], true);
    }

    /**
     * @param int $contentUid
     * @return bool
     */
    protected function isContentElementOnSamePage(int $contentUid): bool
    {
        $row = $this->getDatabase()->exec_SELECTgetSingleRow('pid', 'tt_content', 'uid=' . (int)$contentUid);
        return (int)$row['pid'] === (int)$this->propertyHelper->getPropertyFromRecord('pid');
    }

    /**
     * @return array
     */
    protected function getLeftContentElements(): array
    {
        $flexFormArray = $this->getFlexFormArray();
        return GeneralUtility::trimExplode(',', $flexFormArray['field_content_left'], true);
    }

    /**
     * @return array
     */
    protected function getFlexFormArray(): array
    {
        $flexFormService = $this->getObjectManager()->get(FlexFormService::class);
        return (array)$flexFormService->convertFlexFormContentToArray(
            $this->propertyHelper->getPropertyFromRecord('tx_templavoila_flex')
        );
    }
}
