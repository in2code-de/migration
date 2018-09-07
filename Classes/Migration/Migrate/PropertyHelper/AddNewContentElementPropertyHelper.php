<?php
namespace In2code\Migration\Migration\Migrate\PropertyHelper;

use In2code\Migration\Migration\Helper\DatabaseHelper;

/**
 * Class AddNewContentElementPropertyHelper
 */
class AddNewContentElementPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * @return void
     * @throws \Exception
     */
    public function initialize()
    {
        if (!is_array($this->getConfigurationByKey('conditions'))
            && !is_array($this->getConfigurationByKey('values'))
            && !is_array($this->getConfigurationByKey('add'))) {
            throw new \Exception('Configuration is missing', 1525681846);
        }
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function manipulate()
    {
        if ($this->getConfigurationByKey('add')['before'] === true) {
            $uid = $this->addCeBefore();
            $this->log->addMessage('New content element before current created with uid ' . $uid);
        }
        if ($this->getConfigurationByKey('add')['after'] === true) {
            $uid = $this->addCeAfter();
            $this->log->addMessage('New content element after current created with uid ' . $uid);
        }
    }

    /**
     * @return int
     */
    protected function addCeBefore(): int
    {
        $sorting = (int)$this->getPropertyFromRecord('sorting') - 10;
        return $this->addCe($sorting);
    }

    /**
     * @return int
     */
    protected function addCeAfter(): int
    {
        $sorting = (int)$this->getPropertyFromRecord('sorting') + 10;
        return $this->addCe($sorting);
    }

    /**
     * @param int $sorting
     * @return int
     */
    protected function addCe(int $sorting): int
    {
        $values = [
            'sorting' => $sorting,
            'pid' => $this->getPropertyFromRecord('pid'),
            'colPos' => $this->getPropertyFromRecord('colPos'),
            'tx_gridelements_container' => $this->getPropertyFromRecord('tx_gridelements_container'),
            'tx_gridelements_columns' => $this->getPropertyFromRecord('tx_gridelements_columns'),
            'hidden' => $this->getPropertyFromRecord('hidden'),
            'sys_language_uid' => $this->getPropertyFromRecord('sys_language_uid'),
        ] + $this->getConfigurationByKey('values');
        /** @var DatabaseHelper $databaseHelper */
        $databaseHelper = $this->getObjectManager()->get(DatabaseHelper::class);
        return $databaseHelper->createRecord('tt_content', $values);
    }

    /**
     * @return bool
     */
    public function shouldMigrate(): bool
    {
        $isFitting = true;
        foreach ($this->getConfigurationByKey('condition') as $field => $values) {
            if (!in_array($this->getPropertyFromRecord($field), $values)) {
                $isFitting = false;
                break;
            }
        }
        return $isFitting;
    }
}
