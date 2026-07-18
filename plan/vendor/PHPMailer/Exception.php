<?php
/**
 * PHPMailer Exception namespace stub for standalone deployment
 */

namespace PHPMailer\PHPMailer;

class Exception extends \Exception {
    public function errorMessage(): string {
        return $this->getMessage();
    }
}
