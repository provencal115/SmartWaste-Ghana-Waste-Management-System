<?php

/**
 * Offline keyword-matching chatbot engine (no external AI API).
 */
class ChatbotEngine
{
    private array $knowledge = [];

    public function __construct()
    {
        try {
            $this->knowledge = ChatbotModel::allKnowledge(true);
        } catch (Throwable $e) {
            $this->knowledge = [];
        }
    }

    public function welcomeMessage(): string
    {
        $name = 'SmartWaste Virtual Assistant';
        return "Hello! I'm **{$name}** — your SmartWaste guide. "
            . "Ask me about registration, pickups, pricing, bin sizes, payments, or contact support. "
            . "How can I help you today?";
    }

    /** @return list<array{label: string, query: string}> */
    public function suggestions(): array
    {
        $defaults = [
            ['label' => 'Register', 'query' => 'How do I register?'],
            ['label' => 'Schedule Pickup', 'query' => 'How do I schedule a pickup?'],
            ['label' => 'Bin Sizes', 'query' => 'What bin sizes are available?'],
            ['label' => 'Pricing', 'query' => 'What are your pricing plans?'],
            ['label' => 'Mobile Money', 'query' => 'Do you accept Mobile Money?'],
            ['label' => 'Contact Us', 'query' => 'How can I contact support?'],
            ['label' => 'Collection Days', 'query' => 'When is my collection day?'],
            ['label' => 'Complaints', 'query' => 'How do I report a complaint?'],
            ['label' => 'Payment Help', 'query' => 'Where can I see my payment history?'],
        ];

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
            return $out ?: $defaults;
        } catch (Throwable $e) {
            return $defaults;
        }
    }

    /**
     * @return array{response: string, knowledge_id: ?int, category: ?string, matched: bool}
     */
    public function reply(string $message): array
    {
        $text = strtolower(trim($message));
        if ($text === '') {
            return $this->fallback();
        }

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
                'response' => $this->renderResponse((string)$best['response']),
                'knowledge_id' => (int)$best['id'],
                'category' => (string)$best['category'],
                'matched' => true,
            ];
        }

        return $this->fallback();
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

        $title = strtolower((string)$entry['title']);
        if ($title !== '' && str_contains($text, $title)) {
            $score += 4;
        }

        return $score;
    }

    private function keywordInText(string $text, string $keyword): bool
    {
        if (strlen($keyword) <= 3) {
            return (bool)preg_match('/\b' . preg_quote($keyword, '/') . '\b/u', $text);
        }

        return str_contains($text, $keyword);
    }

    /** @return array{response: string, knowledge_id: ?int, category: ?string, matched: bool} */
    private function fallback(): array
    {
        foreach ($this->knowledge as $entry) {
            if (($entry['category'] ?? '') === 'fallback') {
                return [
                    'response' => $this->renderResponse((string)$entry['response']),
                    'knowledge_id' => (int)$entry['id'],
                    'category' => 'fallback',
                    'matched' => false,
                ];
            }
        }

        return [
            'response' => $this->renderResponse(
                "I'm sorry, I couldn't understand your request.\n\nTry asking about:\n"
                . "• Registration\n• Schedule Pickup\n• Payments\n• Bin Sizes\n• Contact Support"
            ),
            'knowledge_id' => null,
            'category' => 'fallback',
            'matched' => false,
        ];
    }

    private function renderResponse(string $template): string
    {
        $info = companyInfo();
        $replacements = [
            '{register_url}' => baseUrl('auth/register'),
            '{login_url}' => baseUrl('auth/login'),
            '{forgot_url}' => baseUrl('auth/forgot'),
            '{contact_url}' => baseUrl('contact'),
            '{phone}' => $info['phone'] ?? '',
            '{phone_alt}' => $info['phone_alt'] ?? '',
            '{email}' => $info['email'] ?? '',
            '{emergency}' => $info['emergency'] ?? '',
            '{address}' => $info['address'] ?? '',
            '{hours}' => $info['hours'] ?? '',
            '{pricing_table}' => $this->pricingTable(),
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
            return "• Small (120L): Weekly GH₵15 · Bi-weekly GH₵28 · Monthly GH₵50\n"
                . "• Medium (240L): Weekly GH₵25 · Bi-weekly GH₵48 · Monthly GH₵90\n"
                . "• Large (360L): Weekly GH₵40 · Bi-weekly GH₵75 · Monthly GH₵140";
        }

        $grouped = [];
        foreach ($rows as $row) {
            $size = ucfirst((string)$row['bin_size']) . ' (' . binCapacity($row['bin_size']) . 'L)';
            $grouped[$size][] = $row['plan_name'] . ' ' . formatCurrency((float)$row['price']);
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
