<?php
/**
 * Config for the UPAD <-> IPMS (Roads & Traffic) integration.
 *
 * ⚠️ PLACEHOLDER VALUES — replace all of these once the IPMS team
 * issues real credentials and confirms their actual endpoint path.
 *
 * Recommended: move these into environment variables (.env) instead of
 * hardcoding, especially IPMS_API_KEY and IPMS_WEBHOOK_SECRET.
 */

// Base URL of their system. Confirm the exact API path with the IPMS team
// (e.g. it might be /api/v1/inspection-requests, /api/v2/roads/requests, etc.)
define('IPMS_API_URL', 'https://ipms.infragovservices.com');

// API key/token IPMS will issue us to authenticate our outbound requests.
define('IPMS_API_KEY', 'REPLACE_WITH_REAL_API_KEY');

// Shared secret used to verify that inbound webhook calls really came from
// IPMS (HMAC signature check). Both sides must agree on this value.
define('IPMS_WEBHOOK_SECRET', 'REPLACE_WITH_SHARED_WEBHOOK_SECRET');

// The URL we give to IPMS so they know where to POST results back to us.
define('IPMS_WEBHOOK_CALLBACK_URL', 'https://upad.infragovservices.com/api/webhooks/ipms_inspection_result.php');