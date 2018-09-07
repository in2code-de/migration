<?php
namespace In2code\Migration\Migration\Migrate\PropertyHelper;

use In2code\Migration\Migration\Helper\DatabaseHelper;

/**
 * Class AddAccordeonContentElementPropertyHelper
 *
 *  Example configuration:
 *      'configuration' => [
 *          'values' => [
 *              'colPos' => 2,
 *              'sorting' => 1000000,
 *              'CType' => 'gridelements_pi1',
 *              'tx_gridelements_backend_layout' => 'accordion'
 *          ],
 *          'condition' => [
 *              'colPos' => 3,
 *              'CType' => 'textpic'
 *          ]
 *      ]
 */
class AddAccordeonContentElementPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * @var string
     */
    protected $headerGrid = 'Accordeon (Migration)';

    /**
     * @return void
     * @throws \Exception
     */
    public function initialize()
    {
        if (!is_array($this->getConfigurationByKey('values')) || !is_array($this->getConfigurationByKey('condition'))) {
            throw new \Exception('wrong configuration given in class' . __CLASS__, 1525439584);
        }
    }

    /**
     * @return void
     */
    public function manipulate()
    {
        $gridIdentifier = $this->getGridContainerElement();
        $this->setProperties(['tx_gridelements_container' => $gridIdentifier, 'tx_gridelements_columns' => 101]);
        $this->setProperty(-1);
    }

    /**
     * @return int
     */
    protected function getGridContainerElement(): int
    {
        if ($this->isAccordeonElementOnCurrentPage()) {
            $uid = $this->getAccordeonElementIdentifierFromCurrentPage();
        } else {
            $uid = $this->addAccordeonElementToCurrentPage();
        }
        return $uid;
    }

    /**
     * @return bool
     */
    public function shouldMigrate(): bool
    {
        foreach ($this->getConfigurationByKey('condition') as $field => $value) {
            if ($this->getPropertyFromRecord($field) !== $value) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return bool
     */
    protected function isAccordeonElementOnCurrentPage(): bool
    {
        return $this->getAccordeonElementIdentifierFromCurrentPage() > 0;
    }

    /**
     * @return int
     */
    protected function getAccordeonElementIdentifierFromCurrentPage(): int
    {
        $row = (array)$this->database->exec_SELECTgetSingleRow(
            'uid',
            'tt_content',
            'pid=' . (int)$this->getPropertyFromRecord('pid')
            . ' and colPos=3 and deleted=0 and header = "' . $this->headerGrid . '"'
        );
        $uid = 0;
        if (!empty($row['uid'])) {
            $uid = (int)$row['uid'];
        }
        return $uid;
    }

    /**
     * @return int
     */
    protected function addAccordeonElementToCurrentPage(): int
    {
        $properties = [
            'header' => $this->headerGrid,
            'header_layout' => 100,
            'pid' => $this->getPropertyFromRecord('pid')
        ];
        /** @var DatabaseHelper $databaseHelper */
        $databaseHelper = $this->getObjectManager()->get(DatabaseHelper::class);
        $uid = $databaseHelper->createRecord('tt_content', $this->getConfigurationByKey('values') + $properties);
        return $uid;
    }
}
