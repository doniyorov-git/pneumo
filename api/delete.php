<?php
declare(strict_types=1);

require __DIR__ . '/config.php';

require_butun_buxoro();
$data = request_data();
$id = (int)($data['id'] ?? 0);

if ($id <= 0) {
    respond(['success' => false, 'message' => 'Bemor ID kiritilmagan.'], 422);
}

$stmt = db()->prepare('DELETE FROM patients WHERE id = :id');
$stmt->execute(['id' => $id]);

respond(['success' => true, 'message' => 'Bemor o‘chirildi.']);
