<?php
namespace In2code\Migration\Utility;

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class IconUtility
 */
class IconUtility
{

    /**
     * @param array $icons ['name' => 'path']
     */
    public static function registerSvgIcons(array $icons)
    {
        $iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);
        foreach ($icons as $key => $icon) {
            $iconRegistry->registerIcon($key, SvgIconProvider::class, ['source' => $icon]);
        }
    }
}
