<?php
require_once __DIR__ . '/../includes/Controller.php';

class CollectorController extends Controller
{
    private function collectorProfile(array $user): ?array
    {
        return CollectorModel::ensureForUser((int)$user['id']);
    }

    private function zoneId(?array $collector): ?int
    {
        return $collector && !empty($collector['zone_id']) ? (int)$collector['zone_id'] : null;
    }

    public function dashboard(): void
    {
        $user = $this->requireRole(['collector']);
        $collector = $this->collectorProfile($user);
        $filter = $_GET['filter'] ?? 'today';
        $allowed = ['all', 'today', 'upcoming', 'completed', 'missed', 'cancelled'];
        if (!in_array($filter, $allowed, true)) {
            $filter = 'today';
        }

        $zoneId = $this->zoneId($collector);
        $pickups = $collector ? CollectionModel::forCollector((int)$collector['id'], $filter, $zoneId) : [];
        $stats = $collector ? CollectionModel::collectorStats((int)$collector['id'], $zoneId) : [
            'today_total' => 0, 'today_completed' => 0, 'today_pending' => 0, 'today_in_progress' => 0,
        ];

        $this->view('collector/dashboard', compact('user', 'collector', 'pickups', 'stats', 'filter'));
    }

    public function routes(): void
    {
        $user = $this->requireRole(['collector']);
        $collector = $this->collectorProfile($user);
        $zoneId = $this->zoneId($collector);
        $schedule = $collector ? CollectionModel::forCollector((int)$collector['id'], 'today', $zoneId) : [];
        $this->view('collector/routes', compact('schedule', 'collector'));
    }

    public function scan(): void
    {
        $this->requireRole(['collector']);
        $this->view('collector/scan');
    }

    public function scanPost(): void
    {
        $this->requireRole(['collector']);
        $code = trim($_POST['code'] ?? '');
        $bin = DustbinModel::findByCode($code);
        if (isAjax()) {
            $this->json(['success' => (bool)$bin, 'data' => $bin]);
        }
        $this->view('collector/scan', compact('bin', 'code'));
    }

    public function updatePickup(): void
    {
        $user = $this->requireRole(['collector']);
        $this->validateCsrf();
        $collector = $this->collectorProfile($user);
        if (!$collector) {
            setFlash('error', 'Collector profile could not be loaded. Please contact an administrator.');
            redirect('collector/dashboard');
        }

        $scheduleId = (int)($_POST['schedule_id'] ?? 0);
        $status = $_POST['pickup_status'] ?? 'pending';
        $allowed = ['pending', 'in_progress', 'completed', 'missed', 'delayed'];
        if (!in_array($status, $allowed, true)) {
            setFlash('error', 'Invalid pickup status.');
            redirect('collector/dashboard');
        }

        $zoneId = $this->zoneId($collector);
        $pickup = CollectionModel::findForCollector($scheduleId, (int)$collector['id'], $zoneId);
        if (!$pickup) {
            setFlash('error', 'Pickup not found or not assigned to your zone.');
            redirect('collector/dashboard');
        }

        $notes = trim($_POST['collector_notes'] ?? '') ?: null;
        $proof = null;

        if (!empty($_FILES['proof_photo']['tmp_name']) && is_uploaded_file($_FILES['proof_photo']['tmp_name'])) {
            $dir = __DIR__ . '/../assets/uploads/proofs';
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $ext = pathinfo($_FILES['proof_photo']['name'], PATHINFO_EXTENSION) ?: 'jpg';
            $filename = 'proof_' . $scheduleId . '_' . time() . '.' . preg_replace('/[^a-z0-9]/i', '', $ext);
            if (move_uploaded_file($_FILES['proof_photo']['tmp_name'], $dir . '/' . $filename)) {
                $proof = 'uploads/proofs/' . $filename;
            }
        }

        CollectionModel::updatePickup($scheduleId, $status, (int)$collector['id'], $proof, $notes);

        if ($status === 'completed') {
            $residentUserId = (int) Model::fetchOne('SELECT user_id FROM residents WHERE id = ?', [$pickup['resident_id']])['user_id'];
            NotificationDispatcher::notify(
                $residentUserId,
                'Collection Complete',
                'Your waste was collected on ' . date('M j, Y') . '.',
                'collection_complete',
                true,
                ['date' => date('M j, Y')]
            );
        }

        setFlash('success', 'Pickup updated successfully.');
        $filter = $_POST['redirect_filter'] ?? 'today';
        redirect('collector/dashboard', ['filter' => $filter]);
    }

    public function reports(): void
    {
        $this->requireRole(['collector']);
        $this->view('collector/reports');
    }

    public function reportsPost(): void
    {
        $user = $this->requireRole(['collector']);
        $this->validateCsrf();
        $collector = $this->collectorProfile($user);
        if (!$collector) {
            setFlash('error', 'Collector profile could not be loaded. Please contact an administrator.');
            redirect('collector/reports');
        }
        CollectorModel::submitReport([
            'collector_id' => $collector['id'],
            'report_type' => $_POST['report_type'],
            'description' => trim($_POST['description']),
        ]);
        setFlash('success', 'Report submitted.');
        redirect('collector/reports');
    }
}
