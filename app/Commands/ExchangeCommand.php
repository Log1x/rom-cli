<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;


class ExchangeCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'search {name* : The name of the item (required)}';

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
     * Headers
     *
     * @var array
     */
    protected $headers = ['Name', 'Price', 'Changes'];

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $response = $this->client()
            ->getAsync($this->api)
            ->then(
                function (ResponseInterface $response) {
                    return collect(json_decode($response->getBody()->getContents()))->filter();
                },

                function (RequestException $error) {
                    return collect();
                }
            )
            ->wait();

        if ($response->isEmpty()) {
            return $this->error('Error: No item found.');
        }

        $items = $response->map(function ($item) {
            return [
                $item->name,
                $this->price($item->global->latest),
                $this->progress($item->global->week->change)
            ];
        });

        $this->table($this->headers, $items);
    }

    /**
     * Client
     *
     * @return GuzzleHttp\Guzzle\Client
     */
    public function client()
    {
        return new Client([
            'query' => [
                'item'  => implode('%23', $this->argument('name')),
                'exact' => 'false'
            ]
        ]);
    }

    /**
     * Returns a mirrored Progress Bar based on the value.
     *
     * @param  integer $value
     * @param  integer $max
     * @param  integer $bar
     * @return string
     */
    public function progress($value = null, $max = 10, $bar = '▊')
    {
        if (empty($value)) {
            return sprintf(
                '<fg=black>%s</> <fg=yellow>N/A</>',
                str_repeat($bar, $max)
            );
        }

        $percent = ceil(abs($value / 100) * 5);
        $progress = str_repeat($bar, $percent);

        if ($value < 0) {
            return sprintf(
                '<fg=black>%s</><fg=red>%s</><fg=black>%s</> <fg=red>%s%%</>',
                str_repeat($bar, ($max / 2) - $percent),
                $progress,
                str_repeat($bar, $max / 2),
                $value
            );
        }

        return sprintf(
            '<fg=black>%s</><fg=green>%s</><fg=black>%s</> <fg=green>+%s%%</>',
            str_repeat($bar, $max / 2),
            $progress,
            str_repeat($bar, ($max / 2) - $percent),
            $value
        );
    }

    /**
     * Returns a formatted price.
     *
     * @param  integer $value
     * @return string
     */
    public function price($value)
    {
        if ($value <= 0) {
            return '<fg=yellow>N/A</>';
        }

        return number_format($value, 0) . 'z';
    }
}
