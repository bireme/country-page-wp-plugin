<?php
namespace CP\Support;

if (!defined('ABSPATH')) exit;

final class Logger {
    public const OPTION_KEY = 'cp_logs';
    private const RETENTION_DAYS = 5;
    private const MAX_ENTRIES = 500;

    /** @param array<string, mixed> $context */
    public static function info(string $message, array $context = []): void {
        self::log('info', $message, $context);
    }

    /** @param array<string, mixed> $context */
    public static function warning(string $message, array $context = []): void {
        self::log('warning', $message, $context);
    }

    /** @param array<string, mixed> $context */
    public static function error(string $message, array $context = []): void {
        self::log('error', $message, $context);
    }

    /** @param array<string, mixed> $context */
    public static function log(string $level, string $message, array $context = []): void {
        $level = in_array($level, ['info', 'warning', 'error'], true) ? $level : 'info';
        $now = time();
        $logs = self::prune(self::all(), $now);

        $logs[] = [
            'id' => function_exists('wp_generate_uuid4') ? wp_generate_uuid4() : uniqid('cp_log_', true),
            'created_at' => gmdate('c', $now),
            'timestamp' => $now,
            'level' => $level,
            'message' => wp_strip_all_tags($message),
            'context' => self::sanitizeContext($context),
        ];

        if (count($logs) > self::MAX_ENTRIES) {
            $logs = array_slice($logs, -self::MAX_ENTRIES);
        }

        update_option(self::OPTION_KEY, $logs, false);
    }

    /** @return array<int, array<string, mixed>> */
    public static function all(): array {
        $logs = get_option(self::OPTION_KEY, []);
        return is_array($logs) ? array_values(array_filter($logs, 'is_array')) : [];
    }

    public static function clear(): void {
        delete_option(self::OPTION_KEY);
    }

    /** @param array<int, array<string, mixed>> $logs */
    private static function prune(array $logs, int $now): array {
        $min = $now - (self::RETENTION_DAYS * DAY_IN_SECONDS);
        return array_values(array_filter($logs, static function (array $entry) use ($min): bool {
            return (int) ($entry['timestamp'] ?? 0) >= $min;
        }));
    }

    /** @param array<string, mixed> $context */
    private static function sanitizeContext(array $context): array {
        $clean = [];
        foreach ($context as $key => $value) {
            $key = sanitize_key((string) $key);
            if ($key === '') {
                continue;
            }
            if (is_scalar($value) || $value === null) {
                $clean[$key] = self::trimValue((string) $value);
                continue;
            }
            $encoded = wp_json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $clean[$key] = self::trimValue(is_string($encoded) ? $encoded : '[valor nao serializavel]');
        }
        return $clean;
    }

    private static function trimValue(string $value): string {
        $max = 2000;
        return strlen($value) > $max ? substr($value, 0, $max) . '...' : $value;
    }
}
