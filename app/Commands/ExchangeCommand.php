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
    protected $headers = ['Region', 'Name', 'Type', 'Price', 'Changes'];

    /**
     * Items
     *
     * @var array
     */
    protected $items = [
        '1'  => 'Weapon',
        '2'  => 'Off-hand',
        '3'  => 'Armor',
        '4'  => 'Garment',
        '5'  => 'Footgear',
        '6'  => 'Accessory',
        '7'  => 'Blueprint',
        '8'  => 'Potion / Effect',
        '9'  => 'Refine',
        '10' => 'Scroll / Album',
        '11' => 'Material',
        '12' => 'Holiday Material',
        '13' => 'Pet Material',
        '14' => 'Premium',
        '15' => 'Costume',
        '16' => 'Head',
        '17' => 'Face',
        '18' => 'Back',
        '19' => 'Mouth',
        '20' => 'Tail',
        '21' => 'Weapon Card',
        '22' => 'Off-hand Card',
        '23' => 'Armor Card',
        '24' => 'Garment Card',
        '25' => 'Shoe Card',
        '26' => 'Accessory Card',
        '27' => 'Headwear Card'
    ];

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

        $items = $response->map(function ($item) use ($response) {
            return [
                "<fg=blue>Global</>\n" .
                "<fg=yellow>SEA</>" . ($item->name !== $response->last()->name ? "\n" : ''),
                $this->highlight($this->name(), $item->name),
                $this->type($item->type),
                "<fg=blue>{$this->price($item->global->latest)}</>" . "\n" .
                "<fg=yellow>{$this->price($item->sea->latest)}</>",
                $this->progress($item->global->week->change) . "\n" .
                $this->progress($item->sea->week->change)
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
                'item'  => $this->name(),
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
    public function progress($value = null, $max = 10, $bar = '█')
    {
        if (empty($value)) {
            return sprintf(
                '<fg=black>%s</> <fg=red>N/A</>',
                str_repeat($bar, $max)
            );
        }

        $percent = ceil(abs($value / 100) * $max / 2);
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
     * Returns a highlighted word from a string.
     *
     * @param  string $value
     * @return string
     */
    public function highlight($word, $string)
    {
        $highlight = substr($string, stripos($string, $word), strlen($word));

        return str_ireplace($word, "<fg=blue>{$highlight}</>", $string);
    }

    /**
     * Returns the name seperated by an HTML encoded space.
     *
     * @return string
     */
    public function name()
    {
        return implode('%23', $this->argument('name'));
    }

    /**
     * Returns an item type.
     *
     * @param  integer $type
     * @return string
     */
    public function type($type)
    {
        return $this->items[$type];
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
            return '<fg=red>N/A</>';
        }

        return number_format($value, 0) . 'z';
    }
}
