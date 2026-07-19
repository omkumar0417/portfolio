<?php
/**
 * Productivity Analytics Dashboard Controller
 */

declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Analytics.php';

class AnalyticsController extends BaseController {

    /**
     * Renders the master analytics view canvas
     */
    public function index(): void {
        $this->requireAuth();
        
        $this->render('analytics/index', [
            'pageTitle' => 'Analytics Workspace'
        ]);
    }
}
