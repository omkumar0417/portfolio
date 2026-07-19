<?php
/**
 * AJAX Analytics Calculations Datasets API
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../models/Analytics.php';

// Access protection
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthenticated access.']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$analyticsModel = new Analytics();

try {
    $daysRange = (int)($_GET['days'] ?? 30);
    if (!in_array($daysRange, [7, 30, 90, 365])) {
        $daysRange = 30;
    }

    // Aggregate calculated stats
    $scores = $analyticsModel->getProductivityScore($userId, $daysRange);
    $categories = $analyticsModel->getCategoryDistribution($userId);
    $priorities = $analyticsModel->getPriorityDistribution($userId);
    $deadlines = $analyticsModel->getDeadlinePerformance($userId);
    $rollingAverages = $analyticsModel->getRollingAverages($userId, $daysRange);
    $heatmap = $analyticsModel->getGithubStyleHeatmapData($userId);
    $efficiency = $analyticsModel->getTaskEfficiency($userId);
    $insights = $analyticsModel->getAnalyticsInsights($userId);
    $focusTrends = $analyticsModel->getFocusTimeTrends($userId, $daysRange === 30 ? 30 : ($daysRange === 7 ? 7 : 14));

    echo json_encode([
        'success' => true,
        'scores' => $scores,
        'categories' => $categories,
        'priorities' => $priorities,
        'deadlines' => $deadlines,
        'rolling_averages' => $rollingAverages,
        'heatmap' => $heatmap,
        'efficiency' => $efficiency,
        'insights' => $insights,
        'focus_trends' => $focusTrends
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
exit;
