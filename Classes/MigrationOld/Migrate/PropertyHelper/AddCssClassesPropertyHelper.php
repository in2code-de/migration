<?php
namespace In2code\Migration\MigrationOld\Migrate\PropertyHelper;

use In2code\Migration\Utility\DomDocumentUtility;

/**
 * Class AddCssClassesPropertyHelper
 * to add css classes to a HTML tag (only if it's not yet added)
 *
 *  Configuration example:
 *      'configuration' => [
 *          'tags' => [
 *              'ul'
 *          ],
 *          'addClass' => [
 *              'classname1'
 *              'classname2'
 *          ]
 *          'condition' => [
 *              'CType' => [
 *                  'textpic',
 *                  'text',
 *                  'textmedia'
 *              ]
 *          ]
 *      ]
 */
class AddCssClassesPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * @var string
     */
    protected $xmlDeclaration = '<?xml encoding="utf-8" ?>';

    /**
     * @return void
     * @throws \Exception
     */
    public function initialize()
    {
        if (!is_array($this->getConfigurationByKey('tags')) || !is_array($this->getConfigurationByKey('addClass'))
            || !is_array($this->getConfigurationByKey('condition'))) {
            throw new \Exception('configuration is missing', 1525342443);
        }
    }

    /**
     * @return void
     */
    public function manipulate()
    {
        $dom = new \DOMDocument();
        try {
            $dom->loadHTML(
                DomDocumentUtility::wrapHtmlWithMainTags($this->getProperty()),
                LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
            );
            foreach ($this->getConfigurationByKey('tags') as $tagName) {
                $tags = $dom->getElementsByTagName($tagName);
                /** @var \DOMElement $tag */
                foreach ($tags as $tag) {
                    $buildClassName = $tag->getAttribute('class');
                    $newClassNames = $this->getConfigurationByKey('addClass');
                    foreach ($newClassNames as $newClassName) {
                        if (stristr($buildClassName, $newClassName) === false) {
                            $buildClassName .= ' ' . $newClassName;
                        }
                    }
                    $tag->setAttribute('class', trim($buildClassName));
                }
            }
            $this->setProperty(DomDocumentUtility::stripMainTagsFromHtml($dom->saveHTML()));
        } catch (\Exception $exception) {
            $this->log->addError($exception->getMessage());
        }
    }

    /**
     * @return bool
     */
    public function shouldMigrate(): bool
    {
        if ($this->isFittingCondition()) {
            foreach ($this->getConfigurationByKey('tags') as $tag) {
                if (stristr($this->getProperty(), '<' . $tag)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @return bool
     */
    protected function isFittingCondition(): bool
    {
        $isFitting = true;
        foreach ($this->getConfigurationByKey('condition') as $field => $values) {
            if (!in_array($this->getPropertyFromRecord($field), $values)) {
                $isFitting = false;
                break;
            }
        }
        return $isFitting;
    }
}
