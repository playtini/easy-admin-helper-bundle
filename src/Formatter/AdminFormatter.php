<?php

namespace Playtini\EasyAdminHelperBundle\Formatter;

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
}
