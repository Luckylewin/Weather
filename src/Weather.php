<?php
/**
 * Created by PhpStorm.
 * User: lychee
 * Date: 2018/8/24
 * Time: 15:58
 */

namespace Luckywin\Weather;

use GuzzleHttp\Client;
use Luckywin\Weather\Exceptions\HttpException;
use Luckywin\Weather\Exceptions\InvalidArgumentException;

class Weather
{
    //202d72ee58354fe19db1ebaa14ca11dc
    protected $key;

    protected $guzzleOptions = [];

    /**
     * Weather constructor.
     * @param string $key
     */
    public function __construct(string $key)
    {
        $this->key = $key;
    }

    public function getHttpClient()
    {
        return new Client($this->guzzleOptions);
    }

    /**
     * @param mixed $options
     */
    public function setGuzzleOptions(array $options): void
    {
        $this->guzzleOptions = $options;
    }

    public function getWeather($city, string $type = 'base', string $format = 'json')
    {
        $url = 'https://restapi.amap.com/v3/weather/weatherInfo';

        if (!in_array(strtolower($type), ['base', 'all'])) {
            throw new InvalidArgumentException('Invalid type value(base/all): ' . $type);
        }

        if (!in_array(strtolower($format), ['json', 'xml'])) {
            throw new InvalidArgumentException('Invalid response format(json/xml): ' . $format);
        }

        $query = array_filter([
            'key' => $this->key,
            'city' => $city,
            'extensions' => $type,
            'output' => $format
        ]);

        try {
            $response = $this->getHttpClient()->get($url, [
                    'query' => $query
            ])->getBody()->getContents();

            return 'json' === $format ? \json_decode($response, true) : $response;
        } catch (\Exception $e) {
            throw new HttpException($e->getMessage(), $e->getCode(), $e);
        }

    }

}