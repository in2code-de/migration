<?php
namespace In2code\Migration\Migration\Import\PropertyHelper;

use In2code\Migration\Utility\StringUtility;

/**
 * Class ReplaceCssClassesInHtmlStringPropertyHelper
 * to replace css classes in a HTML-string - e.g. RTE fields like tt_content.bodytext
 *
 *  Configuration example:
 *      'configuration' => [
 *          'search' => [
 *              'class1'
 *          ],
 *          'replace' => [
 *              'class2'
 *          ]
 *      ]
 */
class ReplaceCssClassesInHtmlStringPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * @return void
     * @throws \Exception
     */
    public function initialize()
    {
        if (!is_array($this->getConfigurationByKey('search')) || !is_array($this->getConfigurationByKey('replace'))) {
            throw new \Exception('configuration search and replace is missing');
        }
    }

    /**
     * @return void
     */
    public function manipulate()
    {
        $string = $this->getProperty();
        $replacements = $this->getConfigurationByKey('replace');
        foreach ($this->getConfigurationByKey('search') as $key => $searchterm) {
            $replace = $replacements[$key];
            $string = StringUtility::replaceCssClassInString($searchterm, $replace, $string);
        }
        $this->setProperty($string);
    }

    /**
     * @return bool
     */
    public function shouldImport(): bool
    {
        foreach ($this->getConfigurationByKey('search') as $searchterm) {
            if (stristr($this->getProperty(), $searchterm)) {
                return true;
            }
        }
        return false;
    }
}
