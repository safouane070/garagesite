<?php

namespace App\Console\Commands;

use App\Models\Car;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RestoreDemoCatalog extends Command
{
    protected $signature = 'demo:restore-catalog {--snapshot= : Pad naar de SQLite-voorraad uit de build}';

    protected $description = 'Koppelt de gebouwde galerijen aan de blijvende demo-database zonder aanvragen of instellingen te vervangen.';

    public function handle(): int
    {
        $path = $this->option('snapshot') ?: database_path('database.sqlite');
        if (! is_file($path)) {
            $this->error('De gebouwde voorraad ontbreekt.');

            return self::FAILURE;
        }

        config(['database.connections.build_snapshot' => [
            'driver' => 'sqlite', 'database' => $path, 'prefix' => '',
        ]]);
        DB::purge('build_snapshot');
        $snapshot = DB::connection('build_snapshot');
        $count = 0;

        foreach ($snapshot->table('cars')->orderBy('id')->cursor() as $row) {
            if (! $row->dealer_slug) {
                continue;
            }

            DB::transaction(function () use ($row, $snapshot) {
                // Model-casts zetten SQLite JSON/booleans om naar de juiste Postgres-waarden.
                $source = (new Car)->setRawAttributes((array) $row);
                $attributes = $source->attributesToArray();
                unset($attributes['id']);
                $car = Car::firstOrCreate(['dealer_slug' => $row->dealer_slug], $attributes);

                $images = $snapshot->table('car_images')->where('car_id', $row->id)->orderBy('sort_order')->get();
                if ($images->isEmpty()) {
                    return;
                }

                // Nieuwe containers hebben nieuwe fotobestanden. Het auto-ID blijft gelijk,
                // zodat bestaande aanvragen bij dezelfde auto blijven horen.
                $car->images()->delete();
                foreach ($images as $image) {
                    $values = (array) $image;
                    unset($values['id'], $values['car_id']);
                    $car->images()->create($values);
                }
            });
            $count++;
        }

        $this->info("Galerijen van {$count} auto's gekoppeld; bestaande aanvragen en instellingen behouden.");

        return self::SUCCESS;
    }
}
