<?php
namespace In2code\Migration\MigrationOld\Migrate\PropertyHelper;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ConvertBulletspointsToHtmlPropertyHelper
 */
class ConvertBulletspointsToHtmlPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * @return void
     */
    public function manipulate()
    {
        $class = '';
        if ($this->getConfigurationByKey('class') !== null) {
            $class = ' class="' . $this->getConfigurationByKey('class') . '"';
        }
        $lines = [
            '<ul' . $class . '>'
        ];
        foreach (GeneralUtility::trimExplode(PHP_EOL, $this->getProperty(), true) as $line) {
            $lines[] = '<li>' . $line . '</li>';
        }
        $lines[] = '</ul>';
        $this->setProperty(implode('', $lines));
    }

    /**
     * @return bool
     */
    public function shouldMigrate(): bool
    {
        return $this->getPropertyFromRecord('CType') === 'bullets' && $this->getProperty() !== '';
    }
}
