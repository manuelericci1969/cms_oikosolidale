<?php

namespace App\Logging;

use App\Models\SystemError;
use Illuminate\Support\Str;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;

class SystemErrorHandler extends AbstractProcessingHandler
{
    public function __construct(Level $level = Level::Error, bool $bubble = true)
    {
        parent::__construct($level, $bubble);
    }

    protected function write(LogRecord $record): void
    {
        try {
            $context = $this->sanitize($record->context ?? []);
            $extra = $this->sanitize($record->extra ?? []);

            $exception = $record->context['exception'] ?? null;

            SystemError::create([
                'environment' => app()->environment(),
                'level' => $record->level->getName(),
                'channel' => $record->channel,
                'message' => Str::limit((string) $record->message, 1024, ''),
                'exception_class' => is_object($exception) ? get_class($exception) : null,
                'file' => is_object($exception) && method_exists($exception, 'getFile') ? $exception->getFile() : null,
                'line' => is_object($exception) && method_exists($exception, 'getLine') ? (int) $exception->getLine() : null,
                'trace' => is_object($exception) && method_exists($exception, 'getTraceAsString')
                    ? Str::limit($exception->getTraceAsString(), 64000, "\n...truncated")
                    : null,
                'context' => $context,
                'extra' => $extra,
            ]);
        } catch (\Throwable $e) {
            // fail-safe
        }
    }

    private function sanitize(mixed $value): mixed
    {
        if (is_null($value) || is_scalar($value)) {
            return $value;
        }

        if ($value instanceof \Throwable) {
            return [
                'class' => get_class($value),
                'message' => $value->getMessage(),
                'file' => $value->getFile(),
                'line' => $value->getLine(),
            ];
        }

        if (is_array($value)) {
            return collect($value)
                ->map(fn ($v) => $this->sanitize($v))
                ->take(200)
                ->all();
        }

        if (is_object($value)) {
            return [
                'class' => get_class($value),
                'string' => method_exists($value, '__toString') ? (string) $value : null,
            ];
        }

        return (string) $value;
    }
}
