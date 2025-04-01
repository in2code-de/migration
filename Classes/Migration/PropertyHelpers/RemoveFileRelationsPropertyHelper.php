<?php

declare(strict_types=1);
namespace In2code\Migration\Migration\PropertyHelpers;

use In2code\Migration\Exception\ConfigurationException;
use In2code\Migration\Utility\DatabaseUtility;

/**
 * Class RemoveFileRelationsPropertyHelper to remove sys_file_reference records to a foreign record
 *  'configuration' => [
 *      'tablenames' => 'tt_content',
 *      'fieldname' => null, // can be null for any value
 *      'conditions' => [
 *          'CType' => [
 *              'text',
 *              'header',
 *              'html',
 *              'table'
 *          ]
 *      ],
 *  ]
 */
class RemoveFileRelationsPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{
    /**
     * @var array
     */
    protected array $checkForConfiguration = [
        'conditions',
        'tablenames',
        'fieldname',
    ];

    public function manipulate(): void
    {
        $whereClause = 'uid_foreign=' . $this->getPropertyFromRecord('uid')
            . ' and tablenames="' . $this->getConfigurationByKey('tablenames') . '"';
        if ($this->getConfigurationByKey('fieldname') !== null) {
            $whereClause .= ' and fieldname="' . $this->getConfigurationByKey('fieldname') . '"';
        }
        $queryBuilder = DatabaseUtility::getQueryBuilderForTable('sys_file_reference');
        $queryBuilder->delete('sys_file_reference')->where($whereClause)->executeQuery();
    }

    /**
     * @return bool
     * @throws ConfigurationException
     */
    public function shouldMigrate(): bool
    {
        return $this->shouldMigrateByDefaultConditions();
    }
}
