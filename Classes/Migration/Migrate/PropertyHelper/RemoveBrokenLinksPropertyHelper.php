<?php
namespace In2code\In2template\Migration\Migrate\PropertyHelper;

/**
 * Class RemoveBrokenLinksPropertyHelper
 */
class RemoveBrokenLinksPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * @return void
     * @throws \Exception
     */
    protected function manipulate()
    {
        if ($this->bodytextContainsBrokenLinks()) {
            $this->setProperty($this->getConvertedBodytext());
        }
    }

    /**
     * @return string
     */
    protected function getConvertedBodytext(): string
    {
        $bodytext = preg_replace_callback(
            '~<link\sLink:\s.*>(.*)</link>~U',
            [$this, 'replaceCallback'],
            $this->getProperty()
        );
        return $bodytext;
    }

    /**
     * @param array $match
     * @return string
     */
    protected function replaceCallback(array $match): string
    {
        return $match[1];
    }

    /**
     * @return bool
     */
    protected function bodytextContainsBrokenLinks(): bool
    {
        return stristr($this->getProperty(), '<link Link:') !== false;
    }
}
