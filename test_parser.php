<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require 'vendor/autoload.php';

// Bootstrap Laravel app so we can use the service class
$app = require_once 'bootstrap/app.php';

use App\Services\EmXlsParser;

$file = 'Enviromental_Monitoring/2025/JAN/MEDIBAG.xls';
echo "Parsing: $file\n\n";

$parser = (new EmXlsParser())->parse($file);

echo "Personnel rows: " . count($parser->personnel) . "\n";
echo "Surface rows:   " . count($parser->surface) . "\n";
echo "Errors:         " . count($parser->errors) . "\n\n";

echo "--- First 15 Personnel rows ---\n";
foreach (array_slice($parser->personnel, 0, 15) as $i => $r) {
    echo "  [$i] " . json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
}

echo "\n--- First 15 Surface rows ---\n";
foreach (array_slice($parser->surface, 0, 15) as $i => $r) {
    echo "  [$i] " . json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
}

if ($parser->errors) {
    echo "\n--- Errors ---\n";
    foreach ($parser->errors as $e) echo "  $e\n";
}
