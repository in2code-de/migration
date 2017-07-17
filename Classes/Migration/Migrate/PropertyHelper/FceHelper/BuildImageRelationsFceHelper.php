<?php
namespace In2code\In2template\Migration\Migrate\PropertyHelper\FceHelper;

use In2code\In2template\Migration\Helper\FileHelper;
use In2code\In2template\Migration\Helper\ImageHelper;

/**
 * Class BuildImageRelationsFceHelper
 */
class BuildImageRelationsFceHelper extends AbstractFceHelper implements FceHelperInterface
{

    /**
     * @var string
     */
    protected $targetFolder = 'fileadmin/fce_contentelements/';

    /**
     * @return void
     */
    public function start()
    {
        $imageHelper = $this->getObjectManager()->get(ImageHelper::class);
        $images = $imageHelper->getImages(
            $this->getFlexFormArray(),
            $this->getConfigurationByKey('flexFormKey1'),
            $this->getConfigurationByKey('flexFormKey2'),
            'all',
            (array)$this->getConfigurationByKey('tvMappingConfiguration')
        );
        if (!empty($images)) {
            $fileHelper = $this->getObjectManager()->get(FileHelper::class);
            foreach ($images as $imageConfiguration) {
                $additionalProperties = $imageConfiguration;
                unset($additionalProperties['file']);

                $fileHelper->moveFileAndCreateReference(
                    $imageConfiguration['file'],
                    $this->targetFolder,
                    'tt_content',
                    'assets',
                    $this->propertyHelper->getPropertyFromRecord('uid'),
                    $additionalProperties
                );
            }
        }
    }
}
