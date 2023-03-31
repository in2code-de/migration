<?php
declare(strict_types=1);
namespace In2code\Migration\Migration\PropertyHelpers;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class RemoveEmptyLinesPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{
    public function manipulate(): void
    {
        $text = $this->getProperty();
        $text = $this->removeNonBreakingSpaces($text);
        $text = $this->removeEmptyLines($text);
        $text = $this->removeEmptyParagraphTags($text);
        $this->setProperty($text);
    }

    protected function removeNonBreakingSpaces(string $text): string
    {
        return preg_replace('/(&nbsp;)+/', ' ', $text);
    }

    protected function removeEmptyLines(string $text): string
    {
        $textlines = GeneralUtility::trimExplode(PHP_EOL, $text, true);
        return implode(PHP_EOL, $textlines);
    }

    protected function removeEmptyParagraphTags(string $text): string
    {
        return str_replace(['<p></p>', '<p> </p>'], '', $text);
    }
}
