<?php
/**
 * ============================================================
 *  RateLimiter — IP-based login flood protection
 *  Location: /includes/RateLimiter.php
 * ============================================================
 *  Usage:
 *      require_once 'RateLimiter.php';
 *      $limiter = new RateLimiter();
 *      if ($limiter->isBlocked()) { die('Too many attempts.'); }
 *      // ... attempt login ...
 *      if ($login_failed) { $limiter->recordFailure(); }
 *      else { $limiter->reset(); }
 * ============================================================
 */
class RateLimiter {
    private int $maxAttempts;
    private int $decaySeconds;
    private string $sessionKey;

    public function __construct(int $maxAttempts = 5, int $decaySeconds = 900) {
        $this->maxAttempts  = $maxAttempts;
        $this->decaySeconds = $decaySeconds;
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $this->sessionKey = 'rl_' . md5($ip);

        if (session_status() === PHP_SESSION_NONE) {
            include_once __DIR__ . '/session_setup.php';
        }
    }

    public function isBlocked(): bool {
        $data = $_SESSION[$this->sessionKey] ?? null;
        if (!$data) return false;

        // Reset if window has expired
        if (time() - $data['first_attempt'] > $this->decaySeconds) {
            $this->reset();
            return false;
        }

        return $data['attempts'] >= $this->maxAttempts;
    }

    public function recordFailure(): void {
        $data = $_SESSION[$this->sessionKey] ?? null;

        if (!$data || (time() - $data['first_attempt'] > $this->decaySeconds)) {
            $_SESSION[$this->sessionKey] = [
                'attempts'      => 1,
                'first_attempt' => time()
            ];
        } else {
            $_SESSION[$this->sessionKey]['attempts']++;
        }
    }

    public function getAttemptsLeft(): int {
        $data = $_SESSION[$this->sessionKey] ?? null;
        if (!$data) return $this->maxAttempts;
        $attempts = $data['attempts'] ?? 0;
        return max(0, $this->maxAttempts - $attempts);
    }

    public function getRemainingLockSeconds(): int {
        $data = $_SESSION[$this->sessionKey] ?? null;
        if (!$data) return 0;
        $elapsed = time() - $data['first_attempt'];
        return max(0, $this->decaySeconds - $elapsed);
    }

    public function reset(): void {
        unset($_SESSION[$this->sessionKey]);
    }
}
