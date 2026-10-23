<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Device;
use App\Models\Room;
use App\Models\RoomBooking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Früher landeten Raum- und Kategorienamen in onclick-Handlern bzw. innerHTML.
 */
class XssTest extends TestCase
{
    use RefreshDatabase;

    private const PAYLOADS = [
        "x');alert(1);//",
        '<img src=x onerror=alert(1)>',
        '"><script>alert(1)</script>',
    ];

    public function test_room_and_category_names_are_never_rendered_raw(): void
    {
        foreach (self::PAYLOADS as $i => $payload) {
            $category = Category::factory()->create(['name' => $payload.$i]);
            $device = Device::factory()->create(['category_id' => $category->id, 'title' => $payload.'dev'.$i]);
            $room = Room::factory()->create(['name' => $payload.'room'.$i]);
            RoomBooking::factory()->create(['room_id' => $room->id, 'purpose' => $payload]);
        }

        $this->actingAs($this->admin());

        foreach (['devices.index', 'bookings.index', 'rooms.index', 'categories.index', 'home', 'labels.index', 'calendar'] as $route) {
            $html = $this->get(route($route))->assertOk()->getContent();

            $this->assertStringNotContainsString('<img src=x onerror', $html, $route);
            $this->assertStringNotContainsString('<script>alert(1)</script>', $html, $route);
            $this->assertStringNotContainsString("x');alert(1)", $html, $route);
            $this->assertDoesNotMatchRegularExpression('/on\w+="[^"]*alert/i', $html, $route);
        }

        $this->get(route('devices.show', Device::first()))->assertOk()->assertDontSee('<img src=x onerror', false);
    }

    public function test_search_returns_plain_json(): void
    {
        Device::factory()->create(['title' => '<img src=x onerror=alert(1)>']);

        $this->actingAs($this->admin())
            ->getJson(route('search', ['q' => 'img']))
            ->assertOk()
            ->assertJsonPath('results.0.title', '<img src=x onerror=alert(1)>');
    }

    public function test_legal_markdown_strips_html_and_javascript_links(): void
    {
        $this->setting(['legal.imprint' => "# Impressum\n\n<script>alert(1)</script>\n\n[klick](javascript:alert(1))"]);

        $this->get(route('legal.imprint'))
            ->assertOk()
            ->assertSee('<h1>Impressum</h1>', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('javascript:alert', false);
    }
}
