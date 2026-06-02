<?php
declare(strict_types=1);

require __DIR__ . '/config.php';

$user = require_butun_buxoro();
$data = request_data();
$id = (int)($data['id'] ?? 0);

if ($id <= 0) {
    respond(['success' => false, 'message' => 'Bemor ID kiritilmagan.'], 422);
}

$existing = fetch_patient($id);
if (!$existing) {
    respond(['success' => false, 'message' => 'Bemor topilmadi.'], 404);
}

$patient = patient_payload($data);
validate_patient_payload($patient);

$pdo = db();
$pdo->beginTransaction();

try {
    mark_source_if_needed($patient['source_patient_id']);

    $stmt = $pdo->prepare(
        'UPDATE patients SET
            full_name = :full_name,
            birth_day = :birth_day,
            birth_month = :birth_month,
            birth_year = :birth_year,
            passport_series = :passport_series,
            passport_number = :passport_number,
            pinfl = :pinfl,
            district_key = :district_key,
            district_name = :district_name,
            village = :village,
            last_visit = :last_visit,
            infection_date = :infection_date,
            status = :status,
            details = :details,
            source_patient_id = :source_patient_id,
            updated_by = :updated_by
         WHERE id = :id'
    );

    $stmt->execute(array_merge($patient, [
        'updated_by' => $user['id'],
        'id' => $id,
    ]));

    $pdo->commit();
    respond(['success' => true, 'message' => 'Bemor yangilandi.']);
} catch (Throwable $e) {
    $pdo->rollBack();
    respond(['success' => false, 'message' => 'Bemorni yangilashda xatolik: ' . $e->getMessage()], 500);
}
