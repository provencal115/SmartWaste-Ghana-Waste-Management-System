<?php
require_once __DIR__ . '/../includes/Controller.php';

class InventoryController extends Controller
{
    public function dashboard(): void
    {
        $this->requireRole(['inventory_manager']);
        $stats = DustbinModel::stats();
        $alerts = DustbinModel::lowStockAlerts();
        $this->view('inventory/dashboard', compact('stats', 'alerts'));
    }

    public function bins(): void
    {
        $this->requireRole(['inventory_manager']);
        $bins = DustbinModel::all();
        $this->view('inventory/bins', compact('bins'));
    }

    public function binsPost(): void
    {
        $user = $this->requireRole(['inventory_manager']);
        $this->validateCsrf();
        DustbinModel::create([
            'size' => $_POST['size'],
            'color' => $_POST['color'],
            'brand' => trim($_POST['brand'] ?? 'EcoBin'),
            'warehouse_location' => trim($_POST['warehouse_location'] ?? 'Warehouse A'),
        ]);
        logActivity($user['id'], 'add_bin', 'inventory');
        setFlash('success', 'Bin added successfully.');
        redirect('inventory/bins');
    }

    public function reports(): void
    {
        $this->requireRole(['inventory_manager']);
        $bins = DustbinModel::all();
        $this->view('inventory/reports', compact('bins'));
    }
}
