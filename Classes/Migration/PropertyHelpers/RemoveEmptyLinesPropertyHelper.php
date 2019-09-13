<?php
declare(strict_types=1);
namespace In2code\Migration\Migration\PropertyHelpers;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class RemoveEmptyLinesPropertyHelper
 */
class RemoveEmptyLinesPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * @return void
     */
    public function manipulate(): void
    {
        $text = $this->getProperty();
        $text = $this->removeNonBreakingSpaces($text);
        $text = $this->removeEmptyLines($text);
        $text = $this->removeEmptyParagraphTags($text);
        $this->setProperty($text);
    }

    /**
     * @param string $text
     * @return string
     */
    protected function removeNonBreakingSpaces(string $text): string
    {
        return preg_replace('/(&nbsp;)+/', ' ', $text);
    }

    /**
     * @param string $text
     * @return string
     */
    protected function removeEmptyLines(string $text): string
    {
        $textlines = GeneralUtility::trimExplode(PHP_EOL, $text, true);
        return implode(PHP_EOL, $textlines);
    }

    /**
     * @param string $text
     * @return string
     */
    protected function removeEmptyParagraphTags(string $text): string
    {
        return str_replace(['<p></p>', '<p> </p>'], '', $text);
    }
}
