<?php

declare(strict_types=1);

/**
 * Run a transactional unit of work with retry on deadlock/serialization failures.
 *
 * @param PDO $pdo
 * @param callable $work Callable that receives the PDO and performs DB operations.
 * @param int $maxAttempts
 * @param int $initialBackoffMicro initial backoff in microseconds (default 100ms)
 *
 * @return mixed Returns whatever the callable returns.
 * @throws Throwable Re-throws the last exception if not recoverable.
 */
function runTransactionWithRetries(PDO $pdo, callable $work, int $maxAttempts = 3, int $initialBackoffMicro = 100000)
{
    $attempt = 0;

    while (true) {
        try {
            if (!$pdo->inTransaction()) {
                $pdo->beginTransaction();
            }

            $result = $work($pdo);

            if ($pdo->inTransaction()) {
                $pdo->commit();
            }

            return $result;
        } catch (Throwable $e) {
            // Always roll back if in transaction
            try {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
            } catch (Throwable $_) {
                // Ignore rollback failures
            }

            $attempt++;

            // Detect deadlock/serialization SQLSTATEs or MySQL error code 1213
            $isRetryable = false;
            if ($e instanceof PDOException) {
                $errNo = $e->errorInfo[1] ?? null;
                $sqlState = $e->errorInfo[0] ?? (string) $e->getCode();

                if ($errNo === 1213 || $sqlState === '40001' || $sqlState === '40P01') {
                    $isRetryable = true;
                }
            }

            if ($isRetryable && $attempt < $maxAttempts) {
                // exponential backoff
                $backoff = $initialBackoffMicro * (1 << ($attempt - 1));
                usleep($backoff);
                continue;
            }

            throw $e;
        }
    }
}
