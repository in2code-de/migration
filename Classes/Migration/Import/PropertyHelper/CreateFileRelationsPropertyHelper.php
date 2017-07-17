<?php
namespace In2code\In2template\Migration\Import\PropertyHelper;

use In2code\In2template\Migration\Helper\FileHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class CreateFileRelationsPropertyHelper
 */
class CreateFileRelationsPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * @var string
     */
    protected $newFolder = 'fileadmin/news_files/';

    /**
     * @var string
     */
    protected $oldFolder = 'uploads/media/';

    /**
     * @return void
     */
    protected function manipulate()
    {
        $fileHelper = $this->getObjectManager()->get(FileHelper::class);
        $fileNames = GeneralUtility::trimExplode(',', $this->getPropertyFromOldRecord('news_files'), true);
        foreach ($fileNames as $fileName) {
            $fileHelper->moveFileAndCreateReference(
                $this->oldFolder . $fileName,
                $this->newFolder,
                $this->newTable,
                $this->propertyName,
                $this->getPropertyFromNewRecord('uid')
            );
            $this->log->addMessage('Related file moved and created relation to it (' . $fileName . ')');
        }
    }
}
