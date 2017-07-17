<?php
namespace In2code\In2template\Migration\Migrate\PropertyHelper;

/**
 * Class ConvertOutdatedFileadminLinksPropertyHelper
 */
class ConvertOutdatedFileadminLinksPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * @return void
     * @throws \Exception
     */
    protected function manipulate()
    {
        if ($this->bodytextContainsOutdatedLink()) {
            $this->setProperty($this->getConvertedBodytext());
        }
    }

    /**
     * Replace "<link fileadmin/file.pdf ...>" with "<link file:123 ...>"
     *      Basic function:
     *      preg_match_all("/<link\sfileadmin(\S*)(\s.*)?>(.*)<\/link>/U", $input_lines, $output_array);
     *
     * @return string
     */
    protected function getConvertedBodytext(): string
    {
        $bodytext = preg_replace_callback(
            '~<link\sfileadmin(\S*)(\s.*)?>(.*)</link>~U',
            [$this, 'replaceCallback'],
            $this->getProperty()
        );
        return $bodytext;
    }

    /**
     * Replace old link string with new format. If no sys_file identifier could be found, remove link from string
     *
     * @param array $match
     * @return string
     */
    protected function replaceCallback(array $match): string
    {
        $identifier = $this->replaceFileStringWithIdentifier($match[1]);
        $string = $match[3];
        if ($identifier > 0) {
            $string = '<link file:' . $identifier . '>' . $match[3] . '</link>';
        }
        return $string;
    }

    /**
     * Replace "/file.pdf" with its identifier from sys_file
     *
     * @param string $pathAndFilename without fileadmin prefix
     * @return int
     */
    protected function replaceFileStringWithIdentifier(string $pathAndFilename): int
    {
        $row = (array)$this->getDatabase()->exec_SELECTgetSingleRow(
            'uid',
            'sys_file',
            'identifier="' . $pathAndFilename . '"'
        );
        return (int)$row['uid'];
    }

    /**
     * @return bool
     */
    protected function bodytextContainsOutdatedLink(): bool
    {
        return stristr($this->getProperty(), '<link fileadmin') !== false;
    }
}
