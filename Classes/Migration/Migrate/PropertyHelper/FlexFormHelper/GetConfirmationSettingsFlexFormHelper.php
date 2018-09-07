<?php
namespace In2code\Migration\Migration\Migrate\PropertyHelper\FlexFormHelper;

use In2code\Migration\Utility\ObjectUtility;
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;

/**
 * Class GetConfirmationSettingsFlexFormHelper
 */
class GetConfirmationSettingsFlexFormHelper extends AbstractFlexFormHelper implements FlexFormHelperInterface
{

    /**
     * @return int
     */
    public function getVariable(): int
    {
        $confirmationPage = 0;
        if (!empty($this->getConfiguration()['confirmation'])) {
            $confirmationPage = 1;
        }
        return $confirmationPage;
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
        return (array)$configuration;
    }
}
