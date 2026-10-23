<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Device;
use App\Models\DeviceLoan;
use App\Models\DeviceReservation;
use App\Models\Room;
use App\Models\RoomBooking;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Protokolliert schreibende Aktionen (wer, was, wann, woher).
 */
class Audit
{
    /** Kurze, stabile Typnamen statt Klassennamen */
    private const TYPES = [
        Device::class => 'device',
        DeviceLoan::class => 'loan',
        DeviceReservation::class => 'device_reservation',
        Room::class => 'room',
        RoomBooking::class => 'room_booking',
        Category::class => 'category',
        User::class => 'user',
    ];

    /**
     * @param  array<string, mixed>  $properties
     */
    public static function log(string $action, ?Model $subject = null, array $properties = [], ?User $actor = null): AuditLog
    {
        $actor ??= Auth::user();
        $request = request();

        [$type, $id, $label] = [null, null, null];
        if ($subject) {
            $type = self::TYPES[$subject::class] ?? Str::snake(class_basename($subject));
            $id = $subject->getKey();
            $label = self::labelFor($subject);
        }

        return AuditLog::create([
            'user_id' => $actor?->id,
            'user_name' => $actor?->name,
            'action' => $action,
            'subject_type' => $type,
            'subject_id' => $id,
            'subject_label' => $label ? Str::limit($label, 250) : null,
            'properties' => $properties ?: null,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
        ]);
    }

    public static function typeFor(string $class): ?string
    {
        return self::TYPES[$class] ?? null;
    }

    private static function labelFor(Model $subject): ?string
    {
        return match (true) {
            $subject instanceof Device => trim(($subject->inventory_number ? $subject->inventory_number.' · ' : '').$subject->title),
            $subject instanceof DeviceLoan => ($subject->device?->title ?? 'Gerät').' → '.$subject->borrower_name,
            $subject instanceof DeviceReservation => ($subject->device?->title ?? 'Gerät').' ('.$subject->start_at?->format('d.m.Y').')',
            $subject instanceof RoomBooking => ($subject->room?->name ?? 'Raum').' ('.$subject->start_at?->format('d.m.Y H:i').')',
            $subject instanceof Room, $subject instanceof Category => $subject->name,
            $subject instanceof User => $subject->name.' <'.$subject->email.'>',
            default => null,
        };
    }
}
