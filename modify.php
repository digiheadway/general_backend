<?php
// modify.php
require_once 'config.php';

function respond($data, $code = 200)
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$resource = isset($_GET['resource']) ? $_GET['resource'] : '';
if (!in_array($resource, ['contacts', 'activities'])) {
    respond(["success" => false, "message" => "resource must be 'contacts' or 'activities'"], 400);
}

// read JSON body if exists
$raw = file_get_contents("php://input");
$body = [];
if ($raw) {
    $tmp = json_decode($raw, true);
    if (json_last_error() === JSON_ERROR_NONE)
        $body = $tmp;
    else
        parse_str($raw, $body);
}

// allowed fields
$allowed_contacts = [
    'is_in_pipeline',
    'name',
    'phone',
    'alter_contact',
    'address',
    'labels',
    'stage',
    'priority',
    'requirement',
    'budget',
    'about',
    'note',
    'listname',
    'source',
    'custom_fields',
    'type',
    'assignd_to',
    'admin_id',
    'email',
    'lead_scrore',
    'last_note'
];
$allowed_activities = [
    'type',
    'time',
    'reminder',
    'completed',
    'note',
    'response',
    'notified',
    'snoozed',
    'alerty',
    'reminder_type',
    'assigned_to',
    'contact_id'
];

if ($method === 'POST') {
    // insert
    $fields = ($resource === 'contacts') ? $allowed_contacts : $allowed_activities;
    $insertFields = [];
    $insertParams = [];
    foreach ($fields as $f) {
        if (isset($body[$f])) {
            $insertFields[] = "`$f`";
            $insertParams[":$f"] = $body[$f];
        }
    }
    if (empty($insertFields)) {
        respond(["success" => false, "message" => "No valid fields provided to insert"], 400);
    }
    $sql = "INSERT INTO `$resource` (" . implode(',', $insertFields) . ") VALUES (" . implode(',', array_keys($insertParams)) . ")";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($insertParams);
    $newId = $pdo->lastInsertId();
    respond(["success" => true, "message" => ucfirst($resource) . " created", "id" => (int) $newId], 201);
}

if ($method === 'PUT' || $method === 'PATCH') {
    // find id
    $id = null;
    if (isset($_GET['id']) && is_numeric($_GET['id']))
        $id = (int) $_GET['id'];
    if (!$id && isset($body['id']) && is_numeric($body['id']))
        $id = (int) $body['id'];
    if (!$id)
        respond(["success" => false, "message" => "Missing id for update (use ?id= or body.id)"], 400);

    $fields = ($resource === 'contacts') ? $allowed_contacts : $allowed_activities;
    $set = [];
    $params = [];
    foreach ($fields as $f) {
        if (isset($body[$f])) {
            $set[] = "`$f` = :$f";
            $params[":$f"] = $body[$f];
        }
    }
    if (empty($set))
        respond(["success" => false, "message" => "No updatable fields provided"], 400);

    $params[':id'] = $id;
    $sql = "UPDATE `$resource` SET " . implode(', ', $set) . ", updated_at = NOW() WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    if ($stmt->rowCount() > 0)
        respond(["success" => true, "message" => ucfirst($resource) . " updated"]);
    // check existence
    $check = $pdo->prepare("SELECT id FROM `$resource` WHERE id = :id");
    $check->execute([':id' => $id]);
    if ($check->fetch())
        respond(["success" => true, "message" => "No changes made (same data)"]);
    respond(["success" => false, "message" => ucfirst($resource) . " not found"], 404);
}

if ($method === 'DELETE') {
    $id = null;
    if (isset($_GET['id']) && is_numeric($_GET['id']))
        $id = (int) $_GET['id'];
    if (!$id && isset($body['id']) && is_numeric($body['id']))
        $id = (int) $body['id'];
    if (!$id)
        respond(["success" => false, "message" => "Missing id for delete (use ?id= or body.id)"], 400);

    $stmt = $pdo->prepare("DELETE FROM `$resource` WHERE id = :id");
    $stmt->execute([':id' => $id]);
    if ($stmt->rowCount() > 0)
        respond(["success" => true, "message" => ucfirst($resource) . " deleted"]);
    respond(["success" => false, "message" => ucfirst($resource) . " not found"], 404);
}

respond(["success" => false, "message" => "Method not allowed"], 405);
