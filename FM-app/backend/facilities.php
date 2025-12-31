<?php
require_once 'db.php';

function getFacilities($search = '', $type = '', $status = '') {
    global $pdo;
    $sql = "SELECT * FROM facilities WHERE 1=1";
    $params = [];

    if (!empty($search)) {
        $sql .= " AND (name LIKE :search OR manager_name LIKE :search OR address LIKE :search)";
        $params[':search'] = "%$search%";
    }

    if (!empty($type)) {
        $sql .= " AND type = :type";
        $params[':type'] = $type;
    }

    if (!empty($status)) {
        $sql .= " AND status = :status";
        $params[':status'] = $status;
    }

    $sql .= " ORDER BY created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getFacility($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM facilities WHERE id = :id");
    $stmt->execute([':id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function createFacility($data) {
    global $pdo;
    $sql = "INSERT INTO facilities (
        name, type, address, area, construction_year, status, 
        employees, op_hours, hazard_level, manager_name, manager_contact, description
    ) VALUES (
        :name, :type, :address, :area, :construction_year, :status, 
        :employees, :op_hours, :hazard_level, :manager_name, :manager_contact, :description
    )";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':name' => $data['name'],
        ':type' => $data['type'],
        ':address' => $data['address'],
        ':area' => $data['area'],
        ':construction_year' => $data['construction_year'],
        ':status' => $data['status'],
        ':employees' => $data['employees'],
        ':op_hours' => $data['op_hours'],
        ':hazard_level' => $data['hazard_level'],
        ':manager_name' => $data['manager_name'],
        ':manager_contact' => $data['manager_contact'],
        ':description' => $data['description']
    ]);
}

function updateFacility($id, $data) {
    global $pdo;
    $sql = "UPDATE facilities SET 
        name = :name, 
        type = :type, 
        address = :address, 
        area = :area, 
        construction_year = :construction_year, 
        status = :status, 
        employees = :employees, 
        op_hours = :op_hours, 
        hazard_level = :hazard_level, 
        manager_name = :manager_name, 
        manager_contact = :manager_contact, 
        description = :description
        WHERE id = :id";
    
    $stmt = $pdo->prepare($sql);
    $params = [
        ':id' => $id,
        ':name' => $data['name'],
        ':type' => $data['type'],
        ':address' => $data['address'],
        ':area' => $data['area'],
        ':construction_year' => $data['construction_year'],
        ':status' => $data['status'],
        ':employees' => $data['employees'],
        ':op_hours' => $data['op_hours'],
        ':hazard_level' => $data['hazard_level'],
        ':manager_name' => $data['manager_name'],
        ':manager_contact' => $data['manager_contact'],
        ':description' => $data['description']
    ];
    
    $stmt->execute($params);
}
