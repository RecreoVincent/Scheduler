<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MicrosoftGraphMailService
{
    public function isConfigured(): bool
    {
        return collect([
            config('services.microsoft_graph.tenant_id'),
            config('services.microsoft_graph.client_id'),
            config('services.microsoft_graph.client_secret'),
            config('services.microsoft_graph.sender_email'),
        ])->every(fn ($value) => filled($value));
    }

    public function send(string $recipient, string $subject, string $text): void
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Microsoft 365 email delivery is not configured.');
        }

        $tokenResponse = $this->http()->asForm()->post(
            sprintf(
                'https://login.microsoftonline.com/%s/oauth2/v2.0/token',
                rawurlencode((string) config('services.microsoft_graph.tenant_id'))
            ),
            [
                'client_id' => config('services.microsoft_graph.client_id'),
                'client_secret' => config('services.microsoft_graph.client_secret'),
                'scope' => 'https://graph.microsoft.com/.default',
                'grant_type' => 'client_credentials',
            ]
        )->throw();

        $accessToken = $tokenResponse->json('access_token');

        if (! is_string($accessToken) || $accessToken === '') {
            throw new RuntimeException('Microsoft Graph did not return an access token.');
        }

        $sender = rawurlencode((string) config('services.microsoft_graph.sender_email'));

        $this->http()
            ->withToken($accessToken)
            ->post("https://graph.microsoft.com/v1.0/users/{$sender}/sendMail", [
                'message' => [
                    'subject' => $subject,
                    'body' => [
                        'contentType' => 'Text',
                        'content' => $text,
                    ],
                    'toRecipients' => [[
                        'emailAddress' => ['address' => $recipient],
                    ]],
                ],
                'saveToSentItems' => true,
            ])
            ->throw();
    }

    private function http(): PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->connectTimeout(10)
            ->timeout(30)
            ->retry(2, 300);
    }
}
