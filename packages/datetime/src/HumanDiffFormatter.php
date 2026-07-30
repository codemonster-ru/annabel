<?php

declare(strict_types=1);

namespace Codemonster\DateTime;

final class HumanDiffFormatter
{
    /** @var array<string, array{now: string, future: string, past: string, units: array<string, string>}> */
    private const MESSAGES = [
        'en' => [
            'now' => 'just now',
            'future' => 'in %s',
            'past' => '%s ago',
            'units' => [
                'year' => '{count, plural, one {# year} other {# years}}',
                'month' => '{count, plural, one {# month} other {# months}}',
                'week' => '{count, plural, one {# week} other {# weeks}}',
                'day' => '{count, plural, one {# day} other {# days}}',
                'hour' => '{count, plural, one {# hour} other {# hours}}',
                'minute' => '{count, plural, one {# minute} other {# minutes}}',
                'second' => '{count, plural, one {# second} other {# seconds}}',
            ],
        ],
        'ru' => [
            'now' => 'только что',
            'future' => 'через %s',
            'past' => '%s назад',
            'units' => [
                'year' => '{count, plural, one {# год} few {# года} many {# лет} other {# года}}',
                'month' => '{count, plural, one {# месяц} few {# месяца} many {# месяцев} other {# месяца}}',
                'week' => '{count, plural, one {# неделя} few {# недели} many {# недель} other {# недели}}',
                'day' => '{count, plural, one {# день} few {# дня} many {# дней} other {# дня}}',
                'hour' => '{count, plural, one {# час} few {# часа} many {# часов} other {# часа}}',
                'minute' => '{count, plural, one {# минута} few {# минуты} many {# минут} other {# минуты}}',
                'second' => '{count, plural, one {# секунда} few {# секунды} many {# секунд} other {# секунды}}',
            ],
        ],
    ];

    private string $language;

    public function __construct(private string $locale = 'en')
    {
        if (!extension_loaded('intl')) {
            throw new DateTimeFormattingException('The intl extension is required for human interval formatting.');
        }

        $language = strtolower((string) strtok(str_replace('-', '_', $locale), '_'));

        if (!isset(self::MESSAGES[$language])) {
            throw new \InvalidArgumentException('Unsupported human diff locale [' . $locale . '].');
        }

        $this->language = $language;
    }

    public function formatDuration(\DateInterval $interval, int $maxParts = 2): string
    {
        if ($maxParts < 1) {
            throw new \InvalidArgumentException('Human diff max parts must be at least 1.');
        }

        $days = $interval->d;
        $values = [
            'year' => $interval->y,
            'month' => $interval->m,
            'week' => intdiv($days, 7),
            'day' => $days % 7,
            'hour' => $interval->h,
            'minute' => $interval->i,
            'second' => $interval->s,
        ];
        $parts = [];

        foreach ($values as $unit => $value) {
            if ($value === 0) {
                continue;
            }

            $parts[] = $this->formatUnit($unit, $value);

            if (count($parts) === $maxParts) {
                break;
            }
        }

        return $parts === [] ? $this->formatUnit('second', 0) : implode(' ', $parts);
    }

    public function formatRelative(
        DateTime|\DateTimeInterface $reference,
        DateTime|\DateTimeInterface $target,
        int $maxParts = 1,
    ): string {
        $reference = self::dateTime($reference);
        $target = self::dateTime($target);
        $messages = self::MESSAGES[$this->language];

        if ($reference->isSameInstant($target)) {
            return $messages['now'];
        }

        $duration = $this->formatDuration($reference->diff($target, true), $maxParts);
        $template = $target->isAfter($reference) ? $messages['future'] : $messages['past'];

        return sprintf($template, $duration);
    }

    private function formatUnit(string $unit, int $count): string
    {
        $pattern = self::MESSAGES[$this->language]['units'][$unit];
        $formatter = new \MessageFormatter($this->locale, $pattern);
        $formatted = $formatter->format(['count' => $count]);

        if ($formatted === false) {
            throw new DateTimeFormattingException(
                'Unable to format human interval for locale [' . $this->locale . ']: '
                . $formatter->getErrorMessage(),
            );
        }

        return $formatted;
    }

    private static function dateTime(DateTime|\DateTimeInterface $dateTime): DateTime
    {
        return $dateTime instanceof DateTime ? $dateTime : DateTime::fromInterface($dateTime);
    }
}
