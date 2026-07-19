<?php
/**
 * MVC Base Model
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

abstract class BaseModel {
    protected PDO $db;

    public function __construct() {
        $this->db = DB::getConnection();
    }

    /**
     * Common SQL queries can be added here as wrapper methods if needed.
     */
}
