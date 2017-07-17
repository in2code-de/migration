<?php
namespace In2code\In2template\Migration\Import\PropertyHelper;

/**
 * Class SetAppointmentVisibilityPropertyHelper
 */
class SetAppointmentVisibilityPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * @return void
     */
    protected function manipulate()
    {
        $this->setShowFacility();
        $this->setShowOrganizer();
        $this->setFeGroups();
        $this->setVisibilityGlobal();
    }

    /**
     * @return void
     */
    protected function setShowFacility()
    {
        $showFacility = 0;
        if ($this->getPropertyFromOldRecord('organizer') === '') {
            if ($this->getPropertyFromOldRecord('facility') > 0) {
                $showFacility = 1;
            }
        } else {
            $showFacility = 0;
        }
        $this->setPropertyByName('show_facility', $showFacility);
    }

    /**
     * @return void
     */
    protected function setShowOrganizer()
    {
        if ($this->getPropertyFromOldRecord('organizer') !== '') {
            $this->setPropertyByName('show_organizer', 1);
        }
    }

    /**
     * @return void
     */
    protected function setFeGroups()
    {
        if (!empty($this->getPropertyFromOldRecord('visible_for_loggedin'))) {
            $this->setPropertyByName('fe_groups', -2);
        }
    }

    /**
     * @return void
     */
    protected function setVisibilityGlobal()
    {
        if (!empty($this->getPropertyFromOldRecord('global_list_visibility_de'))) {
            $this->setPropertyByName('visibility_global', 1);
        }
    }
}
