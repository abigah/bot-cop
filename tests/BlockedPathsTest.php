<?php

namespace Abigah\BotCop\Tests;

use Abigah\BotCop\Middleware\BotCopMiddleware;
use Abigah\BotCop\Services\ServiceContract;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;

class BlockedPathsTest extends TestCase
{
    /**
     * From a documentation range, so it is on no allowlist.
     */
    protected const IP = '203.0.113.5';

    /**
     * Records every IP the middleware asks to ban.
     */
    protected object $bans;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bans = new class implements ServiceContract
        {
            /** @var array<int, string> */
            public array $ips = [];

            public function addIp(string $ip, string $host, string $path)
            {
                $this->ips[] = $ip;

                return response('Forbidden', 403);
            }

            public function removeIps()
            {
            }
        };

        $this->app->instance('bot-cop.recording-ban-service', $this->bans);

        config([
            // Rate limiter state that starts empty for every test.
            'cache.default' => 'array',
            'bot-cop.enabled' => ['recording'],
            'bot-cop.services.recording.service' => 'bot-cop.recording-ban-service',
        ]);
    }

    /**
     * Put one request through the middleware, answered by the app with the
     * given status. It is built from server variables, the way a web server
     * hands a request on, because Request::create() refuses a raw backslash.
     */
    protected function send(string $path, int $status = 404): Response
    {
        $request = new Request(server: [
            'REQUEST_METHOD' => 'GET',
            'HTTPS' => 'on',
            'HTTP_HOST' => 'example.com',
            'REQUEST_URI' => $path,
            'REMOTE_ADDR' => self::IP,
        ]);

        return (new BotCopMiddleware())->handle($request, fn () => response('from the app', $status));
    }

    /**
     * Ordinary files whose names contain x00–x15 as part of their dimensions.
     *
     * @return array<string, array{string}>
     */
    public static function filenamesWithDimensions(): array
    {
        return [
            'hero image (x10)' => ['/assets/hero-1920x1080.jpg'],
            'iOS touch icon (x12)' => ['/apple-touch-icon-120x120-precomposed.png'],
            'iPad touch icon (x15)' => ['/apple-touch-icon-152x152.png'],
            'Windows tile (x14)' => ['/favicons/ms-icon-144x144.png'],
            'old WordPress upload (x10)' => ['/wp-content/uploads/IMG_9222-724x1024.png'],
        ];
    }

    /**
     * Hex escapes as a probe sends them, raw or percent-encoded.
     *
     * @return array<string, array{string}>
     */
    public static function hexEscapes(): array
    {
        return [
            'raw backslash' => ['/\\x00'],
            'percent-encoded backslash' => ['/%5Cx0A'],
            'lowercase percent-encoding' => ['/cgi-bin/%5cx15'],
        ];
    }

    #[DataProvider('filenamesWithDimensions')]
    public function test_a_missing_file_with_dimensions_in_its_name_is_not_banned(string $path): void
    {
        $response = $this->send($path);

        $this->assertSame([], $this->bans->ips);

        // Handed back untouched, as any other 404 would be.
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('from the app', $response->getContent());
    }

    #[DataProvider('hexEscapes')]
    public function test_a_hex_escape_in_a_missing_path_is_banned(string $path): void
    {
        $response = $this->send($path);

        $this->assertSame([self::IP], $this->bans->ips);
        $this->assertSame(429, $response->getStatusCode());
    }

    public function test_other_blocked_paths_still_ban_on_the_first_404(): void
    {
        $this->send('/.env');

        $this->assertSame([self::IP], $this->bans->ips);
    }

    public function test_a_percent_encoded_blocked_path_is_banned(): void
    {
        $this->send('/%2Eenv');

        $this->assertSame([self::IP], $this->bans->ips);
    }

    public function test_a_blocked_path_that_does_not_404_is_left_alone(): void
    {
        $response = $this->send('/%5Cx00', 200);

        $this->assertSame([], $this->bans->ips);
        $this->assertSame(200, $response->getStatusCode());
    }
}
