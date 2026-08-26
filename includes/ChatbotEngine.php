<?php

/**
 * SmartWaste customer assistant — account lookups, keyword KB, optional external AI.
 */
class ChatbotEngine
{
    private array $knowledge = [];
    private array $settings;

    public function __construct()
    {
        $this->settings = chatbotSettings();

        try {
            $this->knowledge = ChatbotModel::allKnowledge(true);
        } catch (Throwable $e) {
            $this->knowledge = [];
        }
    }

    public function assistantName(): string
    {
        return trim($this->settings['assistant_name'] ?? '') ?: 'SmartWaste Assistant';
    }

    public function isEnabled(): bool
    {
        return !empty($this->settings['enabled']);
    }

    public function welcomeMessage(?array $user = null): string
    {
        $custom = trim($this->settings['welcome_message'] ?? '');
        if ($custom !== '') {
            return $this->renderResponse($custom);
        }

        $name = $this->assistantName();
        $greeting = "Hello! I'm **{$name}** — your SmartWaste customer support assistant.";

        $context = ChatbotAccountService::contextForUser($user);
        if ($context) {
            $first = trim((string)($context['first_name'] ?? ''));
            if ($first !== '') {
                $greeting = "Hello {$first}! I'm **{$name}** — your SmartWaste customer support assistant.";
            }
            return $this->renderResponse(
                $greeting . " I can check your collection schedule, payments, bin details, and answer general waste-management questions. How can I help you today?"
            );
        }

        return $this->renderResponse(
            $greeting . " Ask me about registration, pickups, pricing, bin sizes, payments, recycling, or contact support. How can I help you today?"
        );
    }

    /** @return list<array{label: string, query: string}> */
    public function suggestions(?array $user = null): array
    {
        $context = ChatbotAccountService::contextForUser($user);

        $residentDefaults = [
            ['label' => 'Next Collection', 'query' => 'When is my next collection?'],
            ['label' => 'My Bill', 'query' => 'How much do I pay?'],
            ['label' => 'Missed Pickup', 'query' => 'How do I report a missed collection?'],
            ['label' => 'New Bin', 'query' => 'How do I get a new bin?'],
            ['label' => 'Bin Sizes', 'query' => 'What size bins are available?'],
            ['label' => 'Update Address', 'query' => 'How can I update my address?'],
        ];

        $guestDefaults = [
            ['label' => 'Register', 'query' => 'How do I register?'],
            ['label' => 'Schedule Pickup', 'query' => 'How do I schedule a pickup?'],
            ['label' => 'Bin Sizes', 'query' => 'What size bins are available?'],
            ['label' => 'Pricing', 'query' => 'What are your pricing plans?'],
            ['label' => 'Contact Us', 'query' => 'How can I contact support?'],
            ['label' => 'Recycling', 'query' => 'What can I recycle?'],
        ];

        $defaults = $context ? $residentDefaults : $guestDefaults;

        try {
            $rows = ChatbotModel::suggestions();
            if (!$rows) {
                return $defaults;
            }
            $out = [];
            foreach ($rows as $row) {
                $keywords = array_filter(array_map('trim', explode(',', (string)$row['keywords'])));
                $query = $keywords[0] ?? $row['title'];
                $out[] = ['label' => $row['title'], 'query' => ucfirst($query)];
            }
            if ($context && count($out) < 4) {
                return $defaults;
            }
            return $out ?: $defaults;
        } catch (Throwable $e) {
            return $defaults;
        }
    }

    /**
     * @return array{response: string, knowledge_id: ?int, category: ?string, matched: bool, escalate: bool, source: string}
     */
    public function reply(string $message, ?array $user = null): array
    {
        $text = strtolower(trim($message));
        if ($text === '') {
            return $this->fallback();
        }

        $context = ChatbotAccountService::contextForUser($user);
        if ($context) {
            $accountAnswer = ChatbotAccountService::tryAnswer($message, $context);
            if ($accountAnswer) {
                return [
                    'response'     => $this->renderResponse($accountAnswer['response']),
                    'knowledge_id' => null,
                    'category'     => $accountAnswer['category'],
                    'matched'      => true,
                    'escalate'     => false,
                    'source'       => 'account',
                ];
            }
        }

        $keywordResult = $this->keywordReply($text);
        if ($keywordResult['matched']) {
            $keywordResult['source'] = 'knowledge';
            $keywordResult['escalate'] = false;
            return $keywordResult;
        }

        $aiResponse = $this->tryAiReply($message, $user, $context);
        if ($aiResponse !== null) {
            return [
                'response'     => $this->renderResponse($aiResponse),
                'knowledge_id' => null,
                'category'     => 'ai',
                'matched'      => true,
                'escalate'     => false,
                'source'       => 'ai',
            ];
        }

        return $this->fallback();
    }

    /** @return array{response: string, knowledge_id: ?int, category: ?string, matched: bool} */
    private function keywordReply(string $text): array
    {
        $best = null;
        $bestScore = 0;

        foreach ($this->knowledge as $entry) {
            if (($entry['category'] ?? '') === 'fallback') {
                continue;
            }

            $score = $this->scoreEntry($text, $entry);
            if ($score > $bestScore || ($score === $bestScore && $best && (int)$entry['priority'] > (int)$best['priority'])) {
                $bestScore = $score;
                $best = $entry;
            }
        }

        if ($best && $bestScore > 0) {
            return [
                'response'     => $this->renderResponse((string)$best['response']),
                'knowledge_id' => (int)$best['id'],
                'category'     => (string)$best['category'],
                'matched'      => true,
            ];
        }

        return ['response' => '', 'knowledge_id' => null, 'category' => null, 'matched' => false];
    }

    /** @param array<string, mixed>|null $context */
    private function tryAiReply(string $message, ?array $user, ?array $context): ?string
    {
        if (!ChatbotAiProvider::isAvailable()) {
            return null;
        }

        $info = companyInfo();
        $extra = trim($this->settings['company_info'] ?? '');

        $system = "You are {$this->assistantName()}, the customer support assistant for SmartWaste Ghana (waste collection service).\n"
            . "Answer ONLY waste-management, billing, scheduling, bins, recycling, and company service questions.\n"
            . "NEVER reveal passwords, database details, or any other customer's information.\n"
            . "If the user asks for account-specific data you do not have, say you cannot confirm and direct them to sign in or contact support.\n"
            . "If unsure, say: \"I'm not able to confirm that information. Please contact our support team.\"\n"
            . "Keep answers concise (under 120 words).\n\n"
            . "Company: {$info['name']}\nPhone: {$info['phone']}\nEmail: {$info['email']}\nHours: {$info['hours']}\n"
            . "Contact page: " . baseUrl('contact');

        if ($extra !== '') {
            $system .= "\n\nAdditional company info:\n" . $extra;
        }

        if ($context) {
            $resident = $context['resident'];
            $system .= "\n\nThe user is a logged-in resident. You may reference their general plan/zone but do NOT invent collection dates or payment amounts — those come from the dashboard.";
            $system .= "\nZone: " . ($resident['zone_name'] ?? 'unknown');
            $system .= "\nPlan: " . ($resident['payment_plan_name'] ?? 'unknown');
        }

        return ChatbotAiProvider::complete(
            [['role' => 'user', 'content' => $message]],
            $system
        );
    }

    private function scoreEntry(string $text, array $entry): int
    {
        $score = 0;
        $keywords = array_filter(array_map('trim', explode(',', strtolower((string)$entry['keywords']))));

        foreach ($keywords as $keyword) {
            if ($keyword === '') {
                continue;
            }
            if ($text === $keyword) {
                $score += 10;
            } elseif ($this->keywordInText($text, $keyword)) {
                $score += max(3, (int)round(strlen($keyword) / 3));
            }
        }

        // Match multi-word keywords when words appear in any order (e.g. "size bins" vs "bin sizes")
        if (str_contains($keywords[0] ?? '', ' ') === false && count($keywords) >= 2) {
            foreach ($keywords as $keyword) {
                if (str_contains($keyword, ' ') && $this->allWordsPresent($text, $keyword)) {
                    $score += 6;
                }
            }
        }

        $title = strtolower((string)$entry['title']);
        if ($title !== '' && str_contains($text, $title)) {
            $score += 4;
        }

        return $score;
    }

    private function keywordInText(string $text, string $keyword): bool
    {
        if (str_contains($keyword, ' ') && $this->allWordsPresent($text, $keyword)) {
            return true;
        }

        if (strlen($keyword) <= 3) {
            return (bool)preg_match('/\b' . preg_quote($keyword, '/') . '\b/u', $text);
        }

        return str_contains($text, $keyword);
    }

    private function allWordsPresent(string $text, string $phrase): bool
    {
        foreach (preg_split('/\s+/', trim($phrase)) as $word) {
            if ($word === '') {
                continue;
            }
            if (!preg_match('/\b' . preg_quote($word, '/') . '\b/u', $text)) {
                return false;
            }
        }
        return true;
    }

    /** @return array{response: string, knowledge_id: ?int, category: ?string, matched: bool, escalate: bool, source: string} */
    private function fallback(): array
    {
        foreach ($this->knowledge as $entry) {
            if (($entry['category'] ?? '') === 'fallback') {
                return [
                    'response'     => $this->renderResponse((string)$entry['response']),
                    'knowledge_id' => (int)$entry['id'],
                    'category'     => 'fallback',
                    'matched'      => false,
                    'escalate'     => true,
                    'source'       => 'fallback',
                ];
            }
        }

        return [
            'response'     => $this->renderResponse(
                "I'm not able to confirm that information. I can help you contact our support team.\n\n"
                . "Contact Support: {contact_url}\n"
                . "Phone: {phone}\n"
                . "Email: {email}"
            ),
            'knowledge_id' => null,
            'category'     => 'fallback',
            'matched'      => false,
            'escalate'     => true,
            'source'       => 'fallback',
        ];
    }

    private function renderResponse(string $template): string
    {
        $info = companyInfo();
        $replacements = [
            '{register_url}'     => baseUrl('auth/register'),
            '{login_url}'        => baseUrl('auth/login'),
            '{forgot_url}'       => baseUrl('auth/forgot'),
            '{contact_url}'      => baseUrl('contact'),
            '{phone}'            => $info['phone'] ?? '',
            '{phone_alt}'        => $info['phone_alt'] ?? '',
            '{email}'            => $info['email'] ?? '',
            '{emergency}'        => $info['emergency'] ?? '',
            '{address}'          => $info['address'] ?? '',
            '{hours}'            => $info['hours'] ?? '',
            '{pricing_table}'    => $this->pricingTable(),
            '{bin_colours_list}' => $this->binColoursList(),
        ];

        $out = str_replace(array_keys($replacements), array_values($replacements), $template);
        return preg_replace('/\*\*(.+?)\*\*/', '$1', $out) ?? $out;
    }

    private function pricingTable(): string
    {
        try {
            $rows = Model::fetchAll(
                'SELECT pp.name AS plan_name, pp.frequency, p.bin_size, p.price
                 FROM pricing_policies p
                 JOIN payment_plans pp ON p.payment_plan_id = pp.id
                 WHERE p.is_active = 1 AND pp.is_active = 1
                 ORDER BY FIELD(p.bin_size, "small", "medium", "large"), pp.id'
            );
        } catch (Throwable $e) {
            $rows = [];
        }

        if (!$rows) {
            return '• Small (120L): Weekly ' . formatCurrencyPlain(15) . ' · Bi-weekly ' . formatCurrencyPlain(28) . ' · Monthly ' . formatCurrencyPlain(50) . "\n"
                . '• Medium (240L): Weekly ' . formatCurrencyPlain(25) . ' · Bi-weekly ' . formatCurrencyPlain(48) . ' · Monthly ' . formatCurrencyPlain(90) . "\n"
                . '• Large (360L): Weekly ' . formatCurrencyPlain(40) . ' · Bi-weekly ' . formatCurrencyPlain(75) . ' · Monthly ' . formatCurrencyPlain(140);
        }

        $grouped = [];
        foreach ($rows as $row) {
            $size = ucfirst((string)$row['bin_size']) . ' (' . binCapacity($row['bin_size']) . 'L)';
            $grouped[$size][] = $row['plan_name'] . ' ' . formatCurrencyPlain((float)$row['price']);
        }

        $lines = [];
        foreach ($grouped as $size => $plans) {
            $lines[] = '• **' . $size . '**: ' . implode(' · ', $plans);
        }
        return implode("\n", $lines);
    }

    private function binColoursList(): string
    {
        $lines = [];
        foreach (binColors() as $name => $hex) {
            $lines[] = '• **' . ucfirst($name) . '** (' . strtoupper($hex) . ')';
        }
        return implode("\n", $lines);
    }
}
