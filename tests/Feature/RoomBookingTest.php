<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\RoomBooking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RoomBookingTest extends TestCase
{
    use RefreshDatabase;

    private function book(Room $room, string $start, string $end, string $purpose = 'Test')
    {
        [$sd, $st] = explode(' ', $start);
        [$ed, $et] = explode(' ', $end);

        return $this->post(route('bookings.store', $room), [
            'start_date' => $sd, 'start_time' => $st, 'end_date' => $ed, 'end_time' => $et, 'purpose' => $purpose,
        ]);
    }

    public function test_double_booking_is_rejected_but_back_to_back_is_fine(): void
    {
        $room = Room::factory()->create();
        $day = today()->addDay()->toDateString();
        $this->actingAs($this->moderator());

        $this->book($room, "$day 10:00", "$day 12:00")->assertSessionHasNoErrors();
        $this->book($room, "$day 11:00", "$day 13:00")->assertSessionHasErrors('start_date');
        $this->book($room, "$day 09:00", "$day 14:00")->assertSessionHasErrors('start_date');
        $this->book($room, "$day 12:00", "$day 13:00")->assertSessionHasNoErrors();
        $this->book($room, "$day 08:00", "$day 10:00")->assertSessionHasNoErrors();

        $this->assertSame(3, RoomBooking::count());
    }

    public function test_multi_day_booking_is_possible(): void
    {
        $room = Room::factory()->create();
        $this->actingAs($this->moderator());

        // früher scheiterte das an "end_time after:start_time"
        $this->book($room, today()->addDay()->toDateString().' 14:00', today()->addDays(2)->toDateString().' 10:00')
            ->assertSessionHasNoErrors();

        $booking = RoomBooking::first();
        $this->assertSame('14:00', $booking->start_at->format('H:i'));
        $this->assertSame(today()->addDays(2)->toDateString(), $booking->end_at->toDateString());
    }

    public function test_end_before_start_and_past_bookings_are_rejected(): void
    {
        $room = Room::factory()->create();
        $day = today()->addDay()->toDateString();
        $this->actingAs($this->moderator());

        $this->book($room, "$day 12:00", "$day 10:00")->assertSessionHasErrors('end_time');
        $this->book($room, today()->subDay()->toDateString().' 10:00', today()->subDay()->toDateString().' 12:00')->assertSessionHasErrors('start_date');

        $this->setting(['rooms.allow_past' => true]);
        $this->book($room, today()->subDay()->toDateString().' 10:00', today()->subDay()->toDateString().' 12:00')->assertSessionHasNoErrors();
    }

    public function test_update_checks_overlap_too(): void
    {
        $room = Room::factory()->create();
        $mod = $this->moderator();
        $day = today()->addDay();
        RoomBooking::factory()->create(['room_id' => $room->id, 'start_at' => $day->copy()->setTime(8, 0), 'end_at' => $day->copy()->setTime(10, 0)]);
        $mine = RoomBooking::factory()->create(['room_id' => $room->id, 'user_id' => $mod->id, 'start_at' => $day->copy()->setTime(12, 0), 'end_at' => $day->copy()->setTime(13, 0)]);

        $this->actingAs($mod)->put(route('bookings.update', $mine), [
            'room_id' => $room->id, 'start_date' => $day->toDateString(), 'start_time' => '09:00',
            'end_date' => $day->toDateString(), 'end_time' => '11:00', 'purpose' => 'x',
        ])->assertSessionHasErrors('start_date');

        $this->put(route('bookings.update', $mine), [
            'room_id' => $room->id, 'start_date' => $day->toDateString(), 'start_time' => '12:00',
            'end_date' => $day->toDateString(), 'end_time' => '14:00', 'purpose' => 'länger',
        ])->assertSessionHasNoErrors();
        $this->assertSame('14:00', $mine->fresh()->end_at->format('H:i'));
    }

    public function test_bookings_use_local_timezone(): void
    {
        $this->assertSame('Europe/Berlin', config('app.timezone'));

        Carbon::setTestNow(Carbon::parse('2026-07-01 11:30', 'Europe/Berlin'));
        $room = Room::factory()->create();
        RoomBooking::factory()->create(['room_id' => $room->id, 'start_at' => '2026-07-01 10:00', 'end_at' => '2026-07-01 12:00']);

        // läuft noch (12:00 Ortszeit > 11:30) – mit UTC wäre sie schon im Archiv gelandet
        $this->assertSame(1, RoomBooking::upcoming()->count());
        Carbon::setTestNow();
    }

    public function test_room_with_upcoming_bookings_cannot_be_deleted(): void
    {
        $room = Room::factory()->create();
        RoomBooking::factory()->create(['room_id' => $room->id]);

        $this->actingAs($this->admin())->delete(route('rooms.destroy', $room))->assertSessionHas('error');
        $this->assertNotSoftDeleted($room);
    }
}
