<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\Mail\Client\MicrosoftOAuthClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/api/auth/microsoft')]
final class MicrosoftAuthController extends AbstractController
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly Security $security,
        private readonly MicrosoftOAuthClient $microsoftOAuthClient,
        private readonly string $azureClientId,
        private readonly string $azureClientSecret,
        private readonly string $azureRedirectUri
    ) {
    }

    #[Route('/url', name: 'api_microsoft_auth_url', methods: ['GET'])]
    public function getAuthUrl(): JsonResponse
    {
        $scopes = $this->getRequestedScopes();

        $params = [
            'client_id' => $this->azureClientId,
            'redirect_uri' => $this->azureRedirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', $scopes),
            'response_mode' => 'query',
        ];

        $url = 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize?'.http_build_query($params);

        return new JsonResponse(['url' => $url]);
    }

    #[Route('/callback', name: 'api_microsoft_auth_callback', methods: ['POST'])]
    public function callback(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $code = $data['code'] ?? null;

        if (!$code) {
            return new JsonResponse(['error' => 'No code provided'], 400);
        }

        try {
            $response = $this->httpClient->request('POST', 'https://login.microsoftonline.com/common/oauth2/v2.0/token', [
                'body' => [
                    'code' => $code,
                    'client_id' => $this->azureClientId,
                    'client_secret' => $this->azureClientSecret,
                    'redirect_uri' => $this->azureRedirectUri,
                    'grant_type' => 'authorization_code',
                    'scope' => implode(' ', $this->getRequestedScopes()),
                ],
            ]);

            $tokenData = $response->toArray();

            $email = null;
            if (isset($tokenData['id_token'])) {
                $parts = explode('.', $tokenData['id_token']);
                if (count($parts) >= 2) {
                    $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
                    $email = $payload['email'] ?? $payload['preferred_username'] ?? $payload['upn'] ?? null;
                }
            }

            if (!$email) {
                try {
                    $userResponse = $this->httpClient->request('GET', 'https://graph.microsoft.com/v1.0/me', [
                        'headers' => [
                            'Authorization' => 'Bearer '.$tokenData['access_token'],
                        ],
                    ]);
                    $userData = $userResponse->toArray();
                    $email = $userData['mail'] ?? $userData['userPrincipalName'];
                } catch (\Throwable $e) {
                    throw new \RuntimeException('Could not determine user email from Microsoft: '.$e->getMessage());
                }
            }

            $currentUser = $this->security->getUser();
            $resolvedUser = $this->microsoftOAuthClient->resolveUser(
                $currentUser instanceof User ? $currentUser : null,
                $email,
            );

            $this->microsoftOAuthClient->saveMailboxSettings(
                $resolvedUser['user'],
                $email,
                $tokenData['access_token'],
                $tokenData['refresh_token'] ?? null,
                (int) $tokenData['expires_in'],
            );

            return new JsonResponse([
                'success' => true,
                'email' => $email,
                'token' => $resolvedUser['token'],
                'user' => [
                    'id' => $resolvedUser['user']->getId(),
                    'email' => $resolvedUser['user']->getEmail(),
                ],
            ]);
        } catch (\Throwable $e) {
            error_log('Microsoft Auth Error: '.$e->getMessage());
            if ($e instanceof HttpExceptionInterface) {
                $response = $e->getResponse();
                $body = $response->getContent(false);
                error_log('Response status: '.$response->getStatusCode());
                error_log('Response headers: '.json_encode($response->getHeaders(false)));
                error_log('Response body: '.$body);

                $decodedBody = json_decode($body, true);

                return new JsonResponse([
                    'error' => $e->getMessage(),
                    'status' => $response->getStatusCode(),
                    'response' => is_array($decodedBody) ? $decodedBody : $body,
                ], $response->getStatusCode());
            }

            return new JsonResponse([
                'error' => $e->getMessage(),
                'exception' => $e::class,
            ], 500);
        }
    }

    /**
     * @return string[]
     */
    private function getRequestedScopes(): array
    {
        return [
            'openid',
            'email',
            'profile',
            'offline_access',
            'https://outlook.office.com/IMAP.AccessAsUser.All',
            'https://outlook.office.com/SMTP.Send',
        ];
    }
}
