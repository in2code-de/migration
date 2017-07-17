<?php
namespace In2code\In2template\Migration\Migrate\PropertyHelper;

use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Class ReplaceOnCondition
 */
class ReplaceOnConditionPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * @return void
     * @throws \Exception
     */
    public function initialize()
    {
        if (!is_array($this->getConfigurationByKey('conditions'))) {
            throw new \Exception('Configuration is missing for class ' . __CLASS__);
        }
    }

    /**
     * @return void
     * @throws \Exception
     */
    protected function manipulate()
    {
        if ($this->isFittingValue()) {
            $newValue = $this->getNewValue();
            $this->log->addMessage($this->propertyName . ' changed from ' . $this->getProperty() . ' to ' . $newValue);
            $this->setProperty($newValue);
        }
    }

    /**
     * @return bool
     * @throws \Exception
     */
    protected function isFittingValue(): bool
    {
        $isFitting = true;
        foreach ($this->getConfigurationByKey('conditions') as $field => $values) {
            if (!is_string($field) || !is_array($values)) {
                throw new \Exception('Possible misconfiguration of configuration of ' . __CLASS__);
            }
            if (!in_array($this->getPropertyFromRecord($field), $values)) {
                $isFitting = false;
                break;
            }
        }
        return $isFitting;
    }

    /**
     * @return string
     */
    protected function getNewValue(): string
    {
        $newValue = (string)$this->getConfigurationByKey('replace.value');
        $newValue = $this->parseString($newValue);
        return $newValue;
    }

    /**
     * Make variables available in replace string like {title} or {uid}
     *
     * @param string $newValue
     * @return string
     */
    protected function parseString(string $newValue): string
    {
        if (!empty($newValue) && stristr($newValue, '{')) {
            $standaloneView = $this->getObjectManager()->get(StandaloneView::class);
            $standaloneView->setTemplateSource($newValue);
            $standaloneView->assignMultiple($this->record);
            return $standaloneView->render();
        }
        return $newValue;
    }
}
