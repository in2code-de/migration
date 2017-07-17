<?php
namespace In2code\In2template\Migration\Migrate\PropertyHelper\FlexFormHelper;

/**
 * Interface FlexFormHelperInterface
 */
interface FlexFormHelperInterface
{
    /**
     * @return void
     */
    public function initialize();

    /**
     * @return string
     */
    public function getVariable(): string;
}
