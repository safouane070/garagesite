<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CarImageUploadTest extends TestCase
{
    use RefreshDatabase;

    /** Echte JPEG van $w×$h pixels (GD), zoals een telefoonfoto. */
    private function jpeg(int $w, int $h): UploadedFile
    {
        $img = imagecreatetruecolor($w, $h);
        imagefilledrectangle($img, 0, 0, $w, $h, imagecolorallocate($img, 200, 30, 40));
        $path = tempnam(sys_get_temp_dir(), 'car') . '.jpg';
        imagejpeg($img, $path, 90);

        return new UploadedFile($path, 'telefoon.jpg', 'image/jpeg', null, true);
    }

    private function formData(Car $car, array $images): array
    {
        return [
            'brand' => $car->brand, 'model' => $car->model, 'year' => $car->year,
            'price' => (int) $car->price, 'mileage' => $car->mileage,
            'fuel_type' => 'Benzine', 'transmission' => 'Automaat', 'color' => 'Zwart',
            'status' => 'available', 'images' => $images,
        ];
    }

    public function test_large_photo_is_downscaled_to_webp(): void
    {
        Storage::fake('public');
        $car = Car::factory()->create(['status' => 'available']);

        $this->actingAs(User::factory()->create())
            ->patch(route('admin.cars.update', $car), $this->formData($car, [$this->jpeg(4000, 3000)]))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $path = $car->images()->first()->path;
        $this->assertStringEndsWith('.webp', $path);

        [$w, $h] = getimagesizefromstring(Storage::disk('public')->get($path));
        $this->assertSame(2000, max($w, $h));
        $this->assertSame(1500, min($w, $h)); // verhouding behouden
    }

    public function test_small_photo_is_not_upscaled(): void
    {
        Storage::fake('public');
        $car = Car::factory()->create(['status' => 'available']);

        $this->actingAs(User::factory()->create())
            ->patch(route('admin.cars.update', $car), $this->formData($car, [$this->jpeg(800, 600)]));

        [$w, $h] = getimagesizefromstring(Storage::disk('public')->get($car->images()->first()->path));
        $this->assertSame([800, 600], [$w, $h]);
    }

    /**
     * Telefoon die staand fotografeert: pixels liggend (400×300) + EXIF "Orientation 6".
     * GD schrijft geen EXIF, dus we zetten zelf een minimaal APP1-blok achter de SOI.
     */
    private function rotatedPhoneJpeg(): UploadedFile
    {
        $file = $this->jpeg(400, 300);
        $jpeg = file_get_contents($file->getRealPath());

        // TIFF little-endian, 1 IFD-regel: tag 0x0112 (Orientation), SHORT, waarde 6.
        $tiff = "II\x2A\x00\x08\x00\x00\x00" . "\x01\x00" . "\x12\x01\x03\x00\x01\x00\x00\x00\x06\x00\x00\x00" . "\x00\x00\x00\x00";
        $app1 = "Exif\x00\x00" . $tiff;
        file_put_contents($file->getRealPath(), substr($jpeg, 0, 2) . "\xFF\xE1" . pack('n', strlen($app1) + 2) . $app1 . substr($jpeg, 2));

        return new UploadedFile($file->getRealPath(), 'staand.jpg', 'image/jpeg', null, true);
    }

    public function test_phone_photo_is_turned_upright_from_its_exif_tag(): void
    {
        Storage::fake('public');
        $car = Car::factory()->create(['status' => 'available']);

        $this->actingAs(User::factory()->create())
            ->patch(route('admin.cars.update', $car), $this->formData($car, [$this->rotatedPhoneJpeg()]))
            ->assertSessionHasNoErrors();

        [$w, $h] = getimagesizefromstring(Storage::disk('public')->get($car->images()->first()->path));
        $this->assertSame([300, 400], [$w, $h]); // rechtop: staand
    }

    public function test_png_upload_is_stored_as_webp(): void
    {
        Storage::fake('public');
        $car = Car::factory()->create(['status' => 'available']);
        $path = tempnam(sys_get_temp_dir(), 'car') . '.png';
        imagepng(imagecreatetruecolor(800, 600), $path);

        $this->actingAs(User::factory()->create())
            ->patch(route('admin.cars.update', $car), $this->formData($car, [new UploadedFile($path, 'scan.png', 'image/png', null, true)]))
            ->assertSessionHasNoErrors();

        $stored = $car->images()->first()->path;
        $this->assertStringEndsWith('.webp', $stored);
        $this->assertSame(IMAGETYPE_WEBP, getimagesizefromstring(Storage::disk('public')->get($stored))[2]);
    }

    /** Te grote POST: Nederlandse uitleg i.p.v. de kale Engelse 413-pagina. */
    public function test_oversized_post_shows_dutch_page(): void
    {
        $car = Car::factory()->create(['status' => 'available']);

        $this->actingAs(User::factory()->create())
            ->call('PATCH', route('admin.cars.update', $car), [], [], [], ['CONTENT_LENGTH' => 10 * 1024 * 1024 * 1024])
            ->assertStatus(413)
            ->assertSee('Upload te groot');
    }
}
