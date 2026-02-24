<?php

// Document Controller

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Helper.php';

class DocumentController {
    private $db;
    private $auth;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->auth = new Auth();
    }
    
    public function getDocuments($applicationId) {
        $this->auth->requireLogin();
        
        $application = $this->db->fetchOne(
            "SELECT applicant_id, assigned_officer_id FROM applications WHERE id = ?",
            [$applicationId]
        );
        
        if (!$application) return [];
        
        $hasAccess = ($_SESSION['role'] === 'admin' || 
                      $_SESSION['role'] === 'zoning_officer' || 
                      $_SESSION['role'] === 'building_official' ||
                      $application['applicant_id'] == $_SESSION['user_id'] ||
                      $application['assigned_officer_id'] == $_SESSION['user_id']);
        
        if (!$hasAccess) return [];
        
        return $this->db->fetchAll(
            "SELECT ad.*, u.first_name, u.last_name 
             FROM application_documents ad
             LEFT JOIN users u ON ad.uploaded_by = u.id
             WHERE ad.application_id = ? 
             ORDER BY ad.created_at DESC",
            [$applicationId]
        );
    }
    
    public function generateReport($reportType, $filters = []) {
        $this->auth->requirePermission('generate_reports');
        $report = null;
        
        switch ($reportType) {
            case 'applications_summary':
                $report = $this->generateApplicationsSummary($filters);
                break;
            case 'zoning_compliance':
                $report = $this->generateZoningComplianceReport($filters);
                break;
            case 'inspector_performance':
                $report = $this->generateInspectorPerformance($filters);
                break;
            case 'audit_summary':
                $report = $this->generateAuditSummary($filters);
                break;
            case 'user_growth':
                $report = $this->generateUserGrowth($filters);
                break;
            case 'permits_issued':
                $report = $this->generatePermitsIssuedReport($filters);
                break;
            case 'monthly_analytics':
                $report = $this->generateMonthlyAnalytics($filters);
                break;
            default:
                return ['success' => false, 'error' => 'Invalid report type selected'];
        }
        
        if ($report && !empty($report['data'])) {
            $this->db->query(
                "INSERT INTO reports (report_type, report_name, generated_by, parameters) VALUES (?, ?, ?, ?)",
                [$reportType, $report['name'], $_SESSION['user_id'], json_encode($filters)]
            );
        }
        
        return $report;
    }

    // --- Zoning Compliance List ---
    private function generateZoningComplianceReport($filters) {
        $sql = "SELECT a.id as application_id, a.project_name, a.barangay, a.status 
                FROM applications a 
                WHERE a.status IN ('approved', 'rejected')";
        return ['name' => 'Zoning Compliance List', 'data' => $this->db->fetchAll($sql)];
    }

    // --- Inspector Performance ---
    private function generateInspectorPerformance($filters) {
        $sql = "SELECT 
                    u.first_name, 
                    u.last_name, 
                    u.role,
                    COUNT(i.Id) as total_inspections, 
                    MAX(i.scheduled_at) as last_inspection_date
                FROM users u
                INNER JOIN inspections i ON u.id = i.inspector_id
                GROUP BY u.id, u.first_name, u.last_name, u.role";
        
        $data = $this->db->fetchAll($sql);
        
        return [
            'name' => 'Inspector Performance Report', 
            'data' => $data ?: []
        ];
    }

    // --- Audit Summary ---
    private function generateAuditSummary($filters) {
        $sql = "SELECT user_id, action, entity_type, created_at 
                FROM audit_logs 
                ORDER BY created_at DESC LIMIT 50";
        
        $data = $this->db->fetchAll($sql);
        
        return [
            'name' => 'System Audit Summary', 
            'data' => $data ?: []
        ];
    }
    
    // --- User Growth ---
    private function generateUserGrowth($filters) {
        $year = $filters['year'] ?? date('Y');
        $sql = "SELECT MONTHNAME(created_at) as month, COUNT(id) as registrations 
                FROM users WHERE YEAR(created_at) = ? 
                GROUP BY MONTH(created_at)";
        return ['name' => "User Growth Report ($year)", 'data' => $this->db->fetchAll($sql, [$year])];
    }

    private function generateApplicationsSummary($filters) {
        $sql = "SELECT project_name, status, barangay, created_at FROM applications";
        return ['name' => 'Applications Summary', 'data' => $this->db->fetchAll($sql)];
    }

    private function generatePermitsIssuedReport($filters) {
        $sql = "SELECT id as permit_id, project_name, barangay, updated_at as date_issued 
                FROM applications WHERE status = 'approved'";
        return ['name' => 'Permits Issued Report', 'data' => $this->db->fetchAll($sql)];
    }

    private function generateMonthlyAnalytics($filters) {
        $year = $filters['year'] ?? date('Y');
        $sql = "SELECT MONTHNAME(created_at) as month, COUNT(*) as total_count 
                FROM applications WHERE YEAR(created_at) = ? 
                GROUP BY MONTH(created_at)";
        return ['name' => "Monthly Analytics ($year)", 'data' => $this->db->fetchAll($sql, [$year])];
    }

    // Download Document
    public function downloadDocument($documentId) {
        $this->auth->requireLogin();

        $document = $this->db->fetchOne(
            "SELECT ad.*, a.applicant_id, a.assigned_officer_id 
             FROM application_documents ad
             JOIN applications a ON ad.application_id = a.id
             WHERE ad.id = ?",
            [$documentId]
        );

        if (!$document) {
            die("Error: Document record not found.");
        }

        // Security Check
        $hasAccess = ($_SESSION['role'] === 'admin' || 
                      $_SESSION['role'] === 'zoning_officer' || 
                      $_SESSION['role'] === 'building_official' ||
                      $document['applicant_id'] == $_SESSION['user_id'] ||
                      $document['assigned_officer_id'] == $_SESSION['user_id']);

        if (!$hasAccess) {
            die("Error: Unauthorized access.");
        }

        // --- DYNAMIC PATH REPAIR ---
        $basePath = realpath(__DIR__ . '/../../');
        

        $fileNameOnly = basename($document['file_path']); 
        $filePath = $basePath . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'documents' . DIRECTORY_SEPARATOR . $fileNameOnly;

    if (file_exists($filePath)) {
            if (ob_get_level()) ob_end_clean();

            $isView = isset($_GET['view']) && $_GET['view'] == 1;
            $disposition = $isView ? 'inline' : 'attachment';
            
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $filePath);
            finfo_close($finfo);

            header('Content-Description: File Transfer');
            header('Content-Type: ' . $mimeType); 
            header('Content-Disposition: ' . $disposition . '; filename="' . basename($document['file_name']) . '"');
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));
            
            readfile($filePath);
            exit;
        } else {
            die("Error: File not found on server.");
        }
    }
} 