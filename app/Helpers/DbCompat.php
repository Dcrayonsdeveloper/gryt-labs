<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

/**
 * Database compatibility helper — abstracts MySQL vs PostgreSQL differences.
 * Use this for raw SQL that differs between database engines.
 */
class DbCompat
{
    /**
     * Check if the current connection is PostgreSQL.
     */
    public static function isPostgres(): bool
    {
        return DB::connection()->getDriverName() === 'pgsql';
    }

    /**
     * DATE() — extract date from datetime.
     * MySQL: DATE(col), PostgreSQL: col::date or CAST(col AS DATE)
     */
    public static function date(string $column): string
    {
        return static::isPostgres()
            ? "CAST({$column} AS DATE)"
            : "DATE({$column})";
    }

    /**
     * YEAR() — extract year from datetime.
     * MySQL: YEAR(col), PostgreSQL: EXTRACT(YEAR FROM col)
     */
    public static function year(string $column): string
    {
        return static::isPostgres()
            ? "EXTRACT(YEAR FROM {$column})::INTEGER"
            : "YEAR({$column})";
    }

    /**
     * MONTH() — extract month from datetime.
     * MySQL: MONTH(col), PostgreSQL: EXTRACT(MONTH FROM col)
     */
    public static function month(string $column): string
    {
        return static::isPostgres()
            ? "EXTRACT(MONTH FROM {$column})::INTEGER"
            : "MONTH({$column})";
    }

    /**
     * HOUR() — extract hour from datetime.
     * MySQL: HOUR(col), PostgreSQL: EXTRACT(HOUR FROM col)
     */
    public static function hour(string $column): string
    {
        return static::isPostgres()
            ? "EXTRACT(HOUR FROM {$column})::INTEGER"
            : "HOUR({$column})";
    }

    /**
     * YEARWEEK() — year + week number.
     * MySQL: YEARWEEK(col, 1), PostgreSQL: EXTRACT(ISOYEAR FROM col) * 100 + EXTRACT(WEEK FROM col)
     */
    public static function yearWeek(string $column): string
    {
        return static::isPostgres()
            ? "(EXTRACT(ISOYEAR FROM {$column})::INTEGER * 100 + EXTRACT(WEEK FROM {$column})::INTEGER)"
            : "YEARWEEK({$column}, 1)";
    }

    /**
     * DATEDIFF() — days between two dates.
     * MySQL: DATEDIFF(date1, date2), PostgreSQL: (date1::date - date2::date)
     */
    public static function dateDiff(string $date1, string $date2): string
    {
        return static::isPostgres()
            ? "({$date1}::date - {$date2}::date)"
            : "DATEDIFF({$date1}, {$date2})";
    }

    /**
     * IFNULL() — MySQL-specific null coalescing.
     * MySQL: IFNULL(col, default), PostgreSQL: COALESCE(col, default)
     * (COALESCE works on both, so always use this)
     */
    public static function ifNull(string $column, string $default): string
    {
        return "COALESCE({$column}, {$default})";
    }

    /**
     * GROUP_CONCAT() — aggregate string concatenation.
     * MySQL: GROUP_CONCAT(col SEPARATOR ', ')
     * PostgreSQL: STRING_AGG(col::text, ', ')
     */
    public static function groupConcat(string $column, string $separator = ', '): string
    {
        return static::isPostgres()
            ? "STRING_AGG({$column}::text, '{$separator}')"
            : "GROUP_CONCAT({$column} SEPARATOR '{$separator}')";
    }

    /**
     * NOW() — current timestamp (works on both).
     */
    public static function now(): string
    {
        return 'NOW()';
    }

    /**
     * Case-insensitive LIKE operator.
     * PostgreSQL: ILIKE. MySQL: plain LIKE, which is already case-insensitive under the
     * *_ci collations this app uses — MySQL has no ILIKE and errors out on it.
     */
    public static function ilike(): string
    {
        return static::isPostgres() ? 'ilike' : 'like';
    }

    /**
     * JSON_EXTRACT / ->> operator.
     * MySQL: JSON_EXTRACT(col, '$.key') or col->'$.key'
     * PostgreSQL: col->>'key'
     */
    public static function jsonExtract(string $column, string $key): string
    {
        return static::isPostgres()
            ? "{$column}->>'$key'"
            : "JSON_UNQUOTE(JSON_EXTRACT({$column}, '$.{$key}'))";
    }
}
