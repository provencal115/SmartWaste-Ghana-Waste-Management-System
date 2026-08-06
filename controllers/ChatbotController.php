<?php
require_once __DIR__ . '/../includes/Controller.php';
require_once __DIR__ . '/../includes/ChatbotEngine.php';

class ChatbotController extends Controller
{
    public function init(): void
    {
        $sessionId = trim($_GET['session_id'] ?? '');
        if ($sessionId === '' || !preg_match('/^[a-zA-Z0-9_-]{8,64}$/', $sessionId)) {
            $this->json(['success' => false, 'message' => 'Invalid session.'], 400);
        }

        $engine = new ChatbotEngine();
        $history = [];

        try {
            $history = ChatbotModel::sessionHistory($sessionId);
        } catch (Throwable $e) {
            $history = [];
        }

        $formattedHistory = [];
        foreach ($history as $row) {
            $formattedHistory[] = [
                'role' => 'user',
                'text' => $row['user_message'],
                'time' => $row['created_at'],
            ];
            $formattedHistory[] = [
                'role' => 'bot',
                'text' => $row['bot_response'],
                'time' => $row['created_at'],
            ];
        }

        $this->json([
            'success' => true,
            'assistant_name' => 'SmartWaste Virtual Assistant',
            'welcome' => $engine->welcomeMessage(),
            'suggestions' => $engine->suggestions(),
            'history' => $formattedHistory,
            'csrf' => Csrf::token(),
        ]);
    }

    public function send(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Method not allowed.'], 405);
        }

        Csrf::validate();

        $payload = json_decode(file_get_contents('php://input') ?: '{}', true);
        if (!is_array($payload)) {
            $payload = $_POST;
        }

        $message = trim((string)($payload['message'] ?? ''));
        $sessionId = trim((string)($payload['session_id'] ?? ''));

        if ($message === '') {
            $this->json(['success' => false, 'message' => 'Message is required.'], 422);
        }

        if ($sessionId === '' || !preg_match('/^[a-zA-Z0-9_-]{8,64}$/', $sessionId)) {
            $this->json(['success' => false, 'message' => 'Invalid session.'], 400);
        }

        if (mb_strlen($message) > 2000) {
            $this->json(['success' => false, 'message' => 'Message is too long.'], 422);
        }

        $engine = new ChatbotEngine();
        $result = $engine->reply($message);

        $user = Auth::user();
        $userId = $user['id'] ?? null;

        try {
            ChatbotModel::logMessage(
                $sessionId,
                $userId ? (int)$userId : null,
                $message,
                $result['response'],
                $result['knowledge_id'],
                $result['category']
            );

            if ($result['knowledge_id'] && ($result['matched'] ?? false)) {
                ChatbotModel::incrementUse($result['knowledge_id']);
            }

            ChatbotModel::trackFaq($message, $result['knowledge_id']);
        } catch (Throwable $e) {
            // Continue even if logging fails
        }

        $this->json([
            'success' => true,
            'response' => $result['response'],
            'matched' => $result['matched'] ?? false,
            'category' => $result['category'] ?? null,
            'time' => date('Y-m-d H:i:s'),
        ]);
    }
}
