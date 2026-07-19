<?php
/**
 * Daily Journal Controller
 */

declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Journal.php';
require_once __DIR__ . '/../models/User.php';

class JournalController extends BaseController {
    private Journal $journalModel;

    public function __construct() {
        $this->journalModel = new Journal();
    }

    /**
     * Renders journal page and processes check-in updates
     */
    public function index(): void {
        $this->requireAuth();
        $userId = (int)$_SESSION['user_id'];

        $date = $_GET['date'] ?? date('Y-m-d');

        // Handle POST journal saves
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
                setFlash('error', 'Security token mismatch. Action aborted.');
                $this->redirect('/journal?date=' . $date);
            }

            $data = [
                'morning_journal' => trim($_POST['morning_journal'] ?? ''),
                'night_journal' => trim($_POST['night_journal'] ?? ''),
                'mood' => $_POST['mood'] ?? 'neutral',
                'energy_level' => (int)($_POST['energy_level'] ?? 3),
                'productivity_score' => (int)($_POST['productivity_score'] ?? 3),
                'gratitude' => trim($_POST['gratitude'] ?? ''),
                'reflection' => trim($_POST['reflection'] ?? ''),
                'learning' => trim($_POST['learning'] ?? ''),
                'problems' => trim($_POST['problems'] ?? ''),
                'achievements' => trim($_POST['achievements'] ?? '')
            ];

            try {
                $this->journalModel->save($userId, $date, $data);
                
                $userModel = new User();
                $userModel->logActivity($userId, 'JOURNAL_SAVE', "Saved journal entry for date: {$date}.", $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');

                setFlash('success', 'Journal entry saved successfully.');
            } catch (Exception $e) {
                error_log("Failed to save journal: " . $e->getMessage());
                setFlash('error', 'Failed to save journal entry.');
            }
            $this->redirect('/journal?date=' . $date);
        }

        // Fetch journal logs for target date
        $entry = $this->journalModel->findByDate($userId, $date);
        
        // Fetch last 10 entries list for history panel
        $history = $this->journalModel->getJournalHistory($userId, 10);

        $this->render('journal/index', [
            'pageTitle' => 'Daily Journal',
            'date' => $date,
            'entry' => $entry,
            'history' => $history
        ]);
    }
}
