<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PerfDebug
{
    public function handle(Request $request, Closure $next): Response
    {
        $enabled = config('app.debug') && $request->headers->get('X-Perf-Debug') === '1';
        if (!$enabled) {
            /** @var Response $response */
            $response = $next($request);
            return $response;
        }

        $start = microtime(true);
        $queryCount = 0;
        $queryTimeMs = 0.0;
        $slowestMs = 0.0;
        $slowestSql = null;
        $bySql = []; // sql => ['count'=>int,'time_ms'=>float]

        DB::flushQueryLog();

        $listener = function ($query) use (&$queryCount, &$queryTimeMs, &$slowestMs, &$slowestSql) {
            $queryCount += 1;
            $ms = (float) ($query->time ?? 0);
            $queryTimeMs += $ms;
            if ($ms > $slowestMs) {
                $slowestMs = $ms;
                $slowestSql = (string) ($query->sql ?? '');
            }
        };

        $listener = function ($query) use (&$queryCount, &$queryTimeMs, &$slowestMs, &$slowestSql, &$bySql) {
            $queryCount += 1;
            $ms = (float) ($query->time ?? 0);
            $queryTimeMs += $ms;
            $sql = (string) ($query->sql ?? '');

            if ($ms > $slowestMs) {
                $slowestMs = $ms;
                $slowestSql = $sql;
            }

            if ($sql !== '') {
                if (!isset($bySql[$sql])) {
                    $bySql[$sql] = ['count' => 0, 'time_ms' => 0.0];
                }
                $bySql[$sql]['count'] += 1;
                $bySql[$sql]['time_ms'] += $ms;
            }
        };

        DB::listen($listener);

        /** @var Response $response */
        $response = $next($request);

        $durationMs = (microtime(true) - $start) * 1000;

        $response->headers->set('X-Perf-Duration-Ms', (string) round($durationMs, 1));
        $response->headers->set('X-Perf-Query-Count', (string) $queryCount);
        $response->headers->set('X-Perf-Query-Time-Ms', (string) round($queryTimeMs, 1));
        $response->headers->set('Server-Timing', sprintf(
            'app;dur=%.1f, db;dur=%.1f',
            $durationMs,
            $queryTimeMs
        ));

        // Top offenders (for logs only — headers would be too large).
        $topByCount = [];
        $topByTime = [];
        if (!empty($bySql)) {
            $entries = [];
            foreach ($bySql as $sql => $stats) {
                $entries[] = ['sql' => $sql, 'count' => $stats['count'], 'time_ms' => $stats['time_ms']];
            }

            usort($entries, fn ($a, $b) => ($b['count'] <=> $a['count']) ?: ($b['time_ms'] <=> $a['time_ms']));
            $topByCount = array_slice($entries, 0, 5);

            usort($entries, fn ($a, $b) => ($b['time_ms'] <=> $a['time_ms']) ?: ($b['count'] <=> $a['count']));
            $topByTime = array_slice($entries, 0, 5);
        }

        Log::info('perf_debug', [
            'method' => $request->method(),
            'path' => $request->path(),
            'duration_ms' => round($durationMs, 1),
            'query_count' => $queryCount,
            'query_time_ms' => round($queryTimeMs, 1),
            'slowest_ms' => round($slowestMs, 1),
            'slowest_sql' => $slowestSql,
            'top_by_count' => array_map(fn ($e) => [
                'count' => $e['count'],
                'time_ms' => round($e['time_ms'], 1),
                'sql' => $e['sql'],
            ], $topByCount),
            'top_by_time' => array_map(fn ($e) => [
                'count' => $e['count'],
                'time_ms' => round($e['time_ms'], 1),
                'sql' => $e['sql'],
            ], $topByTime),
        ]);

        return $response;
    }
}
