<?php
/**
 * Config for the UPAD <-> UMAN (Energy & Utilities) integration.
 *
 * ⚠️ PLACEHOLDER VALUES — replace all of these once the UMAN team issues
 * real credentials and confirms their actual endpoint path. UMAN is the
 * Energy/Utilities counterpart to Roads' IPMS.
 *
 * Recommended: move these into environment variables (.env) instead of
 * hardcoding, especially UMAN_API_KEY and UMAN_WEBHOOK_SECRET.
 */

// Base URL of their system. Confirm the exact API path with the
// Energy/Utilities team (e.g. it might be /api/v1/inspection-requests,
// /api/v2/grid/requests, etc.)
define('UMAN_API_URL', 'https://uman.infragovservices.com');

// API key/token the Energy/Utilities team will issue us to authenticate
// our outbound requests.
define('UMAN_API_KEY', 'REPLACE_WITH_REAL_API_KEY');

// Shared secret used to verify that inbound webhook calls really came from
// UMAN (HMAC signature check). Both sides must agree on this value.
define('UMAN_WEBHOOK_SECRET', 'REPLACE_WITH_SHARED_WEBHOOK_SECRET');

// The URL we give to the Energy/Utilities team so they know where to POST
// results back to us.
define('UMAN_WEBHOOK_CALLBACK_URL', 'https://upad.infragovservices.com/api/webhooks/uman_inspection_result.php');