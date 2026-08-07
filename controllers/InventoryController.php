<?php
require_once __DIR__ . '/../includes/Controller.php';

class InventoryController extends Controller
{
    public function dashboard(): void
    {
        $this->requireRole(['inventory_manager', 'administrator']);

        ProcurementModel::ensureTable();

        $totals = InventoryForecastModel::totals();
        $lifecycle = InventoryForecastModel::lifecycleBySize();
        $stock = InventoryForecastModel::stockBySize();
        $alerts = InventoryForecastModel::lowStockAlerts();
        $forecasts = InventoryForecastModel::forecastBySize();
        $trends = InventoryForecastModel::trendCharts(12);
        $movements = InventoryForecastModel::recentMovements(8);
        $procurementStats = ProcurementModel::stats();
        $settings = InventoryForecastModel::settings();
        $hasTrendData = InventoryForecastModel::hasTrendData($trends);
        $procurementReady = ProcurementModel::isAvailable();

        $this->view('inventory/dashboard', compact(
            'totals',
            'lifecycle',
            'stock',
            'alerts',
            'forecasts',
            'trends',
            'movements',
            'procurementStats',
            'settings',
            'hasTrendData',
            'procurementReady'
        ));
    }

    public function bins(): void
    {
        $this->requireRole(['inventory_manager']);
        $bins = DustbinModel::all();
        $stats = DustbinModel::stats();
        $this->view('inventory/bins', compact('bins', 'stats'));
    }

    public function binsPost(): void
    {
        $user = $this->requireRole(['inventory_manager']);
        $this->validateCsrf();
        $id = DustbinModel::create([
            'size' => $_POST['size'],
            'color' => $_POST['color'],
            'brand' => trim($_POST['brand'] ?? 'EcoBin'),
            'warehouse_location' => trim($_POST['warehouse_location'] ?? 'Warehouse A'),
        ]);

        try {
            Model::query(
                'INSERT INTO inventory_movements (dustbin_id, movement_type, to_location, performed_by, notes)
                 VALUES (?, ?, ?, ?, ?)',
                [$id, 'delivery', trim($_POST['warehouse_location'] ?? 'Warehouse A'), (int)$user['id'], 'New stock added via inventory manager']
            );
        } catch (Throwable $e) {
            // Movement logging is optional — bin creation must still succeed
        }

        logActivity($user['id'], 'add_bin', 'inventory');
        setFlash('success', 'Bin added successfully.');
        redirect('inventory/bins');
    }

    public function procurement(): void
    {
        $this->requireRole(['inventory_manager', 'administrator']);
        ProcurementModel::ensureTable();

        $requests = ProcurementModel::all();
        $forecasts = InventoryForecastModel::forecastBySize();
        $stats = ProcurementModel::stats();
        $procurementReady = ProcurementModel::isAvailable();

        $this->view('inventory/procurement', compact('requests', 'forecasts', 'stats', 'procurementReady'));
    }

    public function procurementPost(): void
    {
        $user = $this->requireRole(['inventory_manager', 'administrator']);
        $this->validateCsrf();

        if (!ProcurementModel::ensureTable()) {
            setFlash('error', 'Procurement module is unavailable. Please contact an administrator.');
            redirect('inventory/dashboard');
        }

        $size = $_POST['bin_size'] ?? '';
        $quantity = (int)($_POST['quantity'] ?? 0);
        if (!in_array($size, ['small', 'medium', 'large'], true) || $quantity < 1) {
            setFlash('error', 'Invalid procurement request.');
            redirect('inventory/procurement');
        }

        $forecast = null;
        foreach (InventoryForecastModel::forecastBySize() as $f) {
            if ($f['size'] === $size) {
                $forecast = $f;
                break;
            }
        }

        try {
            ProcurementModel::create([
                'bin_size'              => $size,
                'quantity'              => $quantity,
                'recommended_quantity'  => (int)($forecast['recommended_reorder'] ?? $quantity),
                'reason'                => trim($_POST['reason'] ?? '') ?: 'Procurement request from inventory forecast',
                'requested_by'          => (int)$user['id'],
                'notes'                 => trim($_POST['notes'] ?? ''),
            ]);
        } catch (Throwable $e) {
            setFlash('error', 'Could not save procurement request.');
            redirect('inventory/procurement');
        }

        logActivity((int)$user['id'], 'create_procurement_request', 'inventory', ['size' => $size, 'quantity' => $quantity]);
        setFlash('success', 'Procurement request submitted successfully.');
        redirect('inventory/procurement');
    }

    public function procurementStatusPost(): void
    {
        $user = $this->requireRole(['inventory_manager', 'administrator']);
        $this->validateCsrf();

        if (!ProcurementModel::isAvailable()) {
            setFlash('error', 'Procurement module is unavailable.');
            redirect('inventory/dashboard');
        }

        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $allowed = ['pending', 'approved', 'ordered', 'received', 'cancelled'];
        if ($id < 1 || !in_array($status, $allowed, true)) {
            setFlash('error', 'Invalid status update.');
            redirect('inventory/procurement');
        }

        try {
            ProcurementModel::updateStatus($id, $status);
        } catch (Throwable $e) {
            setFlash('error', 'Could not update procurement request.');
            redirect('inventory/procurement');
        }

        logActivity((int)$user['id'], 'update_procurement_status', 'inventory', ['id' => $id, 'status' => $status]);
        setFlash('success', 'Procurement request updated.');
        redirect('inventory/procurement');
    }

    public function reports(): void
    {
        $this->requireRole(['inventory_manager']);
        $bins = DustbinModel::all();
        $stats = DustbinModel::stats();
        $this->view('inventory/reports', compact('bins', 'stats'));
    }
}
