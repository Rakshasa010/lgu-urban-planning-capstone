<?php
/**
 * RoadsIntegrationService
 *
 * Handles the OUTBOUND side of the integration: sending a road inspection
 * request to IPMS when staff clicks "Request Road Inspection".
 *
 * This replaces the old dummy simulation that used to write fake
 * "AUTOMATED SIMULATION" text straight into impact_assessments.
 * Now it only marks the request as 'pending' — the real traffic_flag/
 * traffic_notes only get filled in later, by the webhook, once IPMS
 * actually responds.
 */

require_once __DIR__ . '/roads_integration.php';
require_once __DIR__ . '/../core/Database.php';

class RoadsIntegrationService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * @param int   $applicationId
     * @param array $applicationData  Confirmed IPMS fields: road_name, barangay,
     *                                 district, category, length_km, priority,
     *                                 address, lat, lng, description, requested_by.
     * @param int   $requestedBy       user_id of the staff member triggering this
     * @return array{request_id:int, sent:bool, external_ref_id:?string, error:?string}
     */
    public function requestInspection(int $applicationId, array $applicationData, int $requestedBy): array
    {
        // Field names below match IPMS's confirmed request form:
        // Road Name, Barangay, District, Category (=road type), Length,
        // Priority, Map Location/address, Description, Requested By.
        // We do NOT send an engineer — that's filled in on their side later.
        $payload = [
            'source_system'   => 'UPAD',
            'application_id'  => $applicationId,   // our correlation id — must be echoed back in their webhook
            'road_name'       => $applicationData['road_name']  ?? $applicationData['address'] ?? null,
            'barangay'        => $applicationData['barangay']   ?? null,
            'district'        => $applicationData['district']  ?? null,
            'category'        => $applicationData['category']  ?? null,  // e.g. Collector Road, Barangay Road, Arterial Road
            'length_km'       => $applicationData['length_km'] ?? null,
            'priority'        => $applicationData['priority']  ?? 'Medium', // Urgent | Medium | Low
            'map_location'    => [
                'address'   => $applicationData['address'] ?? null,
                'latitude'  => $applicationData['lat']      ?? null,
                'longitude' => $applicationData['lng']      ?? null,
            ],
            'description'     => $applicationData['description'] ?? null, // reason/context for the request
            'requested_by'    => $applicationData['requested_by'] ?? 'Urban Planning Office',
            'callback_url'    => IPMS_WEBHOOK_CALLBACK_URL,
            'requested_at'    => date('c'),
        ];

        // 1. Save the request as 'pending' FIRST, so we never lose track of
        //    it even if IPMS's API is down or unreachable.
        $stmt = $this->db->prepare(
            "INSERT INTO road_inspection_requests
                (application_id, status, request_payload, requested_by, requested_at)
             VALUES (?, 'pending', ?, ?, NOW())"
        );
        $stmt->execute([$applicationId, json_encode($payload), $requestedBy]);
        $requestId = (int) $this->db->lastInsertId();

        // 2. Mirror a 'pending' state into impact_assessments so the
        //    Technical Assessment tab shows "awaiting result" instead of
        //    blank or fake data.
        $this->db->prepare(
            "INSERT INTO impact_assessments (application_id, traffic_flag, traffic_notes, checked_at)
             VALUES (?, 'pending', 'Inspection request sent to IPMS (Infrastructure Project Management System). Awaiting result.', NOW())
             ON DUPLICATE KEY UPDATE
                traffic_flag  = 'pending',
                traffic_notes = 'Inspection request sent to IPMS (Infrastructure Project Management System). Awaiting result.',
                checked_at    = NOW()"
        )->execute([$applicationId]);

        // 3. Call out to IPMS. This part is a placeholder until you have
        //    their real endpoint path and auth method.
        [$sent, $externalRefId, $error] = $this->sendToIpms($requestId, $payload);

        $this->db->prepare(
            "UPDATE road_inspection_requests
                SET status = ?, external_ref_id = ?, response_payload = ?
              WHERE id = ?"
        )->execute([
            $sent ? 'sent' : 'failed',
            $externalRefId,
            $error ? json_encode(['error' => $error]) : null,
            $requestId,
        ]);

        return [
            'request_id'      => $requestId,
            'sent'            => $sent,
            'external_ref_id' => $externalRefId,
            'error'           => $error,
        ];
    }

    /**
     * ⚠️ PLACEHOLDER — swap in the real endpoint path, auth header format,
     * and response field names once IPMS shares their API contract.
     *
     * @return array{0: bool, 1: ?string, 2: ?string}  [sent, externalRefId, error]
     */
    private function sendToIpms(int $requestId, array $payload): array
    {

        $ch = curl_init(rtrim(IPMS_API_URL, '/') . '/api/v1/inspection-requests');

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . IPMS_API_KEY,
                'X-UPAD-Request-Id: ' . $requestId,
            ],
            CURLOPT_TIMEOUT        => 10,
        ]);

        $responseBody = curl_exec($ch);
        $httpCode     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError    = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return [false, null, "cURL error: $curlError"];
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            $decoded       = json_decode($responseBody, true);
            $externalRefId = $decoded['reference_id'] ?? $decoded['id'] ?? null;
            return [true, $externalRefId, null];
        }


        $cleanBody = trim(strip_tags($responseBody ?: ''));
        $cleanBody = preg_replace('/\s+/', ' ', $cleanBody);
        $cleanBody = mb_substr($cleanBody, 0, 200);

        return [false, null, "IPMS responded with HTTP $httpCode: $cleanBody"];
    }
}