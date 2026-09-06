<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config/database.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$spreadsheet = IOFactory::load(__DIR__ . '/templates/template_rekom.xlsx');
$htmlWriter = new \PhpOffice\PhpSpreadsheet\Writer\Html($spreadsheet);
$html = $htmlWriter->generateHTMLAll();

echo 'len=' . strlen($html) . PHP_EOL;
echo 'table=' . (strpos($html, '<table') !== false ? 'Y' : 'N') . PHP_EOL;
echo 'img=' . (strpos($html, '<img') !== false ? 'Y' : 'N') . PHP_EOL;
echo substr($html, 0, 5000);
