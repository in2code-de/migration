<?php
namespace In2code\In2template\Migration\Migrate\PropertyHelper;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class CreateRteListFromCtypeBulletsPropertyHelper
 */
class CreateRteListFromCtypeBulletsPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * @return void
     * @throws \Exception
     */
    protected function manipulate()
    {
        if ($this->isConditionMatching()) {
            $this->setProperty($this->createListFromString($this->getProperty()));
            $this->log->addError('CType bullets converted to RTE list');
        }
    }

    /**
     * @param string $string
     * @return string
     */
    protected function createListFromString(string $string): string
    {
        $lines = GeneralUtility::trimExplode(PHP_EOL, $string, true);
        $list = '';
        if (!empty($lines)) {
            $list .= '<ul>';
            foreach ($lines as $line) {
                $list .= '<li>' . $line . '</li>';
            }
            $list .= '</ul>';
        }
        return $list;
    }

    /**
     * @return bool
     */
    protected function isConditionMatching(): bool
    {
        foreach ($this->getConfigurationByKey('condition') as $field => $value) {
            if ($this->getPropertyFromRecord($field) !== $value) {
                return false;
            }
        }
        return true;
    }
}
