<?php

require_once __DIR__ . '/roads_integration.php';

class RoadGeometryClient
{
    /**
     * @return array{success:bool, roads:array, error:?string}
     */
    public function fetchRoads(): array
    {
        if (URBAN_PLANNING_API_KEY === '' || URBAN_PLANNING_API_KEY === null) {
            return [
                'success' => false,
                'roads'   => [],
                'error'   => 'URBAN_PLANNING_API_KEY is not configured (see ipms-integration/.env.example)',
            ];
        }

        $ch = curl_init(rtrim(IPMS_BASE_URL, '/') . '/integrations/urban-planning/road-geometry-feed.php');

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPGET        => true,
            CURLOPT_HTTPHEADER     => ['X-API-Key: ' . URBAN_PLANNING_API_KEY],
            CURLOPT_TIMEOUT        => 30,
        ]);

        $responseBody = curl_exec($ch);
        $httpCode     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError    = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return ['success' => false, 'roads' => [], 'error' => "cURL error: $curlError"];
        }

        $decoded = json_decode($responseBody ?: '', true);

        if ($httpCode !== 200 || empty($decoded['success'])) {
            $cleanBody = trim(strip_tags($responseBody ?: ''));
            $cleanBody = preg_replace('/\s+/', ' ', $cleanBody);
            return [
                'success' => false,
                'roads'   => [],
                'error'   => "IPMS responded with HTTP $httpCode: " . mb_substr($cleanBody, 0, 200),
            ];
        }

        return ['success' => true, 'roads' => $decoded['roads'] ?? [], 'error' => null];
    }
}
