<?php
namespace In2code\In2template\Migration\Migrate\PropertyHelper;

/**
 * Class RandomValuePropertyHelper
 */
class RandomValuePropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * @return void
     */
    protected function manipulate()
    {
        if ($this->conditionFits()) {
            $this->setProperty($this->getRandomString(16, false));
            $this->log->addMessage('Random password set for user ' . $this->getPropertyFromRecord('username'));
        }
    }

    /**
     * @return bool
     */
    protected function conditionFits(): bool
    {
        $fits = true;
        foreach ($this->getConfigurationByKey('condition') as $fieldName => $fieldValue) {
            if ($this->getPropertyFromRecord($fieldName) !== $fieldValue) {
                $fits = false;
                break;
            }
        }
        return $fits;
    }

    /**
     * create a random string
     *
     * @param int $length
     * @param bool $lowerAndUpperCase
     * @return string
     */
    protected function getRandomString(int $length = 32, bool $lowerAndUpperCase = true): string
    {
        $characters = implode('', range(0, 9)) . implode('', range('a', 'z'));
        if ($lowerAndUpperCase) {
            $characters .= implode('', range('A', 'Z'));
        }
        $fileName = '';
        for ($i = 0; $i < $length; $i++) {
            $key = mt_rand(0, strlen($characters) - 1);
            $fileName .= $characters[$key];
        }
        return $fileName;
    }
}
