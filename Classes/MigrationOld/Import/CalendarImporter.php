<?php
namespace In2code\Migration\MigrationOld\Import;

use In2code\Migration\MigrationOld\Import\PropertyHelper\GetTimestampEndDatePropertyHelper;
use In2code\Migration\MigrationOld\Import\PropertyHelper\GetTimestampStartDatePropertyHelper;

/**
 * Class CalendarImporter
 */
class CalendarImporter extends AbstractImporter implements ImporterInterface
{

    /**
     * Because this importer runs after the default news importer - so the tables is already filled with stuff
     *
     * @var bool
     */
    protected $truncate = false;

    /**
     * Because this importer runs after the default news importer - so the tables is already filled with stuff
     *
     * @var bool
     */
    protected $keepIdentifiers = false;

    /**
     * Table to import
     *
     * @var string
     */
    protected $tableName = 'tx_news_domain_model_news';

    /**
     * Table to import
     *
     * @var string
     */
    protected $tableNameOld = 'tx_cal_event';

    /**
     * @var array
     */
    protected $mapping = [
        'title' => 'title',
        'organizer' => 'organizer_simple',
        'location' => 'location_simple',
        'location_link' => 'location_link',
        'allday' => 'full_day',
        'description' => 'bodytext',
        'tx_tuekalendererweiterung_terminreihe' => 'eventline',
        'tx_tuekalendererweiterung_terminsparte' => 'speaker',
        'tx_tuekalendererweiterung_terminlink' => 'event_link',
        'category_id' => 'categories'
    ];

    /**
     * @var array
     */
    protected $values = [
        'is_event' => 1
    ];

    /**
     * PropertyHelpers are called after initial build via mapping
     *
     *      "newProperty" => [
     *          [
     *              "className" => class1::class,
     *              "configuration => ["red"]
     *          ],
     *          [
     *              "className" => class2::class
     *          ]
     *      ]
     *
     * @var array
     */
    protected $propertyHelpers = [
        'datetime' => [
            [
                'className' => GetTimestampStartDatePropertyHelper::class,
                'configuration' => [
                    'fields' => [
                        'date' => 'start_date',
                        'time' => 'start_time'
                    ]
                ]
            ]
        ],
        'event_end' => [
            [
                'className' => GetTimestampEndDatePropertyHelper::class,
                'configuration' => [
                    'fields' => [
                        'date' => 'end_date',
                    ]
                ]
            ]
        ]
    ];

    protected function initialize()
    {
        $this->removeNullValuesFromTerminreihe();
        $this->removeNullValuesFromTerminsparte();
        $this->removeNullValuesFromTerminlink();
    }

    /**
     * Prevent sql error "value for targetfield cannot be null" - the starting field has empty values and null-values,
     * we simply convert them to empty values before importing
     *
     * @return void
     */
    protected function removeNullValuesFromTerminreihe()
    {
        $this->getDatabase()->exec_UPDATEquery(
            'tx_cal_event',
            'tx_tuekalendererweiterung_terminreihe is null',
            ['tx_tuekalendererweiterung_terminreihe' => '']
        );
    }

    /**
     * Prevent sql error "value for targetfield cannot be null" - the starting field has empty values and null-values,
     * we simply convert them to empty values before importing
     *
     * @return void
     */
    protected function removeNullValuesFromTerminsparte()
    {
        $this->getDatabase()->exec_UPDATEquery(
            'tx_cal_event',
            'tx_tuekalendererweiterung_terminsparte is null',
            ['tx_tuekalendererweiterung_terminsparte' => '']
        );
    }

    /**
     * Prevent sql error "value for targetfield cannot be null" - the starting field has empty values and null-values,
     * we simply convert them to empty values before importing
     *
     * @return void
     */
    protected function removeNullValuesFromTerminlink()
    {
        $this->getDatabase()->exec_UPDATEquery(
            'tx_cal_event',
            'tx_tuekalendererweiterung_terminlink is null',
            ['tx_tuekalendererweiterung_terminlink' => '']
        );
    }
}
