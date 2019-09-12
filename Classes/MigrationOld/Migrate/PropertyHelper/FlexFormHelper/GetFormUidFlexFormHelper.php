<?php
namespace In2code\Migration\MigrationOld\Migrate\PropertyHelper\FlexFormHelper;

use In2code\Powermail\Domain\Model\Form;

/**
 * Class GetFormUidFlexFormHelper
 */
class GetFormUidFlexFormHelper extends AbstractFlexFormHelper implements FlexFormHelperInterface
{

    /**
     * @return int
     */
    public function getVariable(): int
    {
        $formUid = $this->propertyHelper->getPropertyFromRecord('uid');
        $row = $this->getDatabase()->exec_SELECTgetSingleRow('uid', Form::TABLE_NAME, '_migrated_uid=' . $formUid);
        return (int)$row['uid'];
    }
}
