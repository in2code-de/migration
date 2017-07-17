<?php
namespace In2code\In2template\Migration\Migrate\PropertyHelper;

use In2code\In2template\Migration\Helper\DatabaseHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class BuildRelationFacilityPropertyHelper
 */
class BuildRelationFacilityPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * be_groups.uid => tx_facilities_domain_model_facility.uid
     *
     * @var array
     */
    protected $mapping = [
        8 => 43,
        14 => 12,
        15 => 13,
        17 => 15,
        18 => 16,
        19 => 17,
        20 => 18,
        21 => 19,
        23 => 20,
        24 => 21,
        25 => 22,
        26 => 23,
        27 => 24,
        29 => 26,
        30 => 28,
        31 => 29,
        32 => 30,
        34 => 32,
        37 => 38,
        38 => 42,
        288 => 34,
        344 => 46,
        361 => 47,
        364 => 25,
        366 => 63,
        381 => 64,
        294 => 44,
        16 => 44,
        301 => 45,
        320 => 50,
        28 => 48,
        363 => 48,
        2 => 6,
        9 => 9
    ];

    /**
     * @var null|DatabaseHelper
     */
    protected $databaseHelper = null;

    /**
     * @return void
     * @throws \Exception
     */
    protected function manipulate()
    {
        if ($this->getProperty()) {
            $facilityIdentifiers = GeneralUtility::intExplode(',', $this->getProperty(), true);
            foreach ($facilityIdentifiers as $facilityIdentifier) {
                $this->createRelationRecord($facilityIdentifier);
            }
        }
    }

    /**
     * @param int $backendUserGroup
     * @return void
     */
    protected function createRelationRecord(int $backendUserGroup)
    {
        $facilityIdentifier = $this->getFacilityIdentifierFromBackendUserGroup($backendUserGroup);
        if ($facilityIdentifier > 0) {
            $properties = [
                'uid_local' => $this->getPropertyFromRecord('uid'),
                'uid_foreign' => $facilityIdentifier
            ];
            $this->databaseHelper->createRecord($this->getRelationTable(), $properties);
            $this->log->addMessage('Create relation in ' . $this->table);
        } else {
            $this->log->addError('No facility found in mapping table to be_groups.uid=' . $backendUserGroup);
        }
    }

    /**
     * @param int $backendUserGroup
     * @return int
     */
    protected function getFacilityIdentifierFromBackendUserGroup(int $backendUserGroup): int
    {
        $facilityIdentifier = 0;
        if (array_key_exists($backendUserGroup, $this->mapping)) {
            $facilityIdentifier = $this->mapping[$backendUserGroup];
        }
        return $facilityIdentifier;
    }

    /**
     * @return string
     * @throws \Exception
     */
    protected function getRelationTable(): string
    {
        if (empty($this->getConfigurationByKey('relationTable'))) {
            throw new \Exception('Relation table missing in configuration');
        }
        return $this->getConfigurationByKey('relationTable');
    }

    /**
     * @return void
     */
    public function initialize()
    {
        $this->databaseHelper = $this->getObjectManager()->get(DatabaseHelper::class);
    }
}
