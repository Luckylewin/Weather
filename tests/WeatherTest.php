<?php

/*
 * This file is part of the luckywin/weather.
 *
 * (c) luckywin <876505905@qq.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Luckywin\Weather\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Luckywin\Weather\Exceptions\HttpException;
use Luckywin\Weather\Exceptions\InvalidArgumentException;
use Luckywin\Weather\Weather;
use Mockery\Matcher\AnyArgs;
use PHPUnit\Framework\TestCase;
use Mockery;

class WeatherTest extends TestCase
{
    public function testGetWeather()
    {
        //测试 json 格式
        // 创建模拟接口响应值
        $response = new Response(200, [], '{"success": true}');

        // 创建模拟 http client。
        $client = \Mockery::mock(Client::class);

        // 指定将会产生的行为 (在后续的测试中将会按下面的参数来调用) 。
        $client->allows()->get('https://restapi.amap.com/v3/weather/weatherInfo', [
            'query' => [
                'key' => 'mock-key',
                'city' => '深圳',
                'output' => 'json',
                'extensions' => 'base',
            ],
        ])->andReturn($response);

        // 将 getHttpClient 方法替换为上面创建的 http client 为返回值的模拟方法
        $w = \Mockery::mock(Weather::class, ['mock-key'])->makePartial();
        // $client 是上面创建的模拟实例
        $w->allows()->getHttpClient()->andReturn($client);

        // 然后调用 `getWeather` 方法, 并断言返回值为模拟的返回值
        $this->assertSame(['success' => true], $w->getWeather('深圳'));

        //2.测试 xml 格式

        // 创建模拟接口响应值
        $response = new Response(200, [], '<hello>content</hello>');

        // 创建模拟 http client
        $client->allows()->get('https://restapi.amap.com/v3/weather/weatherInfo', [
            'query' => [
                'key' => 'mock-key',
                'city' => '深圳',
                'extensions' => 'all',
                'output' => 'xml',
            ],
        ])->andReturn($response);

        $weather = Mockery::mock(Weather::class, ['mock-key'])->makePartial();
        $weather->allows()->getHttpClient()->andReturn($client);
        $this->assertSame('<hello>content</hello>', $w->getWeather('深圳', 'all', 'xml'));
    }

    public function testGetWeatherWithGuzzleRuntimeException()
    {
        $client = Mockery::mock(Client::class);
        $client->allows()
               ->get(new AnyArgs()) // 上面已经验证过了 所以用任意参数
               ->andThrow(new \Exception('request timeout')); // 调用get的时候会发生异常

        $weather = Mockery::mock(Weather::class, ['mock-key'])->makePartial();
        $weather->allows()->getHttpClient()->andReturn($client);

        // 接着断言调用时会产生异常
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('request timeout');

        $weather->getWeather('深圳');
    }

    public function testGetHttpClient()
    {
        $weather = new Weather('mock-key');
        // 断言返回结果 GuzzleHttp\ClientInterface 的实例
        $this->assertInstanceOf(ClientInterface::class, $weather->getHttpClient());
    }

    public function testSetGuzzleOptions()
    {
        $weather = new Weather('mock-key');

        $this->assertNull($weather->getHttpClient()->getConfig('timeout'));

        // 设置参数
        $weather->setGuzzleOptions(['timeout' => 5000]);

        // 设置参数后 值为5000
        $this->assertSame(5000, $weather->getHttpClient()->getConfig('timeout'));
    }

    // 检查 type 参数
    public function testGetWeatherWithInvalidType()
    {
        $w = new Weather('mock-key');

        // 断言会抛出此类异常
        $this->expectException(InvalidArgumentException::class);

        // 断言异常消息为 'Invalid type value(base/all): foo'
        $this->expectExceptionMessage('Invalid type value(base/all): foo');

        $w->getWeather('深圳', 'foo');

        $this->fail('Faild to asset getWeather throw exception with invalid argument.');
    }

    // 检查 format 参数
    public function testGetWeatherWithInvalidFormat()
    {
        $w = new Weather('mock-key');

        // 断言会抛出此异常类
        $this->expectException(InvalidArgumentException::class);

        // 断言异常消息为 'Invalid response format: array'
        $this->expectExceptionMessage('Invalid response format(json/xml): array');

        // 因为支持的格式为 xml/json，所以传入 array 会抛出异常
        $w->getWeather('深圳', 'base', 'array');

        // 如果没有抛出异常，就会运行到这行，标记当前测试没成功
        $this->fail('Faild to asset getWeather throw exception with invalid argument.');
    }

    public function testGetLiveWeather()
    {
        // 将 getWeather 接口模拟为返回固定内容，以测试参数传递是否正确
        $weather = Mockery::mock(Weather::class, ['mock-key'])->makePartial();
        $weather->expects()->getWeather('深圳', 'base', 'json')->andReturn(['success' => true]);

        // 断言 并返回正确
        $this->assertSame(['success' => true], $weather->getLiveWeather('深圳'));
    }

    public function testGetForecastsWeather()
    {
        // 将 getWeather 接口模拟为返回固定内容，以测试参数传递是否正确
        $weather = Mockery::mock(Weather::class, ['mock-key'])->makePartial();
        $weather->expects()->getWeather('深圳', 'all', 'json')->andReturn(['success' => true]);

        // 断言 并返回正确
        $this->assertSame(['success' => true], $weather->getForecastsWeather('深圳'));
    }
}
