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

    public function test_labels_print_copies_and_note(): void
    {
        $device = Device::factory()->create(['inventory_number' => 'LAP-0008']);

        $html = $this->actingAs($this->moderator())->get(route('labels.print', [
            'devices' => [$device->id], 'mode' => 'device', 'format' => 'a4_3x8', 'code_type' => 'qr',
            'copies' => 3, 'note' => 'Eigentum <b>MakerLab</b>',
        ]))->assertOk()->getContent();

        $this->assertSame(3, substr_count($html, '<div class="qr">'));
        $this->assertSame(3, substr_count($html, 'Eigentum &lt;b&gt;MakerLab&lt;/b&gt;'));
    }

    public function test_labels_support_gaps_layout_and_custom_formats(): void
    {
        $device = Device::factory()->quantity(2)->create(['inventory_number' => 'KAB-0009']);
        $this->actingAs($this->moderator());
        $params = ['devices' => [$device->id], 'mode' => 'units', 'code_type' => 'qr'];

        $html = $this->get(route('labels.print', $params + [
            'format' => 'a4_5x13', 'layout' => 'column', 'text_size' => 'l', 'cut_lines' => 1, 'offset_x' => -1.5,
        ]))->assertOk()->getContent();
        $this->assertStringContainsString('column-gap: 2.5mm', $html);
        $this->assertStringContainsString('flex-direction: column', $html);
        $this->assertStringContainsString('<body class="cut">', $html);
        $this->assertStringContainsString('padding: 10.7mm 0 0 3.25mm', $html); // Rand links 4,75 − 1,5

        $html = $this->get(route('labels.print', $params + [
            'format' => 'custom', 'custom_w' => 40, 'custom_h' => 12,
        ]))->assertOk()->getContent();
        $this->assertStringContainsString('size: 40mm 12mm', $html);
        $this->assertSame(2, substr_count($html, '<div class="qr">'));

        $this->get(route('labels.print', $params + [
            'format' => 'custom', 'custom_sheet' => 1, 'custom_w' => 80, 'custom_h' => 30, 'custom_cols' => 3, 'custom_rows' => 5,
        ]))->assertSessionHasErrors('custom_w');
        $this->get(route('labels.print', $params + ['format' => 'custom']))->assertSessionHasErrors(['custom_w', 'custom_h']);
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

    public function test_uploaded_photos_are_rotated_by_exif_orientation(): void
    {
        $this->assertTrue(function_exists('exif_read_data'), 'PHP-Extension exif fehlt');
        Storage::fake('public');
        $category = Category::factory()->create();

        // Handyfoto: Pixel quer gespeichert, EXIF-Orientation 6 (= 90° im Uhrzeigersinn drehen)
        $gd = imagecreatetruecolor(300, 200);
        ob_start();
        imagejpeg($gd);
        $jpeg = ob_get_clean();
        $tiff = "MM\x00\x2A\x00\x00\x00\x08"."\x00\x01"."\x01\x12\x00\x03\x00\x00\x00\x01\x00\x06\x00\x00"."\x00\x00\x00\x00";
        $app1 = "\xFF\xE1".pack('n', strlen($tiff) + 8)."Exif\x00\x00".$tiff;
        $photo = substr($jpeg, 0, 2).$app1.substr($jpeg, 2);

        $this->actingAs($this->admin())->post(route('devices.store'), [
            'title' => 'Box', 'category_id' => $category->id, 'total_quantity' => 1,
            'image' => UploadedFile::fake()->createWithContent('foto.jpg', $photo),
        ])->assertSessionHasNoErrors();

        [$w, $h] = getimagesize(Storage::disk('public')->path(Device::first()->image));
        $this->assertSame([200, 300], [$w, $h]);
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
