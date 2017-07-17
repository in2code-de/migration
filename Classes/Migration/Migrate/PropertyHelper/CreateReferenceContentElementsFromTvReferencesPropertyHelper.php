<?php
namespace In2code\In2template\Migration\Migrate\PropertyHelper;

use In2code\In2template\Migration\Helper\DatabaseHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\FlexFormService;

/**
 * Class CreateReferenceContentElementsFromTvReferencesPropertyHelper
 */
class CreateReferenceContentElementsFromTvReferencesPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * Map columnKeys to tt_content.colPos
     *
     * @var array
     */
    protected $mapping = [
        'field_content' => 0,
        'field_sidebarRight' => 1
    ];

    /**
     * @var string
     */
    protected $headerReference = 'Referenz';

    /**
     * @var null|DatabaseHelper
     */
    protected $databaseHelper = null;

    /**
     * @return void
     */
    public function initialize()
    {
        $this->databaseHelper = $this->getObjectManager()->get(DatabaseHelper::class);
    }

    /**
     * @return void
     * @throws \Exception
     */
    protected function manipulate()
    {
        $contentElements = $this->getAllContentElementUids();
        $this->log->addNote('Rewriting all content sorting fields because of external references');
        foreach ($contentElements as $contentElement) {
            if (!$this->isContentOnSamePage($contentElement)) {
                $this->createReferenceElement(
                    $contentElement['uid'],
                    $contentElement['colPos'],
                    $contentElement['sorting']
                );
            }
            $this->updateContentElementWithNewSorting($contentElement['uid'], $contentElement['sorting']);
        }
    }

    /**
     * @param int $targetUid
     * @param int $colPos
     * @param int $sorting
     * @return void
     */
    protected function createReferenceElement(int $targetUid, int $colPos, int $sorting)
    {
        $properties = [
            'pid' => $this->getPropertyFromRecord('uid'),
            'sorting' => $sorting,
            'colPos' => $colPos,
            'CType' => 'shortcut',
            'header' => $this->getHeaderString(),
            'header_layout' => 100,
            'records' => $targetUid,
            '_migrated' => 1
        ];
        $uid = $this->databaseHelper->createRecord('tt_content', $properties);
        $this->log->addMessage('create new reference element uid ' . $uid . ' to element uid ' . $targetUid);
    }

    /**
     * @param int $uid
     * @param int $sorting
     * @return void
     */
    protected function updateContentElementWithNewSorting(int $uid, int $sorting)
    {
        $this->getDatabase()->exec_UPDATEquery('tt_content', 'uid=' . $uid, ['sorting' => $sorting]);
    }

    /**
     * [
     *      [
     *          'uid' => 123, // current or target element uid
     *          'pid' => 12, // current or target element pid
     *          'sorting' => 200, // change sorting to this value
     *          'colPos' => 1, // move new element to this colPos
     *          'column' => 'field_content'
     *      ],
     *      [
     *          'uid' => 124,
     *          'pid' => 11,
     *          'sorting' => 300,
     *          'colPos' => 0,
     *          'column' => 'field_content'
     *      ]
     * ]
     *
     * @return array
     */
    protected function getAllContentElementUids(): array
    {
        $contentElements = [];
        $sorting = 100;
        $flexFormArray = $this->getFlexFormValues();
        foreach ($this->mapping as $column => $colPos) {
            if (!empty($flexFormArray[$column])) {
                $currentUids = GeneralUtility::intExplode(',', $flexFormArray[$column]);
                foreach ($currentUids as $uid) {
                    $row = $this->getDatabase()->exec_SELECTgetSingleRow(
                        'pid, tx_templavoila_to',
                        'tt_content',
                        'uid=' . (int)$uid
                    );
                    $contentElements[] = [
                        'uid' => $uid,
                        'pid' => (int)$row['pid'],
                        'sorting' => $sorting,
                        'colPos' => $colPos,
                        'column' => $column,
                        'tx_templavoila_to' => (int)$row['tx_templavoila_to']
                    ];
                    $sorting += 10;
                }
            }
        }
        return $contentElements;
    }

    /**
     * @return string
     */
    protected function getHeaderString(): string
    {
        $header = $this->headerReference;
        $originalHeader = $this->getPropertyFromRecord('header');
        if (!empty($originalHeader)) {
            $header .= ' (' . $originalHeader . ')';
        }
        return $header;
    }

    /**
     * @param array $contentElements
     * @return bool
     */
    protected function areThereAnyReferencesOnCurrentPage(array $contentElements): bool
    {
        $referencesFound = false;
        foreach ($contentElements as $contentElement) {
            if (!$this->isContentOnSamePage($contentElement)) {
                $referencesFound = true;
                break;
            }
        }
        return $referencesFound;
    }

    /**
     * @param array $contentElement
     * @return bool
     */
    protected function isContentOnSamePage(array $contentElement): bool
    {
        return (int)$contentElement['pid'] === (int)$this->getPropertyFromRecord('uid');
    }

    /**
     * @return array
     */
    protected function getFlexFormValues(): array
    {
        $flexFormService = $this->getObjectManager()->get(FlexFormService::class);
        return (array)$flexFormService->convertFlexFormContentToArray(
            $this->getPropertyFromRecord('tx_templavoila_flex')
        );
    }
}
