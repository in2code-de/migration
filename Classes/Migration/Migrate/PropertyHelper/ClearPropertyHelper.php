<?php
namespace In2code\In2template\Migration\Migrate\PropertyHelper;

/**
 * Class CleanOnConditionPropertyHelper
 */
class ClearPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * @return void
     * @throws \Exception
     */
    protected function manipulate()
    {
        $this->setProperty('');
    }
}
