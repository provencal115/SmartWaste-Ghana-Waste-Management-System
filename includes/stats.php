<?php
/**
 * Operational statistics — always sourced from the database.
 *
 * @return array<string, int>
 */
function operationalStats(): array
{
    $defaults = [
        'registered_residents'   => 0,
        'active_customers'       => 0,
        'collections_completed'  => 0,
        'collections_scheduled'  => 0,
        'collections_pending'    => 0,
        'collections_missed'     => 0,
        'bins_managed'           => 0,
        'fleet_vehicles'         => 0,
        'communities_served'     => 0,
    ];

    try {
        $registered = (int) (Model::fetchOne(
            'SELECT COUNT(*) AS c FROM residents WHERE registration_confirmed = 1'
        )['c'] ?? 0);

        $activeCustomers = (int) (Model::fetchOne(
            'SELECT COUNT(*) AS c FROM residents r
             JOIN users u ON r.user_id = u.id
             WHERE r.registration_confirmed = 1 AND u.is_active = 1'
        )['c'] ?? 0);

        $collectionsCompleted = (int) (Model::fetchOne(
            "SELECT COUNT(*) AS c FROM collection_schedules WHERE status = 'completed'"
        )['c'] ?? 0);

        $collectionsScheduled = (int) (Model::fetchOne(
            "SELECT COUNT(*) AS c FROM collection_schedules WHERE status IN ('scheduled', 'in_progress', 'rescheduled')"
        )['c'] ?? 0);

        $collectionsPending = (int) (Model::fetchOne(
            "SELECT COUNT(*) AS c FROM collection_schedules WHERE status = 'scheduled' AND preferred_date >= CURDATE()"
        )['c'] ?? 0);

        $collectionsMissed = (int) (Model::fetchOne(
            "SELECT COUNT(*) AS c FROM collection_schedules WHERE status = 'missed'"
        )['c'] ?? 0);

        $binsManaged = (int) (Model::fetchOne('SELECT COUNT(*) AS c FROM dustbins')['c'] ?? 0);

        $fleetVehicles = (int) (Model::fetchOne('SELECT COUNT(*) AS c FROM trucks WHERE status != \'retired\'')['c'] ?? 0);

        $communities = (int) (Model::fetchOne(
            'SELECT COUNT(DISTINCT zone_id) AS c FROM residents WHERE zone_id IS NOT NULL AND registration_confirmed = 1'
        )['c'] ?? 0);

        return [
            'registered_residents'  => $registered,
            'active_customers'      => $activeCustomers,
            'collections_completed' => $collectionsCompleted,
            'collections_scheduled' => $collectionsScheduled,
            'collections_pending'   => $collectionsPending,
            'collections_missed'    => $collectionsMissed,
            'bins_managed'          => $binsManaged,
            'fleet_vehicles'        => $fleetVehicles,
            'communities_served'    => max($communities, (int) (Model::fetchOne(
                'SELECT COUNT(*) AS c FROM collection_zones WHERE is_active = 1'
            )['c'] ?? 0)),
        ];
    } catch (Throwable) {
        return $defaults;
    }
}

/** @return array<string, int|string> */
function publicHomeStats(): array
{
    $ops = operationalStats();

    return [
        'residents'   => $ops['registered_residents'],
        'collections' => $ops['collections_completed'],
        'bins'        => $ops['bins_managed'],
        'trucks'      => $ops['fleet_vehicles'],
    ];
}
