import type { ClassValue } from "clsx"
import { clsx } from "clsx"
import { twMerge } from "tailwind-merge"

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}

const DATE_ONLY_PATTERN = /^\d{4}-\d{2}-\d{2}$/

/**
 * Format an ISO date or datetime string for display, e.g. "Jan 15, 2024".
 *
 * Date-only values ("2024-01-15") are calendar dates with no time zone, so they are
 * formatted in UTC — converting them to the viewer's zone would show the previous
 * day anywhere west of UTC. Datetimes are shown in the viewer's local time zone.
 */
export function formatDate(dateStr: string): string {
  const isDateOnly = DATE_ONLY_PATTERN.test(dateStr)

  return new Date(dateStr).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    ...(isDateOnly ? { timeZone: 'UTC' } : {}),
  })
}

export function formatFileSize(bytes?: number): string {
  if (!bytes) return '—';
  if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + ' MB';
  return Math.round(bytes / 1024) + ' KB';
}

/** Shorten text to `length` characters, adding an ellipsis when it was cut. */
export function truncate(text: string, length: number): string {
  return text.length > length ? text.slice(0, length) + '…' : text
}
