<?php
namespace In2code\In2template\Migration\Migrate\PropertyHelper;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\FlexFormService;

/**
 * Class UpdateColPosFromTemplaVoilaPageSettingsPropertyHelper
 * updates tt_content.colPos from tv pages -> content relation
 *
 * Example configuration:
 *      [
 *          'className' => UpdateColPosFromTemplaVoilaPageSettingsPropertyHelper::class,
 *          'configuration' => [
 *              'colPosMapping' => [
 *                  'field_content' => 0,
 *                  'field_sidebarRight' => 2
 *              ],
 *              'ifNotMatching' => [
 *                  'hidden' => 1
 *              ]
 *          ]
 *      ]
 */
class UpdateColPosFromTemplaVoilaPageSettingsPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * @var array
     */
    protected $tvFfKeyReferences = [
        'field_content_left',
        'field_content_right'
    ];

    /**
     * @return void
     * @throws \Exception
     */
    public function initialize()
    {
        if ($this->getConfigurationByKey('colPosMapping') === null
            || $this->getConfigurationByKey('ifNotMatching') === null
        ) {
            throw new \Exception('configuration is missing in ' . __CLASS__);
        }
    }

    /**
     * @return void
     * @throws \Exception
     */
    protected function manipulate()
    {
        if ($this->shouldResetColPosAndTurnToHidden()) {
            $this->setProperty($this->getColPos());
        }
    }

    /**
     * @return int
     */
    protected function getColPos(): int
    {
        $flexFormArray = $this->getFlexFormValuesFromCurrentPage();
        foreach ($this->getConfigurationByKey('colPosMapping') as $oldKey => $value) {
            if (array_key_exists($oldKey, $flexFormArray)) {
                $relatedContents = GeneralUtility::trimExplode(',', $flexFormArray[$oldKey], true);
                if (in_array($this->getPropertyFromRecord('uid'), $relatedContents)) {
                    return (int)$value;
                }
            }
        }
        $this->notMatchingProcedure();
        return 0;
    }

    /**
     * If content element was not found in current page relations of templavoila
     *
     * @return void
     */
    protected function notMatchingProcedure()
    {
        $this->log->addNote('Turn not related content element to hidden=1');
        $this->setProperties($this->getConfigurationByKey('ifNotMatching'));
    }

    /**
     * @return array
     */
    protected function getFlexFormValuesFromCurrentPage(): array
    {
        $row = $this->getDatabase()->exec_SELECTgetSingleRow(
            'tx_templavoila_flex',
            'pages',
            'uid=' . (int)$this->getPropertyFromRecord('pid')
        );
        return $this->getFlexFormValuesFromString($row['tx_templavoila_flex']);
    }

    /**
     * @param string $string
     * @return array
     */
    protected function getFlexFormValuesFromString(string $string): array
    {
        $flexFormService = $this->getObjectManager()->get(FlexFormService::class);
        return (array)$flexFormService->convertFlexFormContentToArray($string);
    }

    /**
     * @return bool
     */
    protected function shouldResetColPosAndTurnToHidden(): bool
    {
        return !$this->isInGridelementsGridContainer() && !$this->isInTvGridContainer();
    }

    /**
     * @return bool
     */
    protected function isInGridelementsGridContainer(): bool
    {
        return !empty($this->getPropertyFromRecord('tx_gridelements_container'));
    }

    /**
     * Check if given UID is anywhere in tx_templavoila_flex
     *
     * @return bool
     */
    protected function isInTvGridContainer(): bool
    {
        $rows = (array)$this->getDatabase()->exec_SELECTgetRows(
            'tx_templavoila_flex',
            'tt_content',
            'pid = ' . $this->getPropertyFromRecord('pid')
            . ' and deleted = 0 and tx_templavoila_flex like "%' . $this->getPropertyFromRecord('uid') . '%"'
        );
        $referencedFields = [];
        foreach ($rows as $row) {
            $flexFormArray = $this->getFlexFormValuesFromString($row['tx_templavoila_flex']);
            foreach ($this->tvFfKeyReferences as $key) {
                if (!empty($flexFormArray[$key])) {
                    $fields = GeneralUtility::intExplode(',', $flexFormArray[$key], true);
                    $referencedFields = $fields + $referencedFields;
                }
            }
        }
        return in_array($this->getPropertyFromRecord('uid'), $referencedFields);
    }
}
