<?php
/**
 * Optional external AI provider configuration.
 * Copy this file to config/ai.php and set your API key, or use environment variables.
 *
 * NEVER commit config/ai.php with real API keys.
 */
return [
    // Set to true to enable OpenAI-compatible API (falls back to keyword FAQ when false)
    'enabled'  => false,

    'provider' => 'openai',
    'model'    => 'gpt-4o-mini',
    'base_url' => 'https://api.openai.com/v1',

    // Prefer environment variable OPENAI_API_KEY or SMARTWASTE_AI_API_KEY instead
    'api_key'  => '',
];
