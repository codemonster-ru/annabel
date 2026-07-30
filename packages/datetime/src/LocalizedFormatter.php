<?php

declare(strict_types=1);

namespace Codemonster\DateTime;

final class LocalizedFormatter
{
    private string $locale;
    private int $dateStyle;
    private int $timeStyle;
    private ?string $pattern;

    public function __construct(
        string $locale,
        ?int $dateStyle = null,
        ?int $timeStyle = null,
        ?string $pattern = null,
    ) {
        if (!extension_loaded('intl')) {
            throw new DateTimeFormattingException('The intl extension is required for localized formatting.');
        }

        $this->locale = $locale;
        $this->dateStyle = $dateStyle ?? \IntlDateFormatter::MEDIUM;
        $this->timeStyle = $timeStyle ?? \IntlDateFormatter::SHORT;
        $this->pattern = $pattern;
    }

    public function format(DateTime|\DateTimeInterface $dateTime): string
    {
        $dateTime = $dateTime instanceof DateTime
            ? $dateTime->toNative()
            : \DateTimeImmutable::createFromInterface($dateTime);
        $locale = \Locale::canonicalize($this->locale);
        $availableLocales = \ResourceBundle::getLocales('');

        if (
            $locale === null
            || $availableLocales === false
            || !in_array($locale, $availableLocales, true)
        ) {
            throw new DateTimeFormattingException(
                'ICU locale data for [' . $this->locale . '] is not available.',
            );
        }

        $formatter = new \IntlDateFormatter(
            $locale,
            $this->dateStyle,
            $this->timeStyle,
            $dateTime->getTimezone(),
            \IntlDateFormatter::GREGORIAN,
            $this->pattern,
        );
        $formatted = $formatter->format($dateTime);

        if ($formatted === false) {
            throw new DateTimeFormattingException(
                'Unable to format date-time for locale [' . $this->locale . ']: ' . intl_get_error_message(),
            );
        }

        return $formatted;
    }
}
