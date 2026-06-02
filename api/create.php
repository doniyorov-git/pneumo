<?php
declare(strict_types=1);

require __DIR__ . '/config.php';

$user = require_butun_buxoro();
$data = request_data();
$patient = patient_payload($data);

validate_patient_payload($patient);

$pdo = db();
$pdo->beginTransaction();

try {
    mark_source_if_needed($patient['source_patient_id']);

    $stmt = $pdo->prepare(
        'INSERT INTO patients (
            full_name, birth_day, birth_month, birth_year, passport_series, passport_number, pinfl, gender,
            district_key, district_name, village, last_visit, infection_date, status, details,
            source_patient_id, created_by, updated_by
        ) VALUES (
            :full_name, :birth_day, :birth_month, :birth_year, :passport_series, :passport_number, :pinfl, :gender,
            :district_key, :district_name, :village, :last_visit, :infection_date, :status, :details,
            :source_patient_id, :created_by, :updated_by
        )'
    );

    $stmt->execute(array_merge($patient, [
        'created_by' => $user['id'],
        'updated_by' => $user['id'],
    ]));

    $id = (int)$pdo->lastInsertId();
    $pdo->commit();

    respond(['success' => true, 'message' => 'Bemor yaratildi.', 'id' => $id]);
} catch (Throwable $e) {
    $pdo->rollBack();
    respond(['success' => false, 'message' => 'Bemor yaratishda xatolik: ' . $e->getMessage()], 500);
}
