export function firstError(errors, field) {
  const source = errors && typeof errors === 'object' && 'value' in errors
    ? errors.value
    : errors || {};
  const messages = source[field];

  return Array.isArray(messages) && messages.length > 0
    ? messages[0]
    : '';
}
