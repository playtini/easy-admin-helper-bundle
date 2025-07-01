<?php

namespace Playtini\EasyAdminHelperBundle\Formatter;

use DateTimeInterface;
use Gupalo\DateUtils\DateUtils;

class AdminFormatter
{
    public static function formatBoolNull(?bool $v): string
    {
        if ($v === null) {
            return '-';
        }

        return $v ? '1' : '0';
    }

    public static function formatUrlPath(?string $url, int $maxLength = 20): string
    {
        $regex = '#^.*?://[^/]+/?#';
        if (preg_match($regex, (string)$url)) {
            $result = preg_replace($regex, '', (string)$url);
        } else {
            $result = 'invalid_url: ' . ($url ?? '(null)');
        }

        if (mb_strlen($result) > $maxLength) {
            $result = mb_substr($result, 0, max(3, $maxLength - 3)).'...';
        }
        if ($result === '') {
            $result = '/';
        }

        return $result;
    }

    public static function breakWords(?string $s): string
    {
        return sprintf('<span class="break-words">%s</span>', htmlspecialchars((string)$s));
    }

    public static function muteZero(int $v): string
    {
        $s = number_format($v);

        return $v ? $s : sprintf('<span class="text-muted" style="color: #555 !important;">%s</span>', $s);
    }

    public static function formatExpireDate(
        ?DateTimeInterface $date,
        int $dangerDays = 0,
        int $warningDays = 7,
        int $infoDays = 30,
    ): string
    {
        if (!$date) {
            return '-';
        }

        $daysRemaining = DateUtils::diffDays($date, DateUtils::today());

        $class = match (true) {
            ($daysRemaining <= $dangerDays) => 'text-danger',
            ($daysRemaining <= $warningDays) => 'text-warning',
            ($daysRemaining <= $infoDays) => 'text-info',
            default => '',
        };

        return sprintf('<span class="%s">%s</span>', $class, DateUtils::formatShort($date));
    }

    public static function formatBoolNullEmoji(?bool $v): string
    {
        return match ($v) {
            true => '🟢',
            false => '🔴',
            default => ' ',
            //default => '🌕',
        };
    }

    public static function formatHttpStatus(int $v): string
    {
        if ($v < 200) {
            $class = 'warning';
        } elseif ($v < 300) {
            $class = 'success';
        } elseif ($v < 400) {
            $class = 'warning';
        } else {
            $class = 'danger';
        }

        return sprintf('<span class="badge rounded-pill bg-%s">%s</span>', $class, $v);
    }

    public static function percents(mixed $value, mixed $total, int $precision = 0): string
    {
        $value = (float)$value;
        $total = (float)$total;

        if ($total === 0.0) {
            return '<span class="text-muted" style="color: #555 !important;">-</span>';
        }
        if ($value === 0.0) {
            return '<span class="text-muted" style="color: #555 !important;">0%</span>';
        }

        return round(100 * $value / $total, $precision) . '%';
    }

}
