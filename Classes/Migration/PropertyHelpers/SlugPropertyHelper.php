<?php
declare(strict_types=1);
namespace In2code\Migration\Migration\PropertyHelpers;

use Doctrine\DBAL\Driver\Exception;
use In2code\Migration\Exception\ConfigurationException;
use In2code\Migration\Migration\Helper\DatabaseHelper;
use In2code\Migration\Utility\TcaUtility;
use Throwable;
use TYPO3\CMS\Core\DataHandling\Model\RecordState;
use TYPO3\CMS\Core\DataHandling\Model\RecordStateFactory;
use TYPO3\CMS\Core\DataHandling\SlugHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class SlugPropertyHelper
 * to set a new slug based on a fieldname. If pages is the table with the slug, automatically parent pages will be used
 * to create a slug
 *
 *  Configuration example 1:
 *      'configuration' => [
 *          'conditions' => [
 *              'CType' => [
 *                  'text',
 *                  'header'
 *              ]
 *          ]
 *      ]
 *
 *  Configuration example 2:
 *      'configuration' => [
 *          'conditionsNegate' => [
 *              'uid' => [
 *                  '1'
 *              ]
 *          ]
 *      ]
 *
 *  Configuration example 3:
 *      'configuration' => [
 *          'startIdentifiers' => [
 *              '1',
 *              '1000'
 *          ]
 *      ]
 */
class SlugPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{
    public function manipulate(): void
    {
        try {
            $slugHelper = GeneralUtility::makeInstance(
                SlugHelper::class,
                $this->table,
                $this->propertyName,
                TcaUtility::getTcaOfField($this->getPropertyName(), $this->table)['config']
            );
            $slug = $slugHelper->generate($this->getProperties(), (int)$this->getPropertyFromRecord('pid'));
            $uniqueSlug = $slugHelper->buildSlugForUniqueInSite($slug, $this->getRecordState());
            $this->setProperty($uniqueSlug);
            $this->log->addMessage('Set new slug "' . $uniqueSlug . '"');
        } catch (Throwable $exception) {
            $this->log->addError($exception->getMessage(), $this->getProperties(), $this->table);
        }
    }

    protected function getRecordState(): RecordState
    {
        return GeneralUtility::makeInstance(RecordStateFactory::class, $this->table)
            ->fromArray($this->getProperties());
    }

    /**
     * @return bool
     * @throws ConfigurationException
     * @throws Exception
     */
    public function shouldMigrate(): bool
    {
        return $this->shouldMigrateByDefaultConditions() && $this->shouldMigrateByNegateConditions()
            && $this->shouldMigrateByStartIdentifiers();
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

    /**
     *  'configuration' => [
     *      'startIdentifiers' => [
     *          '1',
     *          '1000'
     *      ]
     *  ]
     *
     * @return bool
     * @throws Exception
     */
    protected function shouldMigrateByStartIdentifiers(): bool
    {
        $isFitting = true;
        if ($this->getConfigurationByKey('startIdentifiers') !== null) {
            $databaseHelper = GeneralUtility::makeInstance(DatabaseHelper::class);
            $rootline = $databaseHelper->getRootline($this->getPropertyFromRecord('uid'));
            $delta = array_intersect($rootline, $this->getConfigurationByKey('startIdentifiers'));
            return count($delta) > 0;
        }
        return $isFitting;
    }
}
