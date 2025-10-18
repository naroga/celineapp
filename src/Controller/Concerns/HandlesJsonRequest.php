<?php

namespace App\Controller\Concerns;

use JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationListInterface;

trait HandlesJsonRequest
{
    /**
     * @return array<string, mixed>|JsonResponse
     */
    private function decodePayload(Request $request): array|JsonResponse
    {
        $content = $request->getContent();

        if ($content === '') {
            return new JsonResponse([
                'error' => 'empty_payload',
                'message' => 'Request body is required.',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return new JsonResponse([
                'error' => 'invalid_payload',
                'message' => 'Invalid JSON body.',
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!is_array($data)) {
            return new JsonResponse([
                'error' => 'invalid_payload',
                'message' => 'Invalid request body.',
            ], Response::HTTP_BAD_REQUEST);
        }

        return $data;
    }

    private function validationErrorResponse(ConstraintViolationListInterface $violations): JsonResponse
    {
        $messages = [];

        foreach ($violations as $violation) {
            $messages[] = $violation->getMessage();
        }

        return new JsonResponse([
            'error' => 'validation_failed',
            'messages' => $messages,
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
