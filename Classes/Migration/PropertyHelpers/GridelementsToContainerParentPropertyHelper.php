<?php

declare(strict_types=1);
namespace In2code\Migration\Migration\PropertyHelpers;

use In2code\Migration\Exception\ConfigurationException;

/**
 * Class GridelementsToContainerParentPropertyHelper
 *
 * to migrate container elements (parents) from EXT:gridelements to EXT:container
 *  'configuration' => [
 *      'types' => [
 *          // tt_content.tx_gridelements_backend_layout => tt_content.CType
 *          'gridelements_backend_layout' => 'CType'
 *      ],
 *  ]
 */
class GridelementsToContainerParentPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{
    /**
     * @return void
     * @throws ConfigurationException
     */
    public function initialize(): void
    {
        if (is_array($this->getConfigurationByKey('types')) === false) {
            throw new ConfigurationException('"types" configuration is missing or invalid', 1662636947);
        }
    }

    public function manipulate(): void
    {
        $types = $this->getConfigurationByKey('types');
        $properties = [
            'CType' => $types[$this->getPropertyFromRecordOld('tx_gridelements_backend_layout')],
        ];
        $this->setProperties($properties);
    }

    public function shouldMigrate(): bool
    {
        return $this->getPropertyFromRecordOld('CType') === 'gridelements_pi1'
            && array_key_exists(
                $this->getPropertyFromRecordOld('tx_gridelements_backend_layout'),
                $this->getConfigurationByKey('types')
            );
    }
}
