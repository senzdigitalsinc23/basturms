<?php
require_once 'vendor/autoload.php';
use App\Services\PromotionCriteriaService;
use App\Services\ValidationService;
use App\Core\Database;

try {
    $validationService = new ValidationService();
    $service = new PromotionCriteriaService($validationService);
    
    $db = Database::getInstance()->getConnection();
    $db->exec("INSERT IGNORE INTO classes (class_id, class_name) VALUES ('TEST_CLASS', 'Test Class')");

    $service->createCriteria([
        'level_id' => 'TEST_CLASS',
        'min_score' => 50,
        'min_pass_mark' => 50,
        'min_electives' => 3
    ], 'admin');
    
    file_put_contents('error.log', "Success!\n");

} catch (Exception $e) {
    file_put_contents('error.log', "Error: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString() . "\n");
}
