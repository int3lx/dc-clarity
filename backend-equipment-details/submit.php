<?php
/**
 * Equipment Details Backend
 * Handles fetching equipment details and history by equipment ID
 * Returns JSON response
 */

header('Content-Type: application/json');

try {
  // Get equipment ID from request
  $id = isset($_GET['id']) ? trim($_GET['id']) : '';
  
  if (empty($id)) {
    throw new Exception('Equipment ID is required');
  }
  
  // Read equipment database
  $dbPath = __DIR__ . '/../backend-database/dcfm-equipment-db.json';
  if (!file_exists($dbPath)) {
    throw new Exception('Equipment database file not found');
  }
  
  $jsonData = file_get_contents($dbPath);
  $equipment = json_decode($jsonData, true);
  
  if (!is_array($equipment)) {
    throw new Exception('Invalid equipment database format');
  }
  
  // Find equipment by ID
  $item = null;
  foreach ($equipment as $eq) {
    if (isset($eq['id']) && $eq['id'] === $id) {
      $item = $eq;
      break;
    }
  }
  
  if (!$item) {
    throw new Exception('Equipment not found');
  }
  
  // Read equipment history database
  $histPath = __DIR__ . '/../backend-database/dcfm-equipment-history-db.json';
  $history = [];
  
  if (file_exists($histPath)) {
    $histData = file_get_contents($histPath);
    $histArray = json_decode($histData, true);
    
    if (is_array($histArray)) {
      // Filter history by equipment ID
      $history = array_filter($histArray, function($h) use ($id) {
        return isset($h['id']) && $h['id'] === $id;
      });
      $history = array_values($history);
    }
  }
  
  // Return success response
  http_response_code(200);
  echo json_encode([
    'status' => 'success',
    'equipment' => $item,
    'history' => $history,
    'history_count' => count($history)
  ]);
  
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode([
    'status' => 'error',
    'message' => $e->getMessage()
  ]);
}
?>
