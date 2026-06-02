<?php
declare(strict_types=1);

require __DIR__ . '/config.php';

$user = require_auth();
$pdo = db();

$mode = (string)($_GET['mode'] ?? 'list');

if ($mode === 'source_candidates') {
    $districtKey = trim((string)($_GET['district_key'] ?? ''));
    $village = trim((string)($_GET['village'] ?? ''));
    $excludeId = (int)($_GET['exclude_id'] ?? 0);

    if ($districtKey === '' || $village === '') {
        respond(['success' => true, 'patients' => []]);
    }

    if (!is_butun_buxoro($user) && $districtKey !== $user['district_key']) {
        respond(['success' => false, 'message' => 'Bu tuman sizga tegishli emas.'], 403);
    }

    $sql = 'SELECT * FROM patients
         WHERE district_key = :district_key
           AND village = :village';
    $params = [
        'district_key' => $districtKey,
        'village' => $village,
    ];
    if ($excludeId > 0) {
        $sql .= ' AND id <> :exclude_id';
        $params['exclude_id'] = $excludeId;
    }
    $sql .= ' ORDER BY is_source DESC, created_at DESC LIMIT 10';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    respond(['success' => true, 'patients' => $stmt->fetchAll()]);
}

[$whereAuth, $authParams] = district_where_for_user($user, 'p');

$filters = $authParams;
$where = 'WHERE 1=1' . $whereAuth;

if (!empty($_GET['district_key']) && $_GET['district_key'] !== 'all' && is_butun_buxoro($user)) {
    $where .= ' AND p.district_key = :district_key';
    $filters['district_key'] = (string)$_GET['district_key'];
}

if (!empty($_GET['passport_series'])) {
    $where .= ' AND p.passport_series LIKE :passport_series';
    $filters['passport_series'] = strtoupper((string)$_GET['passport_series']) . '%';
}

if (!empty($_GET['passport_number'])) {
    $where .= ' AND p.passport_number LIKE :passport_number';
    $filters['passport_number'] = (string)$_GET['passport_number'] . '%';
}

if (!empty($_GET['pinfl'])) {
    $where .= ' AND p.pinfl LIKE :pinfl';
    $filters['pinfl'] = (string)$_GET['pinfl'] . '%';
}

$stmt = $pdo->prepare(
    "SELECT p.*, sp.full_name AS source_full_name
     FROM patients p
     LEFT JOIN patients sp ON sp.id = p.source_patient_id
     {$where}
     ORDER BY p.created_at DESC, p.id DESC"
);
$stmt->execute($filters);
$patients = $stmt->fetchAll();

[$callWhereAuth, $callAuthParams] = district_where_for_user($user, 'c');
$callStmt = $pdo->prepare(
    "SELECT c.*
     FROM patient_calls c
     WHERE c.call_status = 'new' {$callWhereAuth}
     ORDER BY c.created_at DESC
     LIMIT 50"
);
$callStmt->execute($callAuthParams);

respond([
    'success' => true,
    'user' => $user,
    'patients' => $patients,
    'calls' => $callStmt->fetchAll(),
]);
