<?php

namespace In2code\Migration\MigrationOld\Migrate\PropertyHelper;

use In2code\Migration\MigrationOld\Helper\DatabaseHelper;

/**
 * Class AddCategoryRelationsForEventPropertyHelper
 */
class AddCategoryRelationsForEventPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * Map tx_cal_category.uid => sys_category.uid
     *
     * @var array
     */
    protected $categoryMapping = [
        65 => 713,
        43 => 692,
        61 => 710,
        47 => 699,
        45 => 697,
        67 => 715,
        55 => 706,
        49 => 702,
        51 => 703,
        63 => 711,
        59 => 709,
        53 => 704,
        41 => 690,
        57 => 707
    ];

    /**
     * @return void
     */
    public function manipulate()
    {
        $relations = $this->getOldCategoryRelations();
        $this->addNewCategoryRelations($relations);
    }

    /**
     * @return array
     */
    protected function getOldCategoryRelations(): array
    {
        $rows = $this->getDatabase()->exec_SELECTgetRows(
            'uid_foreign',
            'tx_cal_event_category_mm',
            'uid_local=' . $this->getPropertyFromRecord('uid')
        );
        $relations = [];
        if (!empty($rows)) {
            foreach ($rows as $row) {
                $relations[] = (int)$row['uid_foreign'];
            }
        }
        return $relations;
    }

    /**
     * @param array $relations
     * @return void
     */
    protected function addNewCategoryRelations(array $relations)
    {
        /** @var DatabaseHelper $databaseHelper */
        $databaseHelper = $this->getObjectManager()->get(DatabaseHelper::class);
        foreach ($relations as $relation) {
            if (array_key_exists($relation, $this->categoryMapping)) {
                $sysCategoryUid = $this->categoryMapping[$relation];
                $databaseHelper->createRecord(
                    'sys_category_record_mm',
                    [
                        'uid_local' => $sysCategoryUid,
                        'uid_foreign' => $this->getMigratedNewsRecordUid(),
                        'tablenames' => 'tx_news_domain_model_news',
                        'fieldname' => 'categories'
                    ]
                );
            }
        }
    }

    /**
     * @return int
     */
    protected function getMigratedNewsRecordUid(): int
    {
        $row = $this->getDatabase()->exec_SELECTgetSingleRow(
            'uid',
            'tx_news_domain_model_news',
            '_migrated_table="tx_cal_event" and _migrated_uid = ' . $this->getPropertyFromRecord('uid')
        );
        if (!empty($row['uid'])) {
            return (int)$row['uid'];
        }
        return 0;
    }

    /**
     * @return bool
     */
    public function shouldImport(): bool
    {
        return $this->getProperty() > 0;
    }
}
