<?php
namespace In2code\In2template\Migration\Migrate\PropertyHelper;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class CreateRteTableFromCtypeTablePropertyHelper
 */
class CreateRteTableFromCtypeTablePropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * @return void
     * @throws \Exception
     */
    protected function manipulate()
    {
        if ($this->isConditionMatching()) {
            $this->setProperty($this->getHtmlTable());
            $this->log->addMessage('CType table converted to RTE table');
        }
    }

    /**
     * @return string
     */
    protected function getHtmlTable(): string
    {
        $tableArray = $this->getTableArray();
        $html = '';
        if (!empty($tableArray)) {
            $html .= '<table' . $this->getClassStringForTable() . '>';
            foreach ($tableArray as $lines) {
                $html .= '<tr>';
                foreach ($lines as $cell) {
                    $html .= '<td>';
                    $html .= $cell;
                    $html .= '</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</table>';
        }
        return $html;
    }

    /**
     * @return string
     */
    protected function getClassStringForTable(): string
    {
        $classString = '';
        if ($this->getConfigurationByKey('classes.table')) {
            $classString = ' class="' . $this->getConfigurationByKey('classes.table') . '"';
        }
        return $classString;
    }

    /**
     * Build array from bodytext string of CType table
     *  before:
     *      "cell1|cell2
     *      cell3|cell4"
     *
     *  after:
     *      [["cell1", "cell2"], ["cell3", "cell4"]]
     *
     * @return array
     */
    protected function getTableArray(): array
    {
        $tableArray = [];
        $lines = GeneralUtility::trimExplode(PHP_EOL, $this->getProperty(), true);
        foreach ($lines as $line) {
            $tableArray[] = GeneralUtility::trimExplode('|', $line, true);
        }
        return $tableArray;
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
