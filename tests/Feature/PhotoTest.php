<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\CarImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhotoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function jpeg(int $w, int $h): UploadedFile
    {
        $img = imagecreatetruecolor($w, $h);
        $path = tempnam(sys_get_temp_dir(), 'car') . '.jpg';
        imagejpeg($img, $path);

        return new UploadedFile($path, 'foto.jpg', 'image/jpeg', null, true);
    }

    private function carWithPhotos(int $n): Car
    {
        $car = Car::factory()->create(['status' => 'available']);
        for ($i = 0; $i < $n; $i++) {
            $path = "cars/{$car->slug}/{$i}.jpg";
            Storage::disk('public')->put($path, 'x');
            $car->images()->create(['path' => $path, 'is_primary' => $i === 0, 'sort_order' => $i]);
        }

        return $car;
    }

    private function order(Car $car): array
    {
        return $car->images()->get()->map(fn ($i) => basename($i->path))->all();
    }

    public function test_upload_creates_thumbnail_with_dimensions_and_card_uses_srcset(): void
    {
        $car = Car::factory()->create(['status' => 'available']);

        $this->actingAs(User::factory()->create())->patch(route('admin.cars.update', $car), [
            'brand' => $car->brand, 'model' => $car->model, 'year' => $car->year, 'price' => 20000,
            'mileage' => 1000, 'fuel_type' => 'Benzine', 'transmission' => 'Automaat', 'color' => 'Zwart',
            'status' => 'available', 'images' => [$this->jpeg(1600, 1200)],
        ])->assertSessionHasNoErrors();

        $image = $car->images()->first();
        $this->assertSame([1600, 1200], [$image->width, $image->height]);
        foreach (['xs_path' => 240, 'thumb_path' => 640, 'md_path' => 1024] as $column => $width) {
            [$w] = getimagesizefromstring(Storage::disk('public')->get($image->{$column}));
            $this->assertSame($width, $w, $column);
        }

        // Telefoon kiest 640/1024 i.p.v. de volledige foto; het strookje op de detailpagina de 240-versie.
        $srcset = $image->thumbUrl() . ' 640w, ' . Storage::disk('public')->url($image->md_path) . ' 1024w, ' . $image->url() . ' 1600w';
        $this->get(route('cars.index'))
            ->assertSee('srcset="' . $srcset . '"', false)
            ->assertSee('width="1600" height="1200"', false);

        // Verwijderen ruimt alle maten op.
        $paths = [$image->path, $image->xs_path, $image->thumb_path, $image->md_path];
        $image->deleteFiles();
        foreach ($paths as $p) {
            Storage::disk('public')->assertMissing($p);
        }
    }

    public function test_photo_can_be_moved_and_first_photo_is_the_cover(): void
    {
        $car = $this->carWithPhotos(3);
        $admin = User::factory()->create();
        $second = $car->images()->get()[1];

        $this->actingAs($admin)->patch(route('admin.cars.images.move', [$car, $second]), ['direction' => 'left']);

        $this->assertSame(['1.jpg', '0.jpg', '2.jpg'], $this->order($car));
        $this->assertTrue($car->images()->first()->is_primary);
        $this->assertSame(1, $car->images()->where('is_primary', true)->count());
    }

    public function test_setting_cover_moves_photo_to_the_front(): void
    {
        $car = $this->carWithPhotos(3);
        $third = $car->images()->get()[2];

        $this->actingAs(User::factory()->create())->patch(route('admin.cars.images.primary', [$car, $third]));

        $this->assertSame(['2.jpg', '0.jpg', '1.jpg'], $this->order($car));
    }

    public function test_deleting_photo_removes_thumbnail_and_keeps_order_tight(): void
    {
        $car = $this->carWithPhotos(2);
        $first = $car->images()->first();
        $first->update(['thumb_path' => "cars/{$car->slug}/thumbs/0.webp"]);
        Storage::disk('public')->put($first->thumb_path, 'x');

        $this->actingAs(User::factory()->create())->delete(route('admin.cars.images.destroy', [$car, $first]));

        Storage::disk('public')->assertMissing($first->thumb_path);
        Storage::disk('public')->assertMissing($first->path);
        $this->assertTrue($car->images()->first()->is_primary); // nieuwe eerste = omslag
    }

    public function test_backfill_command_adds_thumbnails_to_existing_photos(): void
    {
        $car = Car::factory()->create();
        $img = imagecreatetruecolor(1024, 768);
        ob_start();
        imagejpeg($img);
        Storage::disk('public')->put("cars/{$car->slug}/0.jpg", ob_get_clean());
        $image = $car->images()->create(['path' => "cars/{$car->slug}/0.jpg", 'is_primary' => true, 'sort_order' => 0]);

        $this->artisan('images:thumbs')->assertSuccessful();

        $image->refresh();
        $this->assertSame([1024, 768], [$image->width, $image->height]);
        Storage::disk('public')->assertExists($image->thumb_path);
    }

    public function test_detail_page_offers_fullscreen_gallery(): void
    {
        $car = $this->carWithPhotos(2);

        $this->get(route('cars.show', $car))
            ->assertSee('aria-label="Foto\'s schermvullend bekijken"', false)
            ->assertSee('role="dialog"', false)
            ->assertSee('x-teleport="body"', false);
    }
}
