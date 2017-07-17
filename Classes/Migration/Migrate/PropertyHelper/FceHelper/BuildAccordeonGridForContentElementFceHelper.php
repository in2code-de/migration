<?php
namespace In2code\In2template\Migration\Migrate\PropertyHelper\FceHelper;

use In2code\In2template\Migration\Helper\DatabaseHelper;
use TYPO3\CMS\Extbase\Service\FlexFormService;

/**
 * Class BuildAccordeonGridForContentElementFceHelper
 */
class BuildAccordeonGridForContentElementFceHelper extends AbstractFceHelper implements FceHelperInterface
{

    /**
     * Take this header if no h4 in FlexForm bodytext can be located
     *
     * @var string
     */
    protected $defaultTitle = 'Akkordeon';

    /**
     * @var array
     */
    protected $properties = [];

    /**
     * @return void
     * @throws \Exception
     */
    public function start()
    {
        $this->properties = $this->propertyHelper->getProperties();
        if ($this->isAccordeonContent() || $this->getConfigurationByKey('enforceDummyContainer') === true) {
            $this->changeCurrentContentElementToContainer();
            $newContentUid = $this->buildChildrenContentElement();
            $this->changeImageRelationsFromParentToChildren($newContentUid);
        }
    }

    /**
     * @return void
     */
    protected function changeCurrentContentElementToContainer()
    {
        $properties = $this->propertyHelper->getProperties();
        $newProperties = [
            'CType' => 'gridelements_pi1',
            'header' => $this->getAccordeonTitle(),
            'header_layout' => 2,
            'backupColPos' => -2,
            'tx_gridelements_backend_layout' => 'accordion',
            'tx_gridelements_children' => 1,
            '_migrated' => 1
        ];
        if (!$this->isAccordeonContent() && $this->getConfigurationByKey('enforceDummyContainer') === true) {
            $newProperties['tx_gridelements_backend_layout'] = 'dummy';
            $newProperties['header'] = 'Container';
            $newProperties['header_layout'] = 100;
        }
        $this->propertyHelper->setProperties($newProperties + $properties);
    }

    /**
     * @param int $newContentUid
     * @return void
     */
    protected function changeImageRelationsFromParentToChildren(int $newContentUid)
    {
        $this->getDatabase()->exec_UPDATEquery(
            'sys_file_reference',
            'uid_foreign=' . $this->propertyHelper->getPropertyFromRecord('uid')
            . ' and tablenames="tt_content" and fieldname="assets" and deleted=0',
            ['uid_foreign' => $newContentUid]
        );
    }

    /**
     * @return int
     */
    protected function buildChildrenContentElement(): int
    {
        $properties = $this->properties;
        unset($properties['uid']);
        $newProperties = [
            'CType' => 'textmedia',
            'header_layout' => 100,
            'colPos' => -1,
            'tx_gridelements_container' => $this->propertyHelper->getPropertyFromRecord('uid'),
            'tx_gridelements_columns' => 101,
            'backupColPos' => -2,
            'tx_templavoila_flex' => $this->removeFirstH4FromFlexFormBodyText(),
            'bodytext' => $this->removeFirstH4FromBodyText(),
            '_migrated' => 1
        ];
        $databaseHelper = $this->getObjectManager()->get(DatabaseHelper::class);
        return $databaseHelper->createRecord('tt_content', $newProperties + $properties);
    }

    /**
     * @return string
     */
    protected function getAccordeonTitle(): string
    {
        $tvConfiguration = $this->getTvFlexFormValues();
        $bodyText = (string)$tvConfiguration['field_text'];
        if (empty($bodyText)) {
            $bodyText = (string)$tvConfiguration['field_text_top'];
        }
        preg_match_all('~<h4>(.*)</h4>~i', $bodyText, $results);
        if (!empty($results[1][0])) {
            return html_entity_decode(strip_tags($results[1][0]));
        } else {
            $this->log->addError(
                'Could not find a H4 for accordeon in tt_content.uid='
                . $this->propertyHelper->getPropertyFromRecord('uid') . ' - using default title "'
                . $this->defaultTitle . '"'
            );
        }
        return $this->defaultTitle;
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
        $tvConfiguration = $this->getTvFlexFormValues();
        return !empty($tvConfiguration['field_accordion']);
    }

    /**
     * @return array
     */
    protected function getTvFlexFormValues(): array
    {
        $flexFormService = $this->getObjectManager()->get(FlexFormService::class);
        return (array)$flexFormService->convertFlexFormContentToArray(
            $this->propertyHelper->getPropertyFromRecord('tx_templavoila_flex')
        );
    }
}
