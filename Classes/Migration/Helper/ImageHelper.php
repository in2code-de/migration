<?php
namespace In2code\In2template\Migration\Helper;

/**
 * Class ImageHelper
 */
class ImageHelper
{

    /**
     * @var string
     */
    protected $imagePath = 'uploads/tx_templavoila/';

    /**
     * Example:
     *  [
     *      'field_image_left',
     *      'field_image_left_caption'
     *      'field_image_left_link'
     *  ]
     *
     * @var array
     */
    protected $tvMapping = [];

    /**
     * [
     *      file => 'fileadmin/image.jpg',
     *      description => 'Caption',
     *      link => '',
     *      alternative => ''
     * ],
     * [
     *      file => 'fileadmin/image2.jpg',
     *      description => '',
     *      link => '',
     *      alternative => ''
     * ]
     *
     * @param array $flexForm
     * @param string $ffKey1
     * @param string $ffKey2
     * @param string $position "all", "left", "right"
     * @param array $tvMapping will only be respected if tvMapping is set, otherwise ignore
     * @return array
     */
    public function getImages(
        array $flexForm,
        string $ffKey1,
        string $ffKey2,
        string $position = 'all',
        array $tvMapping = []
    ) {
        $this->tvMapping = $tvMapping;
        $images = [];
        if (!empty($flexForm[$ffKey1])) {
            foreach ((array)$flexForm[$ffKey1] as $imageConfiguration) {
                if (!empty($imageConfiguration[$ffKey2])) {
                    $images = $this->addSimpleImage($images, $imageConfiguration[$ffKey2], $position);
                    $images = $this->addTopImage($images, $imageConfiguration[$ffKey2], $position);
                    $images = $this->addLeftImage($images, $imageConfiguration[$ffKey2], $position);
                    $images = $this->addCenterImage($images, $imageConfiguration[$ffKey2], $position);
                    $images = $this->addRightImage($images, $imageConfiguration[$ffKey2], $position);
                }
            }
        }
        return $images;
    }

    /**
     * Add images from key "field_image"
     *
     * @param array $images
     * @param array $imageArray
     * @param string $position
     * @return array
     */
    protected function addSimpleImage(array $images, array $imageArray, string $position): array
    {
        if ($position === 'all') {
            if ($this->ifFieldIsSetAndMappingEnabled($imageArray, 'field_image')) {
                $image = ['file' => $this->getRelativeFilePathFromFileName($imageArray['field_image'])];
                if ($this->ifFieldIsSetAndMappingEnabled($imageArray, 'field_image_description')) {
                    $image['description'] = (string)$imageArray['field_image_description'];
                }
                if ($this->ifFieldIsSetAndMappingEnabled($imageArray, 'field_image_caption')) {
                    $image['description'] = (string)$imageArray['field_image_caption'];
                }
                if ($this->ifFieldIsSetAndMappingEnabled($imageArray, 'field_image_link')) {
                    $image['link'] = (string)$imageArray['field_image_link'];
                }
                if ($this->ifFieldIsSetAndMappingEnabled($imageArray, 'field_image_alt_text')) {
                    $image['alternative'] = (string)$imageArray['field_image_alt_text'];
                }
                $images[] = $image;
            }
        }
        return $images;
    }

    /**
     * Add images from key "field_image_top"
     *
     * @param array $images
     * @param array $imageArray
     * @param string $position
     * @return array
     */
    protected function addTopImage(array $images, array $imageArray, string $position): array
    {
        if ($position === 'all') {
            if ($this->ifFieldIsSetAndMappingEnabled($imageArray, 'field_image_top')) {
                $image = ['file' => $this->getRelativeFilePathFromFileName($imageArray['field_image_top'])];
                if ($this->ifFieldIsSetAndMappingEnabled($imageArray, 'field_image_description')) {
                    $image['description'] = (string)$imageArray['field_image_description'];
                }
                if ($this->ifFieldIsSetAndMappingEnabled($imageArray, 'field_image_caption')) {
                    $image['description'] = (string)$imageArray['field_image_caption'];
                }
                if ($this->ifFieldIsSetAndMappingEnabled($imageArray, 'field_image_link')) {
                    $image['link'] = (string)$imageArray['field_image_link'];
                }
                if ($this->ifFieldIsSetAndMappingEnabled($imageArray, 'field_image_alt_text')) {
                    $image['alternative'] = (string)$imageArray['field_image_alt_text'];
                }
                $images[] = $image;
            }
        }
        return $images;
    }

    /**
     * Add images from key "field_image_left"
     *
     * @param array $images
     * @param array $imageArray
     * @param string $position
     * @return array
     */
    protected function addLeftImage(array $images, array $imageArray, string $position): array
    {
        if ($position === 'all' || $position === 'left') {
            if ($this->ifFieldIsSetAndMappingEnabled($imageArray, 'field_image_left')) {
                $image = ['file' => $this->getRelativeFilePathFromFileName($imageArray['field_image_left'])];
                if ($this->ifFieldIsSetAndMappingEnabled($imageArray, 'field_image_left_description')) {
                    $image['description'] = (string)$imageArray['field_image_left_description'];
                }
                if ($this->ifFieldIsSetAndMappingEnabled($imageArray, 'field_image_left_caption')) {
                    $image['description'] = (string)$imageArray['field_image_left_caption'];
                }
                if ($this->ifFieldIsSetAndMappingEnabled($imageArray, 'field_image_left_link')) {
                    $image['link'] = (string)$imageArray['field_image_left_link'];
                }
                if ($this->ifFieldIsSetAndMappingEnabled($imageArray, 'field_image_left_alt_text')) {
                    $image['alternative'] = (string)$imageArray['field_image_left_alt_text'];
                }
                if ($this->ifFieldIsSetAndMappingEnabled($imageArray, 'field_image_alt_text')) {
                    $image['alternative'] = (string)$imageArray['field_image_alt_text'];
                }
                $images[] = $image;
            }
        }
        return $images;
    }

    /**
     * Add images from key "field_image_center"
     *
     * @param array $images
     * @param array $imageArray
     * @param string $position
     * @return array
     */
    protected function addCenterImage(array $images, array $imageArray, string $position): array
    {
        if ($position === 'all') {
            if ($this->ifFieldIsSetAndMappingEnabled($imageArray, 'field_image_center')) {
                $image = ['file' => $this->getRelativeFilePathFromFileName($imageArray['field_image_center'])];
                if ($this->ifFieldIsSetAndMappingEnabled($imageArray, 'field_image_center_description')) {
                    $image['description'] = (string)$imageArray['field_image_center_description'];
                }
                if ($this->ifFieldIsSetAndMappingEnabled($imageArray, 'field_image_center_caption')) {
                    $image['description'] = (string)$imageArray['field_image_center_caption'];
                }
                if ($this->ifFieldIsSetAndMappingEnabled($imageArray, 'field_image_center_link')) {
                    $image['link'] = (string)$imageArray['field_image_center_link'];
                }
                if ($this->ifFieldIsSetAndMappingEnabled($imageArray, 'field_image_center_alt_text')) {
                    $image['alternative'] = (string)$imageArray['field_image_center_alt_text'];
                }
                $images[] = $image;
            }
        }
        return $images;
    }

    /**
     * Add images from key "field_image_right"
     *
     * @param array $images
     * @param array $imageArray
     * @param string $position
     * @return array
     */
    protected function addRightImage(array $images, array $imageArray, string $position): array
    {
        if ($position === 'all' || $position === 'right') {
            if ($this->ifFieldIsSetAndMappingEnabled($imageArray, 'field_image_right')) {
                $image = ['file' => $this->getRelativeFilePathFromFileName($imageArray['field_image_right'])];
                if ($this->ifFieldIsSetAndMappingEnabled($imageArray, 'field_image_right_description')) {
                    $image['description'] = (string)$imageArray['field_image_right_description'];
                }
                if ($this->ifFieldIsSetAndMappingEnabled($imageArray, 'field_image_right_caption')) {
                    $image['description'] = (string)$imageArray['field_image_right_caption'];
                }
                if ($this->ifFieldIsSetAndMappingEnabled($imageArray, 'field_image_right_link')) {
                    $image['link'] = (string)$imageArray['field_image_right_link'];
                }
                if ($this->ifFieldIsSetAndMappingEnabled($imageArray, 'field_image_right_alt_text')) {
                    $image['alternative'] = (string)$imageArray['field_image_right_alt_text'];
                }
                $images[] = $image;
            }
        }
        return $images;
    }

    /**
     * @param array $imageArray
     * @param string $fieldName
     * @return bool
     */
    protected function ifFieldIsSetAndMappingEnabled(array $imageArray, string $fieldName): bool
    {
        $enabled = true;
        if (empty($imageArray[$fieldName])) {
            $enabled = false;
        }
        if (!empty($this->tvMapping) && !in_array($fieldName, $this->tvMapping)) {
            $enabled = false;
        }
        return $enabled;
    }

    /**
     * @param string $filename
     * @return string
     */
    protected function getRelativeFilePathFromFileName(string $filename): string
    {
        return $this->imagePath . $filename;
    }
}
