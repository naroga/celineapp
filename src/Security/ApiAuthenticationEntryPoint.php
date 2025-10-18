<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

final class ApiAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        $acceptHeader = $request->headers->get('Accept', '');
        $expectsJson = str_contains($acceptHeader, 'application/json')
            || $request->isXmlHttpRequest()
            || $request->getPreferredFormat() === 'json';

        if ($expectsJson || str_starts_with($request->getPathInfo(), '/api/')) {
            return new JsonResponse([
                'error' => 'unauthenticated',
                'message' => 'Authentication required.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return new RedirectResponse($this->urlGenerator->generate('spa_login'));
    }
}
