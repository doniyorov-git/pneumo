<?php
declare(strict_types=1);

require __DIR__ . '/config.php';

$user = require_butun_buxoro();
$data = request_data();
$patientId = (int)($data['patient_id'] ?? $data['patientId'] ?? 0);

if ($patientId <= 0) {
    respond(['success' => false, 'message' => 'Bemor ID kiritilmagan.'], 422);
}

$patient = fetch_patient($patientId);
if (!$patient) {
    respond(['success' => false, 'message' => 'Bemor topilmadi.'], 404);
}

$stmt = db()->prepare(
    'INSERT INTO patient_calls (patient_id, patient_name, district_key, district_name, village, called_by, note)
     VALUES (:patient_id, :patient_name, :district_key, :district_name, :village, :called_by, :note)'
);
$stmt->execute([
    'patient_id' => $patientId,
    'patient_name' => $patient['full_name'],
    'district_key' => $patient['district_key'],
    'district_name' => $patient['district_name'],
    'village' => $patient['village'],
    'called_by' => $user['id'],
    'note' => (string)($data['note'] ?? 'Butun Buxoro tomonidan qabulga chaqirildi.'),
]);

respond(['success' => true, 'message' => 'Bemor qabulga chaqirildi.']);
