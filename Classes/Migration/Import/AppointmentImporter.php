<?php
namespace In2code\In2template\Migration\Import;

use In2code\In2template\Migration\Import\PropertyHelper\CreateAppointmentImageRelationsPropertyHelper;
use In2code\In2template\Migration\Import\PropertyHelper\CreateDateCyclesPropertyHelper;
use In2code\In2template\Migration\Import\PropertyHelper\CreateEmptyRequestRecordPropertyHelper;
use In2code\In2template\Migration\Import\PropertyHelper\GetAppointmentCategoryFromOldCategoryPropertyHelper;
use In2code\In2template\Migration\Import\PropertyHelper\SetAppointmentVisibilityPropertyHelper;

/**
 * Class AppointmentImporter
 */
class AppointmentImporter extends AbstractImporter implements ImporterInterface
{

    /**
     * Table name where to migrate to
     *
     * @var string
     */
    protected $tableName = 'tx_hskacalendar_domain_model_appointment';

    /**
     * Table name from migrate to
     *
     * @var string
     */
    protected $tableNameOld = 'tx_pthskaadmin_domain_model_appointment';

    /**
     * @var string
     */
    protected $additionalWhere
        = ' and pid not in (5421,5427) and type = 0 and date != "" and title != "" and hidden = 0';

    /**
     * @var array
     */
    protected $values = [
        'request' => 1,
        'visibility_facility' => 1,
        'visibility_academy' => 1,
        'type' => 'In2code\HskaCalendar\Domain\Model\AppointmentTypes\FacilityAppointment'
    ];

    /**
     * @var array
     */
    protected $mapping = [
        'title' => 'title',
        'description' => 'description',
        'location' => 'location',
        'facility' => 'facility',
        'max_number_of_participants' => 'max_participants',
        'cruser_id' => 'be_user',
        'cr_feuser' => 'fe_user',
        'notes' => 'notes',
        'link' => 'link',
        'cancelled' => 'cancelled',
        'cancelled_reason' => 'cancelled_reason',
        'appointment_category' => 'category'
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
        '_visibilitySettings' => [
            [
                'className' => SetAppointmentVisibilityPropertyHelper::class
            ]
        ],
        'category' => [
            [
                'className' => GetAppointmentCategoryFromOldCategoryPropertyHelper::class
            ]
        ],
        'request' => [
            [
                'className' => CreateEmptyRequestRecordPropertyHelper::class
            ]
        ],
        'date_cycles' => [
            [
                'className' => CreateDateCyclesPropertyHelper::class
            ]
        ],
        'image' => [
            [
                'className' => CreateAppointmentImageRelationsPropertyHelper::class
            ]
        ]
    ];
}
