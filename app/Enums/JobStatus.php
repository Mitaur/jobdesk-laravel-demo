<?php

namespace App\Enums;

enum JobStatus: string
{
    case Quote = 'quote';
    case Scheduled = 'scheduled';
    case Done = 'done';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Quote => 'Quote',
            self::Scheduled => 'Scheduled',
            self::Done => 'Done',
            self::Cancelled => 'Cancelled',
        };
    }

    /** Tailwind classes for the status badge (all pass WCAG AA contrast). */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Quote => 'bg-amber-100 text-amber-900 ring-amber-300',
            self::Scheduled => 'bg-blue-100 text-blue-900 ring-blue-300',
            self::Done => 'bg-emerald-100 text-emerald-900 ring-emerald-300',
            self::Cancelled => 'bg-gray-100 text-gray-800 ring-gray-300',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
