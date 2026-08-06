<?php uiPageHeader('Zones & Routes', 'Manage collection zones, routes, and collector assignments',
    '<button type="button" class="btn-saas btn-saas-primary btn-saas-sm" data-bs-toggle="modal" data-bs-target="#createZoneModal"><i class="fa-solid fa-plus me-1"></i>New Zone</button>
     <button type="button" class="btn-saas btn-saas-secondary btn-saas-sm" data-bs-toggle="modal" data-bs-target="#createRouteModal"><i class="fa-solid fa-route me-1"></i>New Route</button>'
); ?>

<ul class="nav nav-pills settings-tabs mb-4 animate-in" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#zonesTab"><i class="fa-solid fa-map-location-dot me-2"></i>Zones <span class="badge bg-light text-dark ms-1"><?= count($zones) ?></span></button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#routesTab"><i class="fa-solid fa-route me-2"></i>Routes <span class="badge bg-light text-dark ms-1"><?= count($routes) ?></span></button></li>
</ul>

<div class="tab-content animate-in">
    <!-- Zones Tab -->
    <div class="tab-pane fade show active" id="zonesTab">
        <?php if (empty($zones)): ?>
        <?php uiEmptyState('fa-map-location-dot', 'No zones configured', 'Create your first collection zone to organize routes and residents.', null, 'route'); ?>
        <?php else: ?>
        <?php uiTableWrapOpen('Search zones by name, region, or description...'); ?>
        <thead>
            <tr>
                <?= uiSortableTh('Zone Name') ?>
                <?= uiSortableTh('Region') ?>
                <?= uiSortableTh('Residents') ?>
                <?= uiSortableTh('Scheduled Pickups') ?>
                <?= uiSortableTh('Status') ?>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($zones as $z): ?>
            <tr>
                <td>
                    <strong><?= e($z['name']) ?></strong>
                    <?php if (!empty($z['description'])): ?><br><small class="text-secondary"><?= e($z['description']) ?></small><?php endif; ?>
                </td>
                <td><?= e($z['region'] ?? 'Ghana') ?></td>
                <td><span class="badge bg-light text-dark border"><?= (int)($z['resident_count'] ?? 0) ?> residents</span></td>
                <td><span class="badge bg-primary-subtle text-primary"><?= (int)($z['scheduled_pickups'] ?? 0) ?> pickups</span></td>
                <td><?= !empty($z['is_active']) ? statusBadge('active') : statusBadge('inactive') ?></td>
                <td>
                    <div class="d-flex gap-1">
                        <button type="button" class="btn-saas btn-saas-ghost btn-saas-sm zone-edit-btn"
                                data-id="<?= (int)$z['id'] ?>"
                                data-name="<?= e($z['name']) ?>"
                                data-description="<?= e($z['description'] ?? '') ?>"
                                data-region="<?= e($z['region'] ?? 'Ghana') ?>"
                                data-active="<?= (int)($z['is_active'] ?? 1) ?>"
                                data-bs-toggle="modal" data-bs-target="#editZoneModal">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <form method="post" action="<?= baseUrl('admin/routes') ?>" class="d-inline zone-delete-form">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="action" value="delete_zone">
                            <input type="hidden" name="zone_id" value="<?= (int)$z['id'] ?>">
                            <button type="submit" class="btn-saas btn-saas-ghost btn-saas-sm text-danger"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <?php uiTableWrapClose(); ?>
        <?php endif; ?>
    </div>

    <!-- Routes Tab -->
    <div class="tab-pane fade" id="routesTab">
        <?php if (empty($routes)): ?>
        <?php uiEmptyState('fa-route', 'No routes configured', 'Create collection routes and assign collectors to zones.', null, 'route'); ?>
        <?php else: ?>
        <?php uiTableWrapOpen('Search routes by name, zone, or collector...'); ?>
        <thead>
            <tr>
                <?= uiSortableTh('Route Name') ?>
                <?= uiSortableTh('Zone') ?>
                <?= uiSortableTh('Collector') ?>
                <?= uiSortableTh('Truck') ?>
                <?= uiSortableTh('Scheduled Pickups') ?>
                <?= uiSortableTh('Status') ?>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($routes as $r): ?>
            <tr>
                <td><strong><?= e($r['name']) ?></strong></td>
                <td><span class="status-pill status-info"><span class="status-dot"></span><?= e($r['zone_name'] ?? '—') ?></span></td>
                <td><?= e($r['collector_name'] ?? 'Unassigned') ?><?php if (!empty($r['employee_id'])): ?><br><small class="text-secondary"><?= e($r['employee_id']) ?></small><?php endif; ?></td>
                <td><?= e($r['plate_number'] ?? '—') ?></td>
                <td><span class="badge bg-warning-subtle text-warning-emphasis"><?= (int)($r['scheduled_pickups'] ?? 0) ?></span></td>
                <td><?= !empty($r['is_active']) ? statusBadge('active') : statusBadge('inactive') ?></td>
                <td>
                    <div class="d-flex gap-1">
                        <button type="button" class="btn-saas btn-saas-ghost btn-saas-sm route-edit-btn"
                                data-id="<?= (int)$r['id'] ?>"
                                data-name="<?= e($r['name']) ?>"
                                data-zone="<?= (int)$r['zone_id'] ?>"
                                data-collector="<?= (int)($r['collector_id'] ?? 0) ?>"
                                data-truck="<?= (int)($r['truck_id'] ?? 0) ?>"
                                data-active="<?= (int)($r['is_active'] ?? 1) ?>"
                                data-bs-toggle="modal" data-bs-target="#editRouteModal">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <form method="post" action="<?= baseUrl('admin/routes') ?>" class="d-inline route-delete-form">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="action" value="delete_route">
                            <input type="hidden" name="route_id" value="<?= (int)$r['id'] ?>">
                            <button type="submit" class="btn-saas btn-saas-ghost btn-saas-sm text-danger"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <?php uiTableWrapClose(); ?>
        <?php endif; ?>
    </div>
</div>

<!-- Create Zone Modal -->
<div class="modal fade" id="createZoneModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="<?= baseUrl('admin/routes') ?>" class="modal-content saas-modal">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="create_zone">
            <div class="modal-header"><h5 class="modal-title"><i class="fa-solid fa-map-location-dot me-2 text-success"></i>New Collection Zone</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Zone Name</label><input name="name" class="form-control" required placeholder="e.g. East Legon"></div>
                <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2" placeholder="Brief area description"></textarea></div>
                <div class="mb-3"><label class="form-label">Region</label><input name="region" class="form-control" value="Greater Accra"></div>
                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="czActive" checked><label class="form-check-label" for="czActive">Active zone</label></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn-saas btn-saas-ghost" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn-saas btn-saas-primary">Create Zone</button></div>
        </form>
    </div>
</div>

<!-- Edit Zone Modal -->
<div class="modal fade" id="editZoneModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="<?= baseUrl('admin/routes') ?>" class="modal-content saas-modal" id="editZoneForm">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="update_zone">
            <input type="hidden" name="zone_id" id="editZoneId">
            <div class="modal-header"><h5 class="modal-title"><i class="fa-solid fa-pen me-2 text-primary"></i>Edit Zone</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Zone Name</label><input name="name" id="editZoneName" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Description</label><textarea name="description" id="editZoneDesc" class="form-control" rows="2"></textarea></div>
                <div class="mb-3"><label class="form-label">Region</label><input name="region" id="editZoneRegion" class="form-control"></div>
                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="editZoneActive"><label class="form-check-label" for="editZoneActive">Active zone</label></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn-saas btn-saas-ghost" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn-saas btn-saas-primary">Save Changes</button></div>
        </form>
    </div>
</div>

<!-- Create Route Modal -->
<div class="modal fade" id="createRouteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="<?= baseUrl('admin/routes') ?>" class="modal-content saas-modal">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="create_route">
            <div class="modal-header"><h5 class="modal-title"><i class="fa-solid fa-route me-2 text-success"></i>New Collection Route</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Route Name</label><input name="name" class="form-control" required placeholder="e.g. East Legon Morning Route"></div>
                <div class="mb-3"><label class="form-label">Zone</label>
                    <select name="zone_id" class="form-select" required>
                        <option value="">Select zone</option>
                        <?php foreach ($zones as $z): ?><option value="<?= (int)$z['id'] ?>"><?= e($z['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3"><label class="form-label">Collector</label>
                    <select name="collector_id" class="form-select">
                        <option value="">Unassigned</option>
                        <?php foreach ($collectors as $c): ?>
                        <option value="<?= (int)$c['id'] ?>"><?= e($c['first_name'] . ' ' . $c['last_name']) ?> (<?= e($c['employee_id']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3"><label class="form-label">Truck</label>
                    <select name="truck_id" class="form-select">
                        <option value="">None</option>
                        <?php foreach ($trucks as $t): ?>
                        <option value="<?= (int)$t['id'] ?>"><?= e($t['plate_number']) ?> — <?= e($t['model'] ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" value="1" checked><label class="form-check-label">Active route</label></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn-saas btn-saas-ghost" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn-saas btn-saas-primary">Create Route</button></div>
        </form>
    </div>
</div>

<!-- Edit Route Modal -->
<div class="modal fade" id="editRouteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="<?= baseUrl('admin/routes') ?>" class="modal-content saas-modal">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="update_route">
            <input type="hidden" name="route_id" id="editRouteId">
            <div class="modal-header"><h5 class="modal-title"><i class="fa-solid fa-pen me-2 text-primary"></i>Edit Route</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Route Name</label><input name="name" id="editRouteName" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Zone</label>
                    <select name="zone_id" id="editRouteZone" class="form-select" required>
                        <?php foreach ($zones as $z): ?><option value="<?= (int)$z['id'] ?>"><?= e($z['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3"><label class="form-label">Collector</label>
                    <select name="collector_id" id="editRouteCollector" class="form-select">
                        <option value="">Unassigned</option>
                        <?php foreach ($collectors as $c): ?>
                        <option value="<?= (int)$c['id'] ?>"><?= e($c['first_name'] . ' ' . $c['last_name']) ?> (<?= e($c['employee_id']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3"><label class="form-label">Truck</label>
                    <select name="truck_id" id="editRouteTruck" class="form-select">
                        <option value="">None</option>
                        <?php foreach ($trucks as $t): ?>
                        <option value="<?= (int)$t['id'] ?>"><?= e($t['plate_number']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="editRouteActive"><label class="form-check-label" for="editRouteActive">Active route</label></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn-saas btn-saas-ghost" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn-saas btn-saas-primary">Save Changes</button></div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('.zone-edit-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('editZoneId').value = btn.dataset.id;
        document.getElementById('editZoneName').value = btn.dataset.name;
        document.getElementById('editZoneDesc').value = btn.dataset.description;
        document.getElementById('editZoneRegion').value = btn.dataset.region;
        document.getElementById('editZoneActive').checked = btn.dataset.active === '1';
    });
});
document.querySelectorAll('.route-edit-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('editRouteId').value = btn.dataset.id;
        document.getElementById('editRouteName').value = btn.dataset.name;
        document.getElementById('editRouteZone').value = btn.dataset.zone;
        document.getElementById('editRouteCollector').value = btn.dataset.collector || '';
        document.getElementById('editRouteTruck').value = btn.dataset.truck || '';
        document.getElementById('editRouteActive').checked = btn.dataset.active === '1';
    });
});
document.querySelectorAll('.zone-delete-form, .route-delete-form').forEach(form => {
    form.addEventListener('submit', e => {
        e.preventDefault();
        confirmDelete('This will deactivate the zone or permanently remove the route.', () => form.submit());
    });
});
</script>
