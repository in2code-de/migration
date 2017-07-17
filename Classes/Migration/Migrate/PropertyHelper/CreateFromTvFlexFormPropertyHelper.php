<?php
namespace In2code\In2template\Migration\Migrate\PropertyHelper;

use In2code\In2template\Migration\Migrate\PropertyHelper\FceHelper\AbstractFceHelper;
use In2code\In2template\Migration\Migrate\PropertyHelper\FceHelper\FceHelperInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\FlexFormService;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Class CreateFromTvFlexFormPropertyHelper allows to create bodytext from a FCE
 *
 * Example integration:
 *      $propertyHelpers = [
 *          'bodytext' => [
 *              [
 *                  'className' => CreateFromTvFlexFormPropertyHelper::class,
 *                  'configuration' => [
 *                      'converters' => [
 *                          [
 *                              'condition' => [
 *                                  'CType' => 'templavoila_pi1',
 *                                  'tx_templavoila_ds' => '22'
 *                              ],
 *                              'flexFormField' => 'tx_templavoila_flex',
 *                              'template' => 'EXT:in2template/Resources/Private/Migration/Fce/22.html',
 *                              'properties' => [
 *                                  'header' => 'overwrite header with this text'
 *                              ],
 *                              'fceHelpers' => []
 *                          ]
 *                      ]
 *                  ]
 *              ]
 *          ]
 *      ];
 *
 * In the template files variables can be used:
 *      {flexForm}      Contains FlexForm array from field "pi_flexform" (if given)
 *      {row}           Contains Table row
 */
class CreateFromTvFlexFormPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * @var string
     */
    protected $fceHelperInterface = FceHelperInterface::class;

    /**
     * @return void
     * @throws \Exception
     */
    protected function manipulate()
    {
        foreach ($this->getConfigurationByKey('converters') as $converterConfig) {
            if ($this->isConditionMatching($converterConfig['condition'])) {
                $this->setNewProperties($converterConfig);
                $this->setProperty($this->buildHtml($converterConfig));
                $this->callFceHelpers($converterConfig);
                $this->log->addMessage('Bodytext generated from FCE with ' . $converterConfig['template']);
            }
        }
    }

    /**
     * @param array $converterConfig
     * @return void
     */
    protected function setNewProperties(array $converterConfig)
    {
        if (!empty($converterConfig['properties'])) {
            $this->setProperties($converterConfig['properties']);
        }
    }

    /**
     * @param array $converterConfig
     * @return void
     * @throws \Exception
     */
    protected function callFceHelpers(array $converterConfig)
    {
        if (!empty($converterConfig['fceHelpers'])) {
            foreach ($converterConfig['fceHelpers'] as $fceHelperConfig) {
                if (!class_exists($fceHelperConfig['className'])) {
                    throw new \Exception('Class ' . $fceHelperConfig['className'] . ' does not exists');
                }
                if (is_subclass_of($fceHelperConfig['className'], $this->fceHelperInterface)) {
                    /** @var AbstractFceHelper $helperClass */
                    $fceHelperClass = GeneralUtility::makeInstance(
                        $fceHelperConfig['className'],
                        $this,
                        (array)$fceHelperConfig['configuration']
                    );
                    $fceHelperClass->initialize();
                    $fceHelperClass->start();
                } else {
                    throw new \Exception('Class does not implement ' . $this->fceHelperInterface);
                }
            }
        }
    }

    /**
     * @param array $converterConfig
     * @return string
     */
    protected function buildHtml(array $converterConfig): string
    {
        $standaloneView = $this->getObjectManager()->get(StandaloneView::class);
        $standaloneView->setTemplateSource($this->getTemplateContent($converterConfig));
        $standaloneView->assignMultiple(
            [
                'row' => $this->record,
                'flexForm' => $this->getFlexFormArray($converterConfig)
            ]
        );
        $html = $standaloneView->render();
        if ($html === null) {
            $html = '';
        }
        return $this->removeLastEol($html);
    }

    /**
     * @param array $converterConfig
     * @return string
     */
    protected function getTemplateContent(array $converterConfig): string
    {
        $absolute = GeneralUtility::getFileAbsFileName($converterConfig['template']);
        return (string)file_get_contents($absolute);
    }

    /**
     * @param array $converterConfig
     * @return array
     */
    protected function getFlexFormArray(array $converterConfig): array
    {
        $flexFormService = $this->getObjectManager()->get(FlexFormService::class);
        return (array)$flexFormService->convertFlexFormContentToArray(
            $this->getPropertyFromRecord(
                $converterConfig['flexFormField']
            )
        );
    }

    /**
     * @param array $conditions
     * @return bool
     */
    protected function isConditionMatching(array $conditions): bool
    {
        foreach ($conditions as $field => $value) {
            if ($this->getPropertyFromRecord($field) !== $value) {
                return false;
            }
        }
        return true;
    }

    /**
     * Remove last sign if it's PHP_EOL
     * because HTML-Templates are normally stored with an empty line at the end.
     * A general trim (e.g. a viewhelper) is not option because we want to keep the original blank lines if there are
     * some before in the database.
     *
     * @param $string
     * @return string
     */
    protected function removeLastEol($string): string
    {
        if (substr($string, -1) === PHP_EOL) {
            $string = substr($string, 0, -1);
        }
        return $string;
    }
}
