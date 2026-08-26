-- Adds traffic_photos/energy_photos to impact_assessments.
--
-- ipms_inspection_result.php and uman_inspection_result.php were both
-- updated to store inspector photo URLs (JSON-encoded array, or NULL) in
-- these columns, but the migration for them was never written — every
-- IPMS/UMAN callback since has failed with:
--   SQLSTATE[42S22]: Column not found: 1054 Unknown column 'energy_photos' in 'INSERT INTO'
--   SQLSTATE[42S22]: Column not found: 1054 Unknown column 'traffic_photos' in 'INSERT INTO'
--
-- Safe to import on a database that already has these columns.

ALTER TABLE impact_assessments
    ADD COLUMN IF NOT EXISTS traffic_photos TEXT DEFAULT NULL AFTER traffic_notes,
    ADD COLUMN IF NOT EXISTS energy_photos TEXT DEFAULT NULL AFTER energy_notes;
