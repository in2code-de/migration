<?php
namespace In2code\In2template\Migration\Migrate\PropertyHelper\FceHelper;

use In2code\In2template\Migration\Helper\DatabaseHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\FlexFormService;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Class BuildContentElementFceHelper
 */
class BuildContentElementFceHelper extends AbstractFceHelper implements FceHelperInterface
{

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
    public function start()
    {
        $properties = [
            'pid' => $this->propertyHelper->getPropertyFromRecord('pid'),
            'sorting' => ($this->propertyHelper->getPropertyFromRecord('sorting') + 10),
            'CType' => 'textmedia',
            'header' => $this->getConfigurationByKey('headerLabel'),
            'header_layout' => 100,
            'colPos' => -1,
            'tx_gridelements_container' => $this->propertyHelper->getPropertyFromRecord('uid'),
            'tx_gridelements_columns' => 101,
            'bodytext' => $this->buildHtml(),
            'hidden' => $this->propertyHelper->getPropertyFromRecord('hidden'),
            'starttime' => $this->propertyHelper->getPropertyFromRecord('starttime'),
            'endtime' => $this->propertyHelper->getPropertyFromRecord('endtime'),
            'fe_group' => $this->propertyHelper->getPropertyFromRecord('fe_group'),
            '_migrated' => 1,
        ];
        $this->databaseHelper->createRecord('tt_content', $properties);
        $this->log->addMessage(
            'New content element generated because of FCE for uid '
            . $this->propertyHelper->getPropertyFromRecord('uid')
        );
    }

    /**
     * @return string
     */
    protected function buildHtml(): string
    {
        $standaloneView = $this->getObjectManager()->get(StandaloneView::class);
        $standaloneView->setTemplateSource($this->getTemplateContent());
        $standaloneView->assignMultiple(
            [
                'row' => $this->propertyHelper->getProperties(),
                'flexForm' => $this->getFlexFormArray()
            ]
        );
        return $standaloneView->render();
    }

    /**
     * @return string
     */
    protected function getTemplateContent(): string
    {
        $absolute = GeneralUtility::getFileAbsFileName($this->getConfigurationByKey('template'));
        return (string)file_get_contents($absolute);
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
