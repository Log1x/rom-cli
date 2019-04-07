<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use GuzzleHttp\Client;

class ExchangeCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'exchange
                           {name : The name of the item (required)}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Searches ROM Exchange for a specified item.';

    /**
     * The ROM Exchange API.
     *
     * @var string
     */
    protected $api = 'https://www.romexchange.com/api';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $request = (new Client())->request('GET', $this->api, [
            'query' => [
                'item' => $this->argument('name'),
                'slim' => 'true',
                'exact' => 'false'
            ]
        ])->getBody();

        $response = collect(json_decode($request))->filter();

        if ($response->isEmpty()) {
            return $this->error('No item found.');
        }

        $items = $response->map(function ($item) {
            return [$item->name, number_format($item->global->latest, 0) . 'z'];
        });

        $this->table(['Name', 'Price'], $items);
    }

    /**
     * Define the command's schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
