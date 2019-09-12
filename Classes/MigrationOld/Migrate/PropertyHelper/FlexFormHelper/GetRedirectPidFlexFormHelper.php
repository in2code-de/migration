<?php
namespace In2code\Migration\MigrationOld\Migrate\PropertyHelper\FlexFormHelper;

use In2code\Migration\Utility\ObjectUtility;
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Class GetRedirectPidFlexFormHelper
 */
class GetRedirectPidFlexFormHelper extends AbstractFlexFormHelper implements FlexFormHelperInterface
{

    /**
     * @return string
     */
    public function getVariable(): string
    {
        $redirectPid = '';
        foreach ($this->getFieldKeysFromConfiguration($this->getConfiguration()) as $key) {
            if ($this->getConfiguration()[$key] === 'redirect') {
                $configuration = $this->getConfiguration()[$key . '.'];
                if (!empty($configuration['destination'])) {
                    $redirectPid = $configuration['destination'];
                }
            }
        }
        return $redirectPid;
    }

    /**
     * @return array
     */
    protected function getConfiguration(): array
    {
        /** @var TypoScriptParser $typoScriptParser */
        $typoScriptParser = ObjectUtility::getObjectManager()->get(TypoScriptParser::class);
        $typoScriptParser->parse($this->propertyHelper->getPropertyFromRecord('bodytext'));
        $configuration = $typoScriptParser->setup;
        return (array)$configuration['postProcessor.'];
    }

    /**
     * Get all relevant keys for field configuration
     *
     * @param array $configuration
     * @return int[]
     */
    protected function getFieldKeysFromConfiguration(array $configuration): array
    {
        $fieldKeys = [];
        foreach (array_keys($configuration) as $key) {
            if (MathUtility::canBeInterpretedAsInteger($key)) {
                $fieldKeys[] = (int)$key;
            }
        }
        return $fieldKeys;
    }
}
