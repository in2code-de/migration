<?php
declare(strict_types=1);
namespace In2code\Migration\Migration\PropertyHelpers;

use In2code\Migration\Exception\ConfigurationException;
use In2code\Migration\Utility\ObjectUtility;
use In2code\Migration\Utility\TcaUtility;
use TYPO3\CMS\Core\DataHandling\SlugHelper;

/**
 * Class SlugPropertyHelper
 * to set a new slug based on a fieldname. If pages is the table with the slug, automatically parent pages will be used
 * to create a slug
 *
 *  Configuration examples:
 *      'configuration' => [
 *          'conditions' => [
 *              'CType' => [
 *                  'text',
 *                  'header',
 *                  'html',
 *                  'table'
 *              ]
 *          ]
 *      ]
 *
 *      'configuration' => [
 *          'conditions' => [
 *              'CType' => [
 *                  'text'
 *              ]
 *          ],
 *          'conditionsNegate' => [
 *              'uid' => [
 *                  '1'
 *              ]
 *          ]
 *      ]
 */
class SlugPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{
    /**
     * @var array
     */
    protected $checkForConfiguration = [
        'conditions'
    ];

    /**
     * @return void
     */
    public function manipulate(): void
    {
        $slugHelper = ObjectUtility::getObjectManager()->get(
            SlugHelper::class,
            $this->table,
            $this->propertyName,
            TcaUtility::getTcaOfField($this->getPropertyName(), $this->table)['config']
        );
        $slug = $slugHelper->generate($this->getProperties(), $this->getPropertyFromRecord('pid'));
        $this->setProperty($slug);
    }

    /**
     * @return bool
     * @throws ConfigurationException
     */
    public function shouldMigrate(): bool
    {
        return $this->shouldMigrateByDefaultConditions() && $this->shouldMigrateByNegateConditions();
    }

    /**
     *  'configuration' => [
     *      'conditionsNegate' => [
     *          'uid' => [
     *              '1'
     *          ]
     *      ]
     *  ]
     *
     * @return bool
     */
    protected function shouldMigrateByNegateConditions(): bool
    {
        $isFitting = true;
        if ($this->getConfigurationByKey('conditionsNegate') !== null) {
            foreach ($this->getConfigurationByKey('conditionsNegate') as $field => $values) {
                if (in_array($this->getPropertyFromRecord($field), $values)) {
                    $isFitting = false;
                    break;
                }
            }
        }
        return $isFitting;
    }
}
