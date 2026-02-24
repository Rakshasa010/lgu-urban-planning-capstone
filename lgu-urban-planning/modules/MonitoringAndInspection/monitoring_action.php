<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); 
require_once __DIR__ . '/MonitoringController.php';
require_once __DIR__ . '/../../core/Database.php'; 

$controller = new MonitoringController();
$db = Database::getInstance(); 
header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

try {
    if ($action === 'fetch_events') {
        echo json_encode($controller->getAllInspections());
    } 
    elseif ($action === 'save_schedule') {
        $result = $controller->scheduleInspection($_POST);

        if ($result) {
            $app_id = (int)$_POST['application_id'];
            $msg_subj = "OFFICIAL NOTICE: Inspection Schedule for App #" . $app_id;
            
            $date_raw = $_POST['scheduled_at'] ?? '';
            $formatted_date = $date_raw ? date('F j, Y, g:i A', strtotime($date_raw)) : 'the assigned schedule';
            
            $msg_text = "Dear Applicant,\n\n" .
                        "This is an official notification from the Building Official's Office. An onsite inspection for your application (#" . $app_id . ") has been scheduled on " . $formatted_date . ".\n\n" .
                        "Remarks: " . ($_POST['notes'] ?: 'No specific instructions provided.') . "\n\n" .
                        "Please ensure that the project site is accessible and a representative is present during the visit. Thank you.";

            $db->query(
                "INSERT INTO messages (sender_id, receiver_id, application_id, subject, message, created_at, is_read) 
                 SELECT 1, applicant_id, id, ?, ?, NOW(), 0 
                 FROM applications WHERE id = ?", 
                [$msg_subj, $msg_text, $app_id]
            );
        }

        echo json_encode(['success' => $result]);
    } 
    elseif ($action === 'delete_event') {
        $id = $_POST['id'] ?? null;


        $db->query("DELETE FROM messages WHERE application_id = (SELECT application_id FROM inspections WHERE id = ?) AND subject LIKE 'OFFICIAL NOTICE%'", [$id]);

        $success = $db->query("DELETE FROM inspections WHERE id = ?", [$id]);

        echo json_encode(['success' => $success]);
    }
    elseif ($action === 'approve_occupancy') {
        $app_id = $_POST['application_id'] ?? null;
        echo json_encode(['success' => $controller->approveOccupancy($app_id)]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}