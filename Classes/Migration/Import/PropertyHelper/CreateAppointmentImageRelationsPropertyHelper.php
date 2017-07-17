<?php
namespace In2code\In2template\Migration\Import\PropertyHelper;

use In2code\In2template\Migration\Helper\FileHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class CreateAppointmentImageRelationsPropertyHelper
 */
class CreateAppointmentImageRelationsPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * @var string
     */
    protected $newFolder = 'fileadmin/appointment_images/';

    /**
     * @var string
     */
    protected $oldFolder = 'uploads/tx_in2hskaadmin/';

    /**
     * @return void
     */
    protected function manipulate()
    {
        $fileHelper = $this->getObjectManager()->get(FileHelper::class);
        $imageNames = $this->getImageNames();
        foreach ($imageNames as $imageName) {
            $fileHelper->moveFileAndCreateReference(
                $this->oldFolder . $imageName,
                $this->newFolder,
                $this->newTable,
                $this->propertyName,
                $this->getPropertyFromNewRecord('uid')
            );
            $this->log->addMessage('Image moved and created relation to it (' . $imageName . ')');
        }
        $this->setProperty(count($imageNames));
    }

    /**
     * @return array
     */
    protected function getImageNames(): array
    {
        return GeneralUtility::trimExplode(',', $this->getPropertyFromOldRecord('image'), true);
    }
}
