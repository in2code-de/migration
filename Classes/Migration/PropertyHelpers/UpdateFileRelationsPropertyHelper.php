<?php

declare(strict_types=1);

namespace In2code\Migration\Migration\PropertyHelpers;

use In2code\Migration\Migration\Helper\FileHelper;
use In2code\Migration\Utility\StringUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class UpdateFileRelationsPropertyHelper
 *
 *  Configuration example:
 *      [
 *          'className' => UpdateFileRelationsPropertyHelper::class,
 *          'configuration' => [
 *              'conditions' => [
 *                  'CType' => [
 *                      'textmedia',
 *                  ],
 *              ],
 *              'search' => [
 *                  'tablenames' => 'table',
 *                  'fieldname' => 'fieldname',
 *                  'uid_foreign' => '{properties.uid}',
 *              ],
 *              'newProperties' => [
 *                  'tablenames' => 'newTable',
 *                  'fieldname' => 'newField',
 *              ],
 *          ],
 *      ],
 */
class UpdateFileRelationsPropertyHelper extends AbstractPropertyHelper
{
    protected array $checkForConfiguration = [
        'conditions',
        'search',
        'newProperties',
    ];

    public function manipulate(): void
    {
        $fileHelper = GeneralUtility::makeInstance(FileHelper::class);
        $fileHelper->updateFileRelation(
            StringUtility::parseString($this->getConfigurationByKey('search.tablenames'), ['properties' => $this->getProperties()]),
            StringUtility::parseString($this->getConfigurationByKey('search.fieldname'), ['properties' => $this->getProperties()]),
            (int)StringUtility::parseString($this->getConfigurationByKey('search.uid_foreign'), ['properties' => $this->getProperties()]),
            $this->getConfigurationByKey('newProperties'),
        );
    }

    public function shouldMigrate(): bool
    {
        return $this->shouldMigrateByDefaultConditions();
    }
}
