<?php
// fetch.php
require_once 'config.php';

function respond($data, $code = 200)
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respond(["success" => false, "message" => "Only GET allowed"], 405);
}

$resource = isset($_GET['resource']) ? $_GET['resource'] : '';
if (!in_array($resource, ['contacts', 'activities'])) {
    respond(["success" => false, "message" => "resource must be 'contacts' or 'activities'"], 400);
}

// common pagination
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$per_page = isset($_GET['per_page']) ? min(500, max(1, (int) $_GET['per_page'])) : 25;
$offset = ($page - 1) * $per_page;

// single fetch
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int) $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM `$resource` WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    if ($row)
        respond(["success" => true, "data" => $row]);
    respond(["success" => false, "message" => ucfirst($resource) . " not found"], 404);
}

// build list query
$where = ["1=1"];
$params = [];

// resource-specific filters
if ($resource === 'contacts') {
    $allowed = ['is_in_pipeline', 'stage', 'priority', 'listname', 'assignd_to', 'admin_id', 'source', 'type'];
    foreach ($allowed as $f) {
        if (isset($_GET[$f]) && $_GET[$f] !== '') {
            $where[] = "`$f` = :$f";
            $params[":$f"] = $_GET[$f];
        }
    }
    if (!empty($_GET['min_budget']) && is_numeric($_GET['min_budget'])) {
        $where[] = "budget >= :min_budget";
        $params[':min_budget'] = (int) $_GET['min_budget'];
    }
    if (!empty($_GET['max_budget']) && is_numeric($_GET['max_budget'])) {
        $where[] = "budget <= :max_budget";
        $params[':max_budget'] = (int) $_GET['max_budget'];
    }
    // created range
    if (!empty($_GET['created_from'])) {
        $where[] = "created_at >= :created_from";
        $params[':created_from'] = $_GET['created_from'] . " 00:00:00";
    }
    if (!empty($_GET['created_to'])) {
        $where[] = "created_at <= :created_to";
        $params[':created_to'] = $_GET['created_to'] . " 23:59:59";
    }
} else {
    // activities filters
    $allowedA = ['type', 'reminder', 'completed', 'notified', 'assigned_to'];
    foreach ($allowedA as $f) {
        if (isset($_GET[$f]) && $_GET[$f] !== '') {
            $where[] = "`$f` = :$f";
            $params[":$f"] = $_GET[$f];
        }
    }
    if (!empty($_GET['contact_id']) && is_numeric($_GET['contact_id'])) {
        $where[] = "contact_id = :contact_id";
        $params[':contact_id'] = (int) $_GET['contact_id'];
    }
    if (!empty($_GET['time_from'])) {
        $where[] = "time >= :time_from";
        $params[':time_from'] = $_GET['time_from'];
    }
    if (!empty($_GET['time_to'])) {
        $where[] = "time <= :time_to";
        $params[':time_to'] = $_GET['time_to'];
    }
}

// search across common text fields
if (!empty($_GET['q'])) {
    $q = '%' . trim($_GET['q']) . '%';
    if ($resource === 'contacts') {
        $where[] = "(name LIKE :q OR phone LIKE :q OR email LIKE :q OR listname LIKE :q OR labels LIKE :q OR about LIKE :q OR note LIKE :q)";
    } else {
        $where[] = "(note LIKE :q OR response LIKE :q)";
    }
    $params[':q'] = $q;
}

// sort
$allowed_sort_contacts = ['id', 'created_at', 'updated_at', 'name', 'budget', 'lead_scrore'];
$allowed_sort_activities = ['id', 'time', 'created_at', 'updated_at', 'completed'];
$sort_by = 'id';
if ($resource === 'contacts') {
    $sort_by = (isset($_GET['sort_by']) && in_array($_GET['sort_by'], $allowed_sort_contacts)) ? $_GET['sort_by'] : 'id';
} else {
    $sort_by = (isset($_GET['sort_by']) && in_array($_GET['sort_by'], $allowed_sort_activities)) ? $_GET['sort_by'] : 'time';
}
$sort_dir = (isset($_GET['sort_dir']) && strtolower($_GET['sort_dir']) === 'asc') ? 'ASC' : 'DESC';

// count total
$sqlWhere = implode(' AND ', $where);
$stmtCount = $pdo->prepare("SELECT COUNT(*) as cnt FROM `$resource` WHERE $sqlWhere");
$stmtCount->execute($params);
$total = (int) $stmtCount->fetchColumn();

// fetch rows
$sql = "SELECT * FROM `$resource` WHERE $sqlWhere ORDER BY `$sort_by` $sort_dir LIMIT :lim OFFSET :off";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v)
    $stmt->bindValue($k, $v);
$stmt->bindValue(':lim', (int) $per_page, PDO::PARAM_INT);
$stmt->bindValue(':off', (int) $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

respond([
    "success" => true,
    "meta" => [
        "resource" => $resource,
        "page" => $page,
        "per_page" => $per_page,
        "total" => $total,
        "total_pages" => ($per_page > 0) ? ceil($total / $per_page) : 0
    ],
    "data" => $rows
]);
