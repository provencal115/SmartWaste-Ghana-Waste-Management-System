<?php
require_once __DIR__ . '/../includes/Controller.php';

class AdminController extends Controller
{
    public function dashboard(): void
    {
        $this->requireRole(['administrator']);
        $stats = AdminModel::dashboardStats();
        $collections = CollectionModel::stats();
        $contactStats = ContactMessageModel::stats();
        $routeStats = OptimizedRouteModel::analytics();
        $analytics = AnalyticsModel::fullReport(AnalyticsModel::parseFilters([]));
        $inventoryAlerts = [];
        try {
            $inventoryAlerts = InventoryForecastModel::lowStockAlerts();
        } catch (Throwable $e) {
            $inventoryAlerts = [];
        }
        $cashStats = PaymentModel::cashStats();
        $this->view('admin/dashboard', compact('stats', 'collections', 'contactStats', 'routeStats', 'analytics', 'inventoryAlerts', 'cashStats'));
    }

    public function cashPayments(): void
    {
        $this->requireRole(['administrator']);
        $filters = [
            'status'    => trim($_GET['status'] ?? ''),
            'date_from' => trim($_GET['date_from'] ?? ''),
            'date_to'   => trim($_GET['date_to'] ?? ''),
        ];
        $stats = PaymentModel::cashStats($filters);
        $payments = PaymentModel::cashPayments($filters);
        $this->view('admin/cash-payments', compact('stats', 'payments', 'filters'));
    }

    public function analytics(): void
    {
        $this->requireRole(['administrator']);
        $filters = AnalyticsModel::parseFilters($_GET);
        $report = AnalyticsModel::fullReport($filters);
        $zones = ZoneModel::all();
        $collectors = CollectorModel::allWithUsers();
        $trucks = TruckModel::all();
        $this->view('admin/analytics', compact('report', 'filters', 'zones', 'collectors', 'trucks'));
    }

    public function users(): void
    {
        $this->requireRole(['administrator']);
        $users = UserModel::all();
        $this->view('admin/users', compact('users'));
    }

    public function usersPost(): void
    {
        $admin = $this->requireRole(['administrator']);
        $this->validateCsrf();
        $action = $_POST['action'] ?? '';

        if ($action === 'resend_welcome') {
            $userId = (int) ($_POST['user_id'] ?? 0);
            $user = UserModel::findById($userId);
            if (!$user || $user['role_name'] !== 'resident') {
                setFlash('error', 'Customer not found.');
                redirect('admin/users');
            }
            $sent = Mailer::sendWelcomeEmail($user);
            logActivity((int) $admin['id'], 'welcome_email_resent', 'admin', ['user_id' => $userId, 'sent' => $sent]);
            setFlash($sent ? 'success' : 'error', $sent
                ? 'Welcome email sent successfully to ' . $user['email'] . '.'
                : 'Could not send welcome email. Check SMTP settings.');
        }

        redirect('admin/users');
    }

    public function sms(): void
    {
        $this->requireRole(['administrator']);
        $filters = [
            'status'    => trim($_GET['status'] ?? ''),
            'type'      => trim($_GET['type'] ?? ''),
            'q'         => trim($_GET['q'] ?? ''),
            'date_from' => trim($_GET['date_from'] ?? ''),
            'date_to'   => trim($_GET['date_to'] ?? ''),
        ];
        $messages = SmsMessageModel::all($filters);
        $stats = SmsMessageModel::stats();
        $this->view('admin/sms', compact('messages', 'filters', 'stats'));
    }

    public function smsPost(): void
    {
        $this->requireRole(['administrator']);
        $this->validateCsrf();
        $action = $_POST['action'] ?? '';

        if ($action === 'resend') {
            $id = (int) ($_POST['sms_id'] ?? 0);
            if ($id && SmsService::resend($id)) {
                setFlash('success', 'SMS resent successfully.');
            } else {
                setFlash('error', 'Could not resend SMS.');
            }
        }

        redirect('admin/sms');
    }

    public function routes(): void
    {
        $this->requireRole(['administrator']);
        $zones = ZoneModel::allWithStats();
        $routes = RouteModel::allWithStats();
        $collectors = CollectorModel::allWithUsers();
        $trucks = TruckModel::all();
        $this->view('admin/routes', compact('zones', 'routes', 'collectors', 'trucks'));
    }

    public function routesPost(): void
    {
        $user = $this->requireRole(['administrator']);
        $this->validateCsrf();
        $action = $_POST['action'] ?? '';

        try {
            match ($action) {
                'create_zone' => ZoneModel::create([
                    'name' => trim($_POST['name']),
                    'description' => trim($_POST['description'] ?? ''),
                    'region' => trim($_POST['region'] ?? 'Ghana'),
                    'is_active' => !empty($_POST['is_active']) ? 1 : 0,
                ]),
                'update_zone' => ZoneModel::update((int)$_POST['zone_id'], [
                    'name' => trim($_POST['name']),
                    'description' => trim($_POST['description'] ?? ''),
                    'region' => trim($_POST['region'] ?? 'Ghana'),
                    'is_active' => !empty($_POST['is_active']) ? 1 : 0,
                ]),
                'delete_zone' => ZoneModel::delete((int)$_POST['zone_id']),
                'create_route' => RouteModel::create([
                    'name' => trim($_POST['name']),
                    'zone_id' => (int)$_POST['zone_id'],
                    'collector_id' => !empty($_POST['collector_id']) ? (int)$_POST['collector_id'] : null,
                    'truck_id' => !empty($_POST['truck_id']) ? (int)$_POST['truck_id'] : null,
                    'is_active' => !empty($_POST['is_active']) ? 1 : 0,
                ]),
                'update_route' => RouteModel::update((int)$_POST['route_id'], [
                    'name' => trim($_POST['name']),
                    'zone_id' => (int)$_POST['zone_id'],
                    'collector_id' => !empty($_POST['collector_id']) ? (int)$_POST['collector_id'] : null,
                    'truck_id' => !empty($_POST['truck_id']) ? (int)$_POST['truck_id'] : null,
                    'is_active' => !empty($_POST['is_active']) ? 1 : 0,
                ]),
                'delete_route' => RouteModel::delete((int)$_POST['route_id']),
                default => throw new InvalidArgumentException('Invalid action'),
            };
            setFlash('success', 'Changes saved successfully.');
        } catch (Throwable $e) {
            setFlash('error', 'Could not save: ' . $e->getMessage());
        }
        redirect('admin/routes');
    }

    public function routeOptimisation(): void
    {
        $this->requireRole(['administrator']);
        $zones = ZoneModel::allWithStats();
        $collectors = CollectorModel::allWithUsers();
        $trucks = TruckModel::all();
        $routeHistory = OptimizedRouteModel::history(15);
        $routeStats = OptimizedRouteModel::analytics();

        $selectedDate = trim($_GET['date'] ?? date('Y-m-d'));
        $selectedZone = (int)($_GET['zone_id'] ?? 0);
        $selectedCollector = (int)($_GET['collector_id'] ?? 0);
        $selectedTruck = (int)($_GET['truck_id'] ?? 0);
        $optimizedRoute = null;

        if ($selectedZone && $selectedCollector && $selectedDate) {
            $optimizedRoute = OptimizedRouteModel::findCurrent($selectedZone, $selectedCollector, $selectedDate);
            if ($optimizedRoute) {
                $optimizedRoute['route_data_decoded'] = OptimizedRouteModel::decodeRouteData($optimizedRoute['route_data'] ?? null);
            }
        }

        $previewCount = 0;
        if ($selectedZone && $selectedDate) {
            try {
                $previewCount = count(CollectionModel::scheduledForOptimisation(
                    $selectedDate,
                    $selectedZone,
                    $selectedCollector ?: null
                ));
            } catch (Throwable) {
                $previewCount = 0;
            }
        }

        $this->view('admin/route-optimisation', compact(
            'zones', 'collectors', 'trucks', 'routeHistory', 'routeStats',
            'selectedDate', 'selectedZone', 'selectedCollector', 'selectedTruck',
            'optimizedRoute', 'previewCount'
        ));
    }

    public function routeOptimisationPost(): void
    {
        $user = $this->requireRole(['administrator']);
        $this->validateCsrf();
        $action = $_POST['action'] ?? 'optimise';

        $date = trim($_POST['collection_date'] ?? date('Y-m-d'));
        $zoneId = (int)($_POST['zone_id'] ?? 0);
        $collectorId = (int)($_POST['collector_id'] ?? 0);
        $truckId = !empty($_POST['truck_id']) ? (int)$_POST['truck_id'] : null;
        $notes = trim($_POST['notes'] ?? '') ?: null;

        $redirectParams = [
            'date' => $date,
            'zone_id' => $zoneId,
            'collector_id' => $collectorId,
            'truck_id' => $truckId ?? 0,
        ];

        try {
            if (!$zoneId || !$collectorId || !$date) {
                throw new InvalidArgumentException('Please select collection date, zone, and collector.');
            }

            $reoptimise = $action === 'reoptimise';
            OptimizedRouteModel::optimise($date, $zoneId, $collectorId, $truckId, (int)$user['id'], $reoptimise, $notes);
            setFlash('success', $reoptimise ? 'Route re-optimised successfully.' : 'Route optimised successfully.');
        } catch (Throwable $e) {
            setFlash('error', $e->getMessage());
        }

        redirect('admin/route-optimisation', $redirectParams);
    }

    public function trucks(): void
    {
        $this->requireRole(['administrator']);
        $trucks = TruckModel::all();
        $this->view('admin/trucks', compact('trucks'));
    }

    public function complaints(): void
    {
        $this->requireRole(['administrator']);
        $complaints = ComplaintModel::all();
        $this->view('admin/complaints', compact('complaints'));
    }

    public function complaintsPost(): void
    {
        $this->requireRole(['administrator']);
        $this->validateCsrf();
        ComplaintModel::updateStatus((int)$_POST['complaint_id'], $_POST['status'], trim($_POST['resolution_notes'] ?? ''));
        setFlash('success', 'Complaint updated.');
        redirect('admin/complaints');
    }

    public function reports(): void
    {
        $this->requireRole(['administrator']);
        $this->view('admin/reports');
    }

    public function logs(): void
    {
        $this->requireRole(['administrator']);
        $logs = ActivityModel::recent();
        $this->view('admin/logs', compact('logs'));
    }

    public function settings(): void
    {
        $this->requireRole(['administrator']);
        $settings = SettingModel::all();
        $this->view('admin/settings', compact('settings'));
    }

    public function settingsPost(): void
    {
        $user = $this->requireRole(['administrator']);
        $this->validateCsrf();

        $map = [
            'route_optimization' => [
                'enabled' => !empty($_POST['route_optimization_enabled']),
                'algorithm' => $_POST['route_optimization_algorithm'] ?? 'nearest_neighbor',
            ],
            'bin_fullness_prediction' => [
                'enabled' => !empty($_POST['bin_fullness_enabled']),
                'threshold_percent' => (int)($_POST['bin_fullness_threshold'] ?? 80),
            ],
            'demand_prediction' => [
                'enabled' => !empty($_POST['demand_prediction_enabled']),
                'lookback_days' => (int)($_POST['demand_lookback_days'] ?? 30),
            ],
            'auto_reschedule' => [
                'enabled' => !empty($_POST['auto_reschedule_enabled']),
                'delay_minutes' => (int)($_POST['auto_reschedule_delay'] ?? 60),
            ],
            'reminder_system' => [
                'payment_days_before' => (int)($_POST['reminder_payment_days'] ?? 3),
                'pickup_hours_before' => (int)($_POST['reminder_pickup_hours'] ?? 24),
            ],
            'ai_assistant' => [
                'enabled'         => !empty($_POST['ai_assistant_enabled']),
                'assistant_name'  => trim($_POST['ai_assistant_name'] ?? '') ?: 'SmartWaste Assistant',
                'welcome_message' => trim($_POST['ai_assistant_welcome'] ?? ''),
                'company_info'    => trim($_POST['ai_assistant_company_info'] ?? ''),
            ],
            'inventory_forecast' => [
                'enabled'            => !empty($_POST['inventory_forecast_enabled']),
                'lookback_days'      => (int)($_POST['inventory_lookback_days'] ?? 90),
                'safety_stock_days'  => (int)($_POST['inventory_safety_days'] ?? 30),
                'reorder_multiplier' => (float)($_POST['inventory_reorder_multiplier'] ?? 1.5),
                'minimum_by_size'    => [
                    'small'  => (int)($_POST['inventory_min_small'] ?? 20),
                    'medium' => (int)($_POST['inventory_min_medium'] ?? 20),
                    'large'  => (int)($_POST['inventory_min_large'] ?? 20),
                ],
            ],
        ];

        foreach ($map as $key => $value) {
            SettingModel::upsert($key, $value, (int)$user['id']);
        }

        logActivity((int)$user['id'], 'update_settings', 'admin');
        setFlash('success', 'Settings saved successfully.');
        redirect('admin/settings');
    }

    public function messages(): void
    {
        $this->requireRole(['administrator']);
        $filters = [
            'status'    => trim($_GET['status'] ?? ''),
            'date_from' => trim($_GET['date_from'] ?? ''),
            'date_to'   => trim($_GET['date_to'] ?? ''),
            'q'         => trim($_GET['q'] ?? ''),
        ];
        $messages = ContactMessageModel::all($filters);
        $stats = ContactMessageModel::stats();
        $this->view('admin/messages', compact('messages', 'filters', 'stats'));
    }

    public function messageView(): void
    {
        $this->requireRole(['administrator']);
        $id = (int) ($_GET['id'] ?? 0);
        $message = ContactMessageModel::findWithReplies($id);
        if (!$message) {
            $this->json(['error' => 'Message not found'], 404);
        }

        ContactMessageModel::markAsRead($id);
        $message = ContactMessageModel::findWithReplies($id);

        $this->json(['message' => $message]);
    }

    public function messagesPost(): void
    {
        $user = $this->requireRole(['administrator']);
        $this->validateCsrf();
        $action = $_POST['action'] ?? '';

        try {
            match ($action) {
                'reply' => $this->handleContactReply($user),
                'delete' => $this->handleContactDelete(),
                'bulk_delete' => $this->handleContactBulkDelete(),
                default => throw new InvalidArgumentException('Invalid action'),
            };
        } catch (Throwable $e) {
            setFlash('error', 'Could not complete action: ' . $e->getMessage());
        }

        redirect('admin/messages');
    }

    private function handleContactReply(array $user): void
    {
        $id = (int) ($_POST['message_id'] ?? 0);
        $replyBody = mb_substr(strip_tags(trim($_POST['reply_body'] ?? '')), 0, 5000);

        if ($replyBody === '') {
            throw new InvalidArgumentException('Reply message is required.');
        }

        $message = ContactMessageModel::find($id);
        if (!$message) {
            throw new InvalidArgumentException('Message not found.');
        }

        $adminName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        if ($adminName === '') {
            $adminName = 'Smart Waste Management Support';
        }

        $emailSent = false;
        try {
            $emailSent = Mailer::sendContactReply($message, $replyBody, $adminName);
        } catch (Throwable) {
            $emailSent = false;
        }

        ContactMessageModel::addReply($id, (int) $user['id'], $replyBody, $emailSent);

        logActivity((int) $user['id'], 'contact_reply', 'admin', ['message_id' => $id, 'email_sent' => $emailSent]);

        if ($emailSent) {
            setFlash('success', 'Reply sent successfully and message marked as replied.');
        } else {
            setFlash('success', 'Reply saved. Email could not be sent — check SMTP settings or copy the response to the customer manually.');
        }
    }

    private function handleContactDelete(): void
    {
        $id = (int) ($_POST['message_id'] ?? 0);
        if (!$id || !ContactMessageModel::delete($id)) {
            throw new InvalidArgumentException('Message could not be deleted.');
        }
        setFlash('success', 'Message deleted successfully.');
    }

    private function handleContactBulkDelete(): void
    {
        $ids = $_POST['message_ids'] ?? [];
        if (!is_array($ids)) {
            $ids = [];
        }
        $count = ContactMessageModel::deleteMany($ids);
        if ($count === 0) {
            throw new InvalidArgumentException('No messages were selected.');
        }
        setFlash('success', $count . ' message(s) deleted successfully.');
    }

    public function chatbot(): void
    {
        $this->requireRole(['administrator']);
        $knowledge = ChatbotModel::allKnowledge();
        $stats = ChatbotModel::stats();
        $faqs = ChatbotModel::topFaqs(15);
        $recent = ChatbotModel::recentMessages(15);
        $categories = ['registration', 'login', 'schedule', 'payment', 'bins', 'notifications', 'complaints', 'finance', 'contact', 'greeting', 'fallback'];
        $editId = (int)($_GET['edit'] ?? 0);
        $editItem = $editId ? ChatbotModel::find($editId) : null;
        $this->view('admin/chatbot', compact('knowledge', 'stats', 'faqs', 'recent', 'categories', 'editItem', 'editId'));
    }

    public function chatbotPost(): void
    {
        $this->requireRole(['administrator']);
        $this->validateCsrf();
        $action = $_POST['action'] ?? '';

        try {
            match ($action) {
                'create', 'update' => $this->handleChatbotSave($action),
                'delete' => $this->handleChatbotDelete(),
                'toggle' => $this->handleChatbotToggle(),
                default => throw new InvalidArgumentException('Invalid action'),
            };
        } catch (Throwable $e) {
            setFlash('error', $e->getMessage());
        }

        redirect('admin/chatbot');
    }

    private function handleChatbotSave(string $action): void
    {
        $data = [
            'category' => trim($_POST['category'] ?? ''),
            'title' => trim($_POST['title'] ?? ''),
            'keywords' => trim($_POST['keywords'] ?? ''),
            'response' => trim($_POST['response'] ?? ''),
            'is_enabled' => isset($_POST['is_enabled']) ? 1 : 0,
            'is_suggestion' => isset($_POST['is_suggestion']) ? 1 : 0,
            'priority' => (int)($_POST['priority'] ?? 0),
        ];

        if ($data['title'] === '' || $data['keywords'] === '' || $data['response'] === '') {
            throw new InvalidArgumentException('Title, keywords, and response are required.');
        }

        if ($action === 'update') {
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                throw new InvalidArgumentException('Invalid response ID.');
            }
            ChatbotModel::update($id, $data);
            setFlash('success', 'Chatbot response updated successfully.');
            return;
        }

        ChatbotModel::create($data);
        setFlash('success', 'Chatbot response added successfully.');
    }

    private function handleChatbotDelete(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            throw new InvalidArgumentException('Invalid response ID.');
        }
        ChatbotModel::delete($id);
        setFlash('success', 'Chatbot response deleted.');
    }

    private function handleChatbotToggle(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $enabled = (int)($_POST['enabled'] ?? 0) === 1;
        if (!$id) {
            throw new InvalidArgumentException('Invalid response ID.');
        }
        ChatbotModel::toggle($id, $enabled);
        setFlash('success', 'Response ' . ($enabled ? 'enabled' : 'disabled') . '.');
    }
}
