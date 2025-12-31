<?php
require_once 'db.php';

function getFloors($facility_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM floors WHERE facility_id = ? ORDER BY level_number ASC");
    $stmt->execute([$facility_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getFloor($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM floors WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function createFloor($facility_id, $name, $level_number) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO floors (facility_id, name, level_number) VALUES (?, ?, ?)");
    $stmt->execute([$facility_id, $name, $level_number]);
    return $pdo->lastInsertId();
}

function updateFloor($id, $name, $level_number) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE floors SET name = ?, level_number = ? WHERE id = ?");
    $stmt->execute([$name, $level_number, $id]);
}

function deleteFloor($id) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM floors WHERE id = ?");
    $stmt->execute([$id]);
}

function getRooms($floor_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE floor_id = ? ORDER BY name ASC");
    $stmt->execute([$floor_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function createRoom($floor_id, $name, $type, $capacity, $area) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO rooms (floor_id, name, type, capacity, area) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$floor_id, $name, $type, $capacity, $area]);
    return $pdo->lastInsertId();
}

function getRoom($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function updateRoom($id, $name, $type, $capacity, $area) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE rooms SET name = ?, type = ?, capacity = ?, area = ? WHERE id = ?");
    $stmt->execute([$name, $type, $capacity, $area, $id]);
}

function deleteRoom($id) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
    $stmt->execute([$id]);
}
