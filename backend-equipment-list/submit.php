<?php
/**
 * Equipment List Backend
 * Handles fetching and filtering equipment data
 * Returns JSON response
 */

header('Content-Type: application/json');

try {
  // Get filter parameters from request
  $dc = isset($_GET['dc']) ? trim($_GET['dc']) : '';
  $type = isset($_GET['type']) ? trim($_GET['type']) : '';
  
  // Read equipment database
  $dbPath = __DIR__ . '/../backend-database/dcfm-equipment-db.json';
  if (!file_exists($dbPath)) {
    throw new Exception('Database file not found');
  }
  
  $jsonData = file_get_contents($dbPath);
  $equipment = json_decode($jsonData, true);
  
  if (!is_array($equipment)) {
    throw new Exception('Invalid database format');
  }
  
  // Apply filters
  $filtered = array_filter($equipment, function($item) use ($dc, $type) {
    if ($dc && (isset($item['dc']) && $item['dc'] !== $dc)) {
      return false;
    }
    if ($type && (isset($item['type']) && $item['type'] !== $type)) {
      return false;
    }
    return true;
  });
  
  // Return success response
  http_response_code(200);
  echo json_encode([
    'status' => 'success',
    'data' => array_values($filtered),
    'count' => count($filtered)
  ]);
  
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode([
    'status' => 'error',
    'message' => $e->getMessage()
  ]);
}
?>
