<?php
namespace In2code\In2template\Migration\Import\PropertyHelper;

use KoninklijkeCollective\MyRedirects\Service\RedirectService;

/**
 * Class GenerateUrlHashPropertyHelper
 */
class GenerateUrlHashPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * @return void
     * @throws \Exception
     */
    public function initialize()
    {
        if (!class_exists(RedirectService::class)) {
            throw new \Exception('Class ' . RedirectService::class . ' is not available in system');
        }
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function manipulate()
    {
        $url = $this->getPropertyFromOldRecord('suburl');
        $redirectService = $this->getObjectManager()->get(RedirectService::class);
        $hash = $redirectService->generateUrlHash($url);
        $this->setProperty($hash);
    }
}
