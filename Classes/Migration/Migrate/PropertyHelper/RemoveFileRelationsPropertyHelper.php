<?php

namespace In2code\Migration\Migration\Migrate\PropertyHelper;

/**
 * Class RemoveFileRelationsPropertyHelper
 */
class RemoveFileRelationsPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * @return void
     */
    public function manipulate()
    {
        $this->getDatabase()->exec_DELETEquery(
            'sys_file_reference',
            'uid_foreign=' . $this->getPropertyFromRecord('uid') . ' and tablenames="tt_content" and fieldname="image"'
        );
        $this->log->addMessage(
            'Removed some files from content element with CType ' . $this->getPropertyFromRecord('CType')
        );
        $this->setProperties(['image' => 0] + $this->getProperties());
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function shouldMigrate(): bool
    {
        if ($this->getPropertyFromRecord('image') > 0) {
            $isFitting = true;
            foreach ($this->getConfigurationByKey('conditions') as $field => $values) {
                if (!is_string($field) || !is_array($values)) {
                    throw new \Exception('Possible misconfiguration of configuration of ' . __CLASS__);
                }
                if (!in_array($this->getPropertyFromRecord($field), $values)) {
                    $isFitting = false;
                    break;
                }
            }
            return $isFitting;
        }
        return false;
    }
}
