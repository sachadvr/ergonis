<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\Mail\Client\GoogleOAuthClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/api/auth/google')]
final class GoogleAuthController extends AbstractController
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly Security $security,
        private readonly GoogleOAuthClient $googleOAuthClient,
        private readonly string $googleClientId,
        private readonly string $googleClientSecret,
        private readonly string $googleRedirectUri
    ) {
    }

    #[Route('/url', name: 'api_google_auth_url', methods: ['GET'])]
    public function getAuthUrl(): JsonResponse
    {
        $scopes = [
            'https://mail.google.com/',
            'https://www.googleapis.com/auth/userinfo.email',
            'https://www.googleapis.com/auth/gmail.send',
        ];

        $params = [
            'client_id' => $this->googleClientId,
            'redirect_uri' => $this->googleRedirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', $scopes),
            'access_type' => 'offline',
            'prompt' => 'consent',
        ];

        $url = 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query($params);

        return new JsonResponse(['url' => $url]);
    }

    #[Route('/callback', name: 'api_google_auth_callback', methods: ['POST'])]
    public function callback(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $code = $data['code'] ?? null;

        if (!$code) {
            return new JsonResponse(['error' => 'No code provided'], 400);
        }

        try {
            $response = $this->httpClient->request('POST', 'https://oauth2.googleapis.com/token', [
                'body' => [
                    'code' => $code,
                    'client_id' => $this->googleClientId,
                    'client_secret' => $this->googleClientSecret,
                    'redirect_uri' => $this->googleRedirectUri,
                    'grant_type' => 'authorization_code',
                ],
            ]);

            $tokenData = $response->toArray();

            // Get user email from Google to confirm identity
            $userResponse = $this->httpClient->request('GET', 'https://www.googleapis.com/oauth2/v3/userinfo', [
                'headers' => [
                    'Authorization' => 'Bearer '.$tokenData['access_token'],
                ],
            ]);
            $userData = $userResponse->toArray();
            $email = $userData['email'];

            $currentUser = $this->security->getUser();
            $resolvedUser = $this->googleOAuthClient->resolveUser(
                $currentUser instanceof User ? $currentUser : null,
                $email,
            );

            $this->googleOAuthClient->saveMailboxSettings(
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
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
