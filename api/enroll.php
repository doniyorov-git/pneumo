<?php
declare(strict_types=1);

require __DIR__ . '/config.php';

$user = require_butun_buxoro();
$data = request_data();
$sourcePatientId = (int)($data['source_patient_id'] ?? $data['sourcePatientId'] ?? 0);
$targetPatientId = (int)($data['target_patient_id'] ?? $data['targetPatientId'] ?? 0);

if ($sourcePatientId <= 0) {
    respond(['success' => false, 'message' => 'Manba bemor ID kiritilmagan.'], 422);
}

$source = fetch_patient($sourcePatientId);
if (!$source) {
    respond(['success' => false, 'message' => 'Manba bemor topilmadi.'], 404);
}

$pdo = db();
$pdo->beginTransaction();

try {
    $stmt = $pdo->prepare('UPDATE patients SET is_source = 1, updated_by = :updated_by WHERE id = :id');
    $stmt->execute(['updated_by' => $user['id'], 'id' => $sourcePatientId]);

    if ($targetPatientId > 0) {
        $target = fetch_patient($targetPatientId);
        if (!$target) {
            throw new RuntimeException('Bog‘lanadigan bemor topilmadi.');
        }

        $linkStmt = $pdo->prepare('UPDATE patients SET source_patient_id = :source_id, updated_by = :updated_by WHERE id = :target_id');
        $linkStmt->execute([
            'source_id' => $sourcePatientId,
            'updated_by' => $user['id'],
            'target_id' => $targetPatientId,
        ]);
    }

    $pdo->commit();
    respond(['success' => true, 'message' => 'Bemor manba sifatida belgilandi.']);
} catch (Throwable $e) {
    $pdo->rollBack();
    respond(['success' => false, 'message' => 'Manba belgilashda xatolik: ' . $e->getMessage()], 500);
}
