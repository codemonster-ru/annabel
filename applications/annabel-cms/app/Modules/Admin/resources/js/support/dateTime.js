let dateTimeFormatter = createFormatter();

function createFormatter(locale) {
  return new Intl.DateTimeFormat(locale || undefined, {
    dateStyle: 'medium',
    timeStyle: 'short',
  });
}

export function configureDateTime({ locale } = {}) {
  dateTimeFormatter = createFormatter(locale);
}

export function formatDateTime(value) {
  if (value == null || value === '') return '—';

  const date = new Date(value);

  return Number.isNaN(date.getTime()) ? '—' : dateTimeFormatter.format(date);
}

export function toLocalDateTimeValue(value) {
  if (value == null || value === '') return '';

  const date = new Date(value);

  if (Number.isNaN(date.getTime())) return '';

  const year = String(date.getFullYear()).padStart(4, '0');
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  const hour = String(date.getHours()).padStart(2, '0');
  const minute = String(date.getMinutes()).padStart(2, '0');

  return `${year}-${month}-${day}T${hour}:${minute}`;
}

export function localDateTimeValueToIso(value) {
  if (value == null || value === '') return '';

  const match = value.match(/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})$/);

  if (!match) return null;

  const [, year, month, day, hour, minute] = match.map(Number);
  const date = new Date(year, month - 1, day, hour, minute);

  if (toLocalDateTimeValue(date) !== value) return null;

  return date.toISOString();
}
