<?php

namespace app\core;

/**
 * Class CoreConstants
 *
 * Provides standardized constants for application core functionality,
 * including status codes, scenarios, validation patterns, and synchronization flags.
 *
 * These constants help maintain consistency across models, controllers, and services.
 *
 * @package app\core
 * @version 1.0.0
 * @since 2025-11-04
 */
class CoreConstants
{
    // -------------------------------------------------------------------------
    // Status Codes
    // -------------------------------------------------------------------------
    const STATUS_INACTIVE      = 0;
    const STATUS_ACTIVE        = 1;
    const STATUS_DRAFT         = 2;
    const STATUS_COMPLETED     = 3;
    const STATUS_DELETED       = 4;
    const STATUS_MAINTENANCE   = 5;
    const STATUS_APPROVED      = 6;
    const STATUS_REJECTED      = 7;

    // -------------------------------------------------------------------------
    // Scenario Definitions
    // -------------------------------------------------------------------------
    const SCENARIO_DEFAULT      = 'default';
    const SCENARIO_CREATE       = 'create';
    const SCENARIO_UPDATE       = 'update';
    const SCENARIO_DELETE       = 'delete';
    const SCENARIO_DRAFT        = 'draft';
    const SCENARIO_VIEW         = 'view';
    const SCENARIO_COMPLETED    = 'completed';
    const SCENARIO_RECEIVE      = 'receive';
    const SCENARIO_RECEIVE_ITEM = 'receiveItem';
    const SCENARIO_REJECT       = 'reject';
    const SCENARIO_REJECT_ITEM  = 'rejectItem';
    const SCENARIO_APPROVE      = 'approve';
    const SCENARIO_DETAIL       = 'detail';

    // -------------------------------------------------------------------------
    // Synchronization / Locking Constants
    // -------------------------------------------------------------------------
    const OPTIMISTIC_LOCK = 'lock_version';
    const SYNC_MONGODB    = 'sync_mdb';
    const SYNC_MASTER     = 'sync_master';
    const SYNC_SLAVE      = 'sync_slave';
    const SLAVE_ID        = 'slave_id';
    const MASTER_ID       = 'master_id';

    // -------------------------------------------------------------------------
    // Validation Patterns
    // -------------------------------------------------------------------------
    const DECIMAL_PATTERN = '/^\d+(\.\d{1,2})?$/';

    // -------------------------------------------------------------------------
    // Filtered Statuses
    // -------------------------------------------------------------------------
    const STATUS_NOT_DELETED = [
        '<>', 'status', self::STATUS_DELETED
    ];

    // -------------------------------------------------------------------------
    // Status Labels
    // -------------------------------------------------------------------------
    const STATUS_LIST = [
        self::STATUS_INACTIVE    => 'Inactive',
        self::STATUS_ACTIVE      => 'Active',
        self::STATUS_DRAFT       => 'Draft',
        self::STATUS_COMPLETED   => 'Completed',
        self::STATUS_DELETED     => 'Deleted',
        self::STATUS_MAINTENANCE => 'Maintenance',
        self::STATUS_APPROVED    => 'Approved',
        self::STATUS_REJECTED    => 'Rejected',
    ];

    // Status list specifically for purchase processes
    const PURCHASE_STATUS_LIST = self::STATUS_LIST;

    // Statuses restricted for certain operations
    const RESTRICT_STATUS_LIST = [
        self::STATUS_DELETED,
        self::STATUS_COMPLETED,
    ];

    // Scenarios that allow updates
    const SCENARIO_UPDATE_LIST = [
        self::SCENARIO_UPDATE,
        self::SCENARIO_DELETE,
    ];

    // Allowed status transitions for update operations
    const ALLOWED_UPDATE_STATUS_LIST = [
        self::STATUS_DRAFT => [
            self::STATUS_INACTIVE,
            self::STATUS_ACTIVE,
            self::STATUS_DELETED,
            self::STATUS_MAINTENANCE,
        ],
        self::STATUS_ACTIVE => [
            self::STATUS_COMPLETED,
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
        ],
        self::STATUS_INACTIVE => [
            self::STATUS_ACTIVE,
            self::STATUS_DRAFT,
            self::STATUS_DELETED,
        ],
        self::STATUS_MAINTENANCE => [
            self::STATUS_INACTIVE,
            self::STATUS_ACTIVE,
            self::STATUS_DRAFT,
            self::STATUS_DELETED,
        ],
        self::STATUS_APPROVED => [
            self::STATUS_COMPLETED,
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
        ],
    ];

    // Statuses that cannot be updated
    const DISALLOWED_UPDATE_STATUS_LIST = [
        self::STATUS_COMPLETED,
        self::STATUS_DELETED,
        self::STATUS_REJECTED,
    ];
}