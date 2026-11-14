<?php

declare(strict_types=1);

namespace App\Socialite;

use GuzzleHttp\RequestOptions;
use JsonException;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User;
use RuntimeException;

class HiveProvider extends AbstractProvider
{
    /**
     * @param  string|null  $state
     */
    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase($this->endpoint('/oauth/authorize'), $state);
    }

    protected function getTokenUrl(): string
    {
        return $this->endpoint('/oauth/token');
    }

    /**
     * @param  string  $token
     * @return array<string, mixed>
     */
    protected function getUserByToken($token): array
    {
        if (! is_string($token) || $token === '') {
            throw new RuntimeException('Hive Hub returned an invalid OAuth access token.');
        }

        try {
            $profile = json_decode(
                (string) $this->getHttpClient()->get($this->endpoint('/api/user'), [
                    RequestOptions::HEADERS => [
                        'Accept' => 'application/json',
                        'Authorization' => 'Bearer '.$token,
                    ],
                ])->getBody(),
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
        } catch (JsonException $exception) {
            throw new RuntimeException('Hive Hub returned an invalid user profile response.', previous: $exception);
        }

        if (! is_array($profile)) {
            throw new RuntimeException('Hive Hub returned an invalid user profile response.');
        }

        if (isset($profile['data']) && is_array($profile['data'])) {
            return $profile['data'];
        }

        return $profile;
    }

    protected function mapUserToObject(array $user): User
    {
        return (new User)->setRaw($user)->map([
            'id' => $user['id'] ?? null,
            'name' => $user['name'] ?? null,
            'email' => $user['email'] ?? null,
        ]);
    }

    private function endpoint(string $path): string
    {
        $baseUrl = rtrim((string) config('services.hive.base_url'), '/');

        return $baseUrl.$path;
    }
}
