<?php
/**
 * Config for the UPAD <-> UMAN (Energy & Utilities) integration.
 *
 * Real values come from environment variables — see .env.example in this
 * folder. Copy it to `.env` (untracked, gitignored) and override values
 * there for a different environment; nothing secret belongs in this file.
 *
 * The defaults below already match UMAN's own local defaults (see
 * uman_/api/integration_config.php on the UMAN side), so this integration
 * works out of the box on a local XAMPP setup with both projects under
 * htdocs. Override UMAN_API_URL/UMAN_WEBHOOK_CALLBACK_URL once either side
 * is deployed somewhere other than localhost.
 */

require_once __DIR__ . '/env.php';

// Base URL of the UMAN system. Local XAMPP default assumes both projects
// live side by side under htdocs (…/htdocs/uman_). Override via .env once
// UMAN is deployed somewhere else reachable from this server.
define('UMAN_API_URL', uman_env('UMAN_API_URL', 'http://localhost/uman_'));

// API key/token UMAN expects on inbound requests (Authorization: Bearer).
// Must match UPAD_API_KEY in UMAN's api/integration_config.php.
define('UMAN_API_KEY', uman_env('UMAN_API_KEY', 'UPAD_UMAN_INTEGRATION_KEY_2026'));

// Shared secret used to verify that inbound webhook calls really came from
// UMAN (HMAC signature check). Must match UPAD_WEBHOOK_SECRET in UMAN's
// api/integration_config.php.
define('UMAN_WEBHOOK_SECRET', uman_env('UMAN_WEBHOOK_SECRET', 'UPAD_UMAN_WEBHOOK_SECRET_2026'));

// The URL we give UMAN so it knows where to POST results back to us. Local
// XAMPP default points at this project's own webhook receiver.
define('UMAN_WEBHOOK_CALLBACK_URL', uman_env(
    'UMAN_WEBHOOK_CALLBACK_URL',
    'http://localhost/lgu-urban-planning-capstone/lgu-urban-planning/uman-integration/uman_inspection_result.php'
));
