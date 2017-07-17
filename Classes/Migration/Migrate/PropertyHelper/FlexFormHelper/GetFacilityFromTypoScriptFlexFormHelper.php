<?php
namespace In2code\In2template\Migration\Migrate\PropertyHelper\FlexFormHelper;

/**
 * Class GetFacilityFromTypoScriptFlexFormHelper
 */
class GetFacilityFromTypoScriptFlexFormHelper extends AbstractFlexFormHelper implements FlexFormHelperInterface
{

    /**
     * @return string
     */
    public function getVariable(): string
    {
        $variable = '';
        $facilityUid = $this->getFacilityUid();
        if ($facilityUid > 0) {
            $variable = (string)$facilityUid;
        }
        return $variable;
    }

    /**
     * @return int
     */
    protected function getFacilityUid(): int
    {
        $facilityUid = 0;
        $constantsString = $this->getTypoScriptConstantsString();
        preg_match_all(
            '~plugin.tx_pthskaadmin.settings.extlist.facilityId\s*=\s*([0-9]+)~',
            $constantsString,
            $results
        );
        if (!empty($results[1][0])) {
            $facilityUid = (int)$results[1][0];
        }
        return $facilityUid;
    }

    /**
     * Get first typoscript template constants string on current page
     *
     * @return string
     */
    protected function getTypoScriptConstantsString(): string
    {
        $currentPid = $this->propertyHelper->getPropertyFromRecord('pid');
        $row = $this->getDatabase()->exec_SELECTgetSingleRow('constants', 'sys_template', 'pid=' . (int)$currentPid);
        return (string)$row['constants'];
    }
}
