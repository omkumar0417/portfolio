<?php
/**
 * Test script to display the exact absolute server path of the application.
 */

declare(strict_types=1);

echo "Absolute path: " . __FILE__ . "\n";
echo "Document root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
