<?php
/**
 * Response Handler
 * 
 * Standardizes all API responses to JSON format.
 * All endpoints must use these functions to return responses.
 */

/**
 * Send success response
 * 
 * @param mixed $data Response data (array or object)
 * @param string $message Success message
 * @param int $httpCode HTTP status code
 */
function successResponse($data = null, $message = 'Success', $httpCode = 200) {
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    
    echo json_encode(array(
        'success' => true,
        'message' => $message,
        'data' => $data
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    exit;
}

/**
 * Send error response
 * 
 * @param string $message Error message
 * @param mixed $errors Additional error details (optional)
 * @param int $httpCode HTTP status code
 */
function errorResponse($message = 'Error', $errors = null, $httpCode = 400) {
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    
    $response = array(
        'success' => false,
        'message' => $message
    );
    
    if ($errors !== null) {
        $response['errors'] = $errors;
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    exit;
}

/**
 * Send validation error response
 * 
 * @param array $fieldErrors Field-specific validation errors
 * @param string $message General error message
 */
function validationErrorResponse($fieldErrors, $message = 'Validation failed') {
    http_response_code(422);
    header('Content-Type: application/json; charset=utf-8');
    
    echo json_encode(array(
        'success' => false,
        'message' => $message,
        'errors' => $fieldErrors
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    exit;
}

/**
 * Send not found error response
 * 
 * @param string $message Error message
 */
function notFoundResponse($message = 'Resource not found') {
    errorResponse($message, null, 404);
}

/**
 * Send server error response
 * 
 * @param string $message Error message
 */
function serverErrorResponse($message = 'Internal server error') {
    errorResponse($message, null, 500);
}

/**
 * Handle uncaught exceptions
 * 
 * @param Exception $e Exception object
 */
function handleException($e) {
    if (DEBUG_MODE) {
        errorResponse(
            $e->getMessage(),
            array('file' => $e->getFile(), 'line' => $e->getLine()),
            500
        );
    } else {
        serverErrorResponse();
    }
}

// Set error handler
set_exception_handler('handleException');
