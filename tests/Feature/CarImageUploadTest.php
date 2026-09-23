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
