<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Device;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_numbers_are_generated_per_category(): void
    {
        $cables = Category::factory()->create(['name' => 'Kabel', 'code' => 'KAB']);
        $this->actingAs($this->admin());

        $this->post(route('devices.store'), ['title' => 'HDMI', 'category_id' => $cables->id, 'total_quantity' => 5]);
        $this->post(route('devices.store'), ['title' => 'USB-C', 'category_id' => $cables->id, 'total_quantity' => 1]);
        $this->post(route('devices.store'), ['title' => 'Eigen', 'category_id' => $cables->id, 'total_quantity' => 1, 'inventory_number' => 'sonder-1']);

        $this->assertSame(['KAB-0001', 'KAB-0002', 'SONDER-1'], Device::orderBy('id')->pluck('inventory_number')->all());
        $this->assertSame('KAB-0001-5', Device::first()->unitLabel(5));
    }

    public function test_category_code_is_suggested(): void
    {
        $this->actingAs($this->admin())->post(route('categories.store'), ['name' => 'Lautsprecher']);
        $this->post(route('categories.store'), ['name' => 'Laptop']);

        $this->assertSame(['LAU', 'LAP'], Category::orderBy('id')->pluck('code')->all());
    }

    public function test_lookup_resolves_ids_units_and_typos(): void
    {
        $device = Device::factory()->quantity(5)->create(['inventory_number' => 'KAB-0042']);
        $this->actingAs($this->moderator());

        $this->get('/d/KAB-0042')->assertRedirect(route('devices.show', $device));
        $this->get('/d/KAB-0042-3')->assertRedirect(route('devices.show', ['device' => $device, 'unit' => 3]));
        $this->get('/d/kab-42-3')->assertRedirect(route('devices.show', ['device' => $device, 'unit' => 3]));
        // Hand-Scanner mit US-Layout: "-" kommt als "ß"
        $this->get('/d/'.rawurlencode('KABß0042ß3'))->assertRedirect(route('devices.show', ['device' => $device, 'unit' => 3]));
        // Exemplar existiert nicht
        $this->get('/d/KAB-0042-9')->assertRedirect(route('scan'));
        $this->get('/d/GIBTS-NICHT')->assertRedirect(route('scan'))->assertSessionHas('error');

        $this->postJson(route('lookup'), ['code' => 'https://loan.example.org/d/KAB-0042-2'])
            ->assertOk()->assertJson(['found' => true, 'label' => 'KAB-0042-2']);
    }

    public function test_labels_render_qr_and_barcodes(): void
    {
        $device = Device::factory()->quantity(2)->create(['inventory_number' => 'LAP-0007']);

        $html = $this->actingAs($this->moderator())->get(route('labels.print', [
            'devices' => [$device->id], 'mode' => 'both', 'format' => 'a4_3x8', 'code_type' => 'both', 'show_title' => 1,
        ]))->assertOk()->getContent();

        $this->assertStringContainsString('LAP-0007-2', $html);
        $this->assertSame(3, substr_count($html, '<div class="qr">'));
        $this->assertSame(3, substr_count($html, '<div class="bar">'));
    }

    public function test_uploaded_images_are_reencoded_without_metadata(): void
    {
        Storage::fake('public');
        $category = Category::factory()->create();

        $this->actingAs($this->admin())->post(route('devices.store'), [
            'title' => 'Kamera', 'category_id' => $category->id, 'total_quantity' => 1,
            'image' => UploadedFile::fake()->image('foto.jpg', 3000, 2000),
        ])->assertSessionHasNoErrors();

        $path = Device::first()->image;
        $this->assertStringEndsWith('.webp', $path);
        Storage::disk('public')->assertExists($path);
        Storage::disk('public')->assertExists(dirname($path).'/thumbs/'.basename($path));
        [$w] = getimagesize(Storage::disk('public')->path($path));
        $this->assertSame(1600, $w);
        // Thumbnail entsteht aus demselben (einmal dekodierten) Bild
        [$tw] = getimagesize(Storage::disk('public')->path(dirname($path).'/thumbs/'.basename($path)));
        $this->assertSame(400, $tw);
    }

    public function test_non_images_are_rejected(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->admin())->post(route('devices.store'), [
            'title' => 'X', 'category_id' => $category->id, 'total_quantity' => 1,
            'image' => UploadedFile::fake()->create('shell.php', 10, 'application/x-php'),
        ])->assertSessionHasErrors('image');

        $this->actingAs($this->admin())->post(route('devices.store'), [
            'title' => 'X', 'category_id' => $category->id, 'total_quantity' => 1,
            'image' => UploadedFile::fake()->createWithContent('bild.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
        ])->assertSessionHasErrors('image');
    }

    public function test_global_search_finds_devices_and_ids(): void
    {
        Device::factory()->quantity(3)->create(['inventory_number' => 'LS-0001', 'title' => 'Lautsprecher JBL']);

        $this->actingAs($this->moderator())->getJson(route('search', ['q' => 'LS-0001-2']))
            ->assertOk()
            ->assertJsonPath('results.0.type', 'id')
            ->assertJsonPath('results.0.title', 'LS-0001-2');

        $this->getJson(route('search', ['q' => 'jbl']))->assertJsonPath('results.0.title', 'Lautsprecher JBL');
    }
}
