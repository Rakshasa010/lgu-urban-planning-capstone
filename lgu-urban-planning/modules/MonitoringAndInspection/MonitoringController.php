<?php
require_once __DIR__ . '/../../core/Database.php';

if (!class_exists('MonitoringController')) {
    class MonitoringController {
        private $db;
        public function __construct() { $this->db = Database::getInstance(); }

        public function getAllInspections() {
            return $this->db->fetchAll("SELECT i.*, a.application_number FROM inspections i JOIN applications a ON i.application_id = a.id");
        }

        public function getApplicationsForDropdown() {
            return $this->db->fetchAll("SELECT id, application_number, project_name FROM applications WHERE status != 'completed' ORDER BY created_at DESC");
        }

        public function getStaffList() {
            return $this->db->fetchAll("SELECT id, first_name, last_name FROM users WHERE role IN ('admin', 'zoning_officer', 'building_official')");
        }

        public function getRecentViolations() {
            return $this->db->fetchAll("SELECT v.*, a.application_number FROM violations v JOIN applications a ON v.application_id = a.id WHERE v.resolved = 0 ORDER BY v.created_at DESC LIMIT 5");
        }

        public function scheduleInspection($data) {
            $date = str_replace('T', ' ', $data['scheduled_at']);
            return $this->db->query("INSERT INTO inspections (application_id, scheduled_at, inspector_id, status) VALUES (?, ?, ?, 'scheduled')", 
                [$data['application_id'], $date, $data['inspector_id']]);
        }

        public function approveOccupancy($id) {
            return $this->db->query("UPDATE applications SET status = 'completed' WHERE id = ?", [$id]);
        }
    }
}