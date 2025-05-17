<?php

declare(strict_types=1);
namespace In2code\Migration\Migration\PropertyHelpers;

use In2code\Migration\Utility\DomDocumentUtility;
use In2code\Migration\Utility\StringUtility;

/**
 * Class ReplaceCssClassesInHtmlStringPropertyHelper
 * to replace css classes in a HTML-string - e.g. RTE fields like tt_content.bodytext
 *
 *  Configuration example:
 *      'configuration' => [
 *          'condition' => [
 *              'CType' => [
 *                  'textpic',
 *                  'text',
 *                  'textmedia'
 *              ]
 *          ],
 *          'tags' => [
 *              'table'
 *          ],
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
    protected array $checkForConfiguration = [
        'condition',
        'tags',
        'search',
        'replace',
    ];

    public function manipulate(): void
    {
        $dom = new \DOMDocument();
        try {
            $previousErrorSetting = libxml_use_internal_errors(true);
            $dom->loadHTML(
                DomDocumentUtility::wrapHtmlWithMainTags($this->getProperty()),
                LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
            );
            $this->logErrors($previousErrorSetting);

            foreach ($this->getConfigurationByKey('tags') as $tagName) {
                $tags = $dom->getElementsByTagName($tagName);
                /** @var \DOMElement $tag */
                foreach ($tags as $tag) {
                    if ($tag->hasAttribute('class')) {
                        $newClasses = $existingClasses = $tag->getAttribute('class');
                        foreach ($this->getConfigurationByKey('search') as $key => $searchTerm) {
                            $newClasses = StringUtility::replaceCssClassInString(
                                $searchTerm,
                                $this->getConfigurationByKey('replace')[$key],
                                $newClasses
                            );
                        }
                        if ($newClasses !== $existingClasses) {
                            $tag->setAttribute('class', $newClasses);
                        }
                    }
                }
            }
            $this->setProperty(DomDocumentUtility::stripMainTagsFromHtml($dom->saveHTML()));
        } catch (\Throwable $exception) {
            $this->log->addError($exception->getMessage());
        }
    }

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

    protected function logErrors(bool $previousErrorSetting): void
    {
        $errors = libxml_get_errors();
        foreach ($errors as $error) {
            if ($error->level === LIBXML_ERR_FATAL) {
                $this->log->addWarning('XML Parse Error: ' . $error->message);
            }
        }
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrorSetting);
    }
}
