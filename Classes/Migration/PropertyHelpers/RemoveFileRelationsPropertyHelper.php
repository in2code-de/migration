<?php
namespace In2code\Migration\Migration\PropertyHelpers;

use In2code\Migration\Migration\Exception\ConfigurationException;
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
    protected $checkForConfiguration = [
        'conditions',
        'tablenames',
        'fieldname'
    ];

    /**
     * @return void
     * @throws \Exception
     */
    public function manipulate(): void
    {
        $whereClause = 'uid_foreign=' . $this->getPropertyFromRecord('uid')
            . ' and tablenames="' . $this->getConfigurationByKey('tablenames') . '"';
        if ($this->getConfigurationByKey('fieldname') !== null) {
            $whereClause .= ' and fieldname="' . $this->getConfigurationByKey('fieldname') . '"';
        }
        $queryBuilder = DatabaseUtility::getQueryBuilderForTable('sys_file_reference');
        $queryBuilder->delete('sys_file_reference')->where($whereClause)->execute();
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function shouldMigrate(): bool
    {
        $isFitting = true;
        foreach ($this->getConfigurationByKey('conditions') as $field => $values) {
            if (!is_string($field) || !is_array($values)) {
                throw new ConfigurationException('Possible misconfiguration in ' . __CLASS__, 1569323862);
            }
            if (!in_array($this->getPropertyFromRecord($field), $values)) {
                $isFitting = false;
                break;
            }
        }
        return $isFitting;
    }
}
