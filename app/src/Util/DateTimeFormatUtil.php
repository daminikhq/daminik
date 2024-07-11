<?php

declare(strict_types=1);

namespace App\Util;

use App\Dto\Utility\DateRange;

class DateTimeFormatUtil
{
    private const YEAR = 'year';
    private const MONTH = 'month';
    private const DAY = 'day';

    public static function parseDateString(string $dateString): ?DateRange
    {
        preg_match('/(\d{4})-?(\d{1,2})?-?(\d{1,2})?/m', $dateString, $matches);

        return match (count($matches)) {
            2 => self::parseMatchesIntoDate($matches[1]),
            3 => self::parseMatchesIntoDate($matches[1], $matches[2]),
            4 => self::parseMatchesIntoDate($matches[1], $matches[2], $matches[3]),
            default => null,
        };
    }

    /** @noinspection PhpRedundantOptionalArgumentInspection */
    private static function parseMatchesIntoDate(string $yearString, ?string $monthString = null, ?string $dayString = null): DateRange
    {
        $s = self::YEAR;
        $year = (int) $yearString;

        if (null !== $monthString) {
            $s = self::MONTH;
            $month = (int) $monthString;
        } else {
            $month = 1;
        }
        if (null !== $dayString) {
            $s = self::DAY;
            $day = (int) $dayString;
        } else {
            $day = 1;
        }

        $parsedDateTime = (new \DateTime())->setDate($year, $month, $day);
        $after = clone $parsedDateTime;
        $before = clone $parsedDateTime;
        $second = new \DateInterval('PT1S');
        switch ($s) {
            case self::DAY:
                $after = $after->setTime(0, 0, 0)->sub($second);
                $before = $before->setTime(23, 59, 59)->add($second);
                break;
            case self::MONTH:
                $after = $after->setDate($year, $month, 1)->setTime(0, 0, 0)->sub($second);
                $before = $before->setDate($year, $month, (int) $parsedDateTime->format('t'))->setTime(23, 59, 59)->add($second);
                break;
            case self::YEAR:
                $after = $after->setDate($year, 1, 1)->setTime(0, 0, 0)->sub($second);
                $before = $before->setDate($year, 12, 31)->setTime(23, 59, 59)->add($second);
        }

        return new DateRange(
            dateTime: $parsedDateTime,
            after: $after,
            before: $before
        );
    }
}
