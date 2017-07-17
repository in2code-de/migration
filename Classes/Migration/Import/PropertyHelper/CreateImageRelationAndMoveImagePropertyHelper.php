<?php
namespace In2code\In2template\Migration\Import\PropertyHelper;

use In2code\In2template\Migration\Helper\FileHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class CreateImageRelationAndMoveImagePropertyHelper
 */
class CreateImageRelationAndMoveImagePropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * @var string
     */
    protected $newFolder = 'fileadmin/news_images/';

    /**
     * @var string
     */
    protected $oldFolder = 'uploads/pics/';

    /**
     * @return void
     */
    protected function manipulate()
    {
        $fileHelper = $this->getObjectManager()->get(FileHelper::class);
        $imageNames = $this->getImageNames();
        foreach ($imageNames as $key => $imageName) {
            $fileHelper->moveFileAndCreateReference(
                $this->oldFolder . $imageName,
                $this->newFolder,
                $this->newTable,
                $this->propertyName,
                $this->getPropertyFromNewRecord('uid'),
                $this->getAdditionalProperties($key)
            );
            $this->log->addMessage('Image moved and created relation to it (' . $imageName . ')');
        }
    }

    /**
     * @param int $key
     * @return array
     */
    protected function getAdditionalProperties(int $key): array
    {
        $titleTexts = $this->getTitleTexts();
        $altTexts = $this->getAltTexts();
        $imageCaptions = $this->getImageCaptions();
        $links = $this->getImageLinks();

        $additionalProperties = ['showinpreview' => 1];
        if (array_key_exists($key, $titleTexts)) {
            $additionalProperties['title'] = $titleTexts[$key];
        }
        if (array_key_exists($key, $altTexts)) {
            $additionalProperties['alternative'] = $altTexts[$key];
        }
        if (array_key_exists($key, $imageCaptions)) {
            $additionalProperties['description'] = $imageCaptions[$key];
        }
        if (array_key_exists($key, $links)) {
            $additionalProperties['link'] = $links[$key];
        }
        return $additionalProperties;
    }

    /**
     * @return array
     */
    protected function getImageNames(): array
    {
        return GeneralUtility::trimExplode(',', $this->getPropertyFromOldRecord('image'), true);
    }

    /**
     * @return array
     */
    protected function getTitleTexts(): array
    {
        return GeneralUtility::trimExplode(PHP_EOL, $this->getPropertyFromOldRecord('imagetitletext'), true);
    }

    /**
     * @return array
     */
    protected function getAltTexts(): array
    {
        return GeneralUtility::trimExplode(PHP_EOL, $this->getPropertyFromOldRecord('imagealttext'), true);
    }

    /**
     * @return array
     */
    protected function getImageCaptions(): array
    {
        return GeneralUtility::trimExplode(PHP_EOL, $this->getPropertyFromOldRecord('imagecaption'), true);
    }

    /**
     * @return array
     */
    protected function getImageLinks(): array
    {
        return GeneralUtility::trimExplode(PHP_EOL, $this->getPropertyFromOldRecord('links'), true);
    }
}
