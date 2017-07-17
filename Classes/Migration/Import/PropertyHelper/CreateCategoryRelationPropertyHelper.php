<?php
namespace In2code\In2template\Migration\Import\PropertyHelper;

use In2code\In2template\Migration\Helper\DatabaseHelper;

/**
 * Class CreateCategoryRelationPropertyHelper
 */
class CreateCategoryRelationPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * @var string
     */
    protected $newTableName = 'sys_category_record_mm';

    /**
     * @var string
     */
    protected $oldTableName = 'tt_news_cat_mm';

    /**
     * @return void
     */
    protected function manipulate()
    {
        $databaseHelper = $this->getObjectManager()->get(DatabaseHelper::class);
        $newsUid = $this->getPropertyFromNewRecord('uid');
        $rows = $this->database->exec_SELECTgetRows('*', $this->oldTableName, 'uid_local=' . (int)$newsUid);
        foreach ($rows as $row) {
            $newRow = [
                'uid_foreign' => $row['uid_local'],
                'uid_local' => $row['uid_foreign'],
                'sorting' => $row['sorting'],
                'tablenames' => $this->newTable,
                'fieldname' => $this->propertyName
            ];
            $databaseHelper->createRecord($this->newTableName, $newRow);
            $this->log->addMessage('New relation to category with uid ' . $row['uid_foreign'] . ' created');
        }
    }
}
