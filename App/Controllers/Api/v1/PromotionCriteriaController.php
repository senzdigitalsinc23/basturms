<?php

namespace App\Controllers\Api\v1;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\PromotionCriteriaService;
use App\Services\ValidationService;
use App\Services\LoggingService;
use App\Exceptions\ValidationException;
use OpenApi\Attributes as OA;

/**
 * Controller for managing promotion criteria configuration.
 */
#[OA\Tag(
    name: "Promotion Critera Setup",
    description: "API endpoints for setting up promotion criteria"
)]
class PromotionCriteriaController
{
    private PromotionCriteriaService $service;
    private LoggingService $loggingService;
    private ValidationService $validationService;

    /**
     * @param ValidationService $validationService
     * @param LoggingService $loggingService
     */
    public function __construct(ValidationService $validationService, LoggingService $loggingService)
    {
        $this->validationService = $validationService;
        $this->loggingService = $loggingService;
        $this->service = new PromotionCriteriaService($validationService);
    }

    /**
     * Creates new promotion criteria.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */


    #[OA\Post(
        path: "/promotion-criteria/create",
        summary: "Create a new promotion criteria",
        description: "Creates a new promotion criteria with specified parameters.",
        tags: ["Promotion Critera Setup"],
        security: [["ApiKeyAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["level_id", "min_score", "min_pass_mark", "min_electives"],
                properties: [
                    new OA\Property(property: "level_id", type: "string", example: "PS (for Pre School)"),
                    new OA\Property(property: "min_score", type: "integer", example: 50),
                    new OA\Property(property: "min_pass_mark", type: "integer", example: 40),
                    new OA\Property(property: "min_electives", type: "integer", example: 2),
                    new OA\Property(property: "number_of_terms", type: "integer", example: 3),
                    new OA\Property(property: "remarks", type: "string", example: "Standard promotion criteria")]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Promotion criteria created successfully"),
            new OA\Response(response: 400, description: "Validation error or criteria already exists")
        ]
    )]
    public function create(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();
            $userSession = (array)Session::get('user');
            $userId = (string)($userSession['user_id'] ?? 'system');
            
            $result = $this->service->createCriteria($data, $userId);
            
            $levelId = (string)($data['level_id'] ?? 'unknown');
            $this->loggingService->logAudit('promotion_criteria', "Created promotion criteria for level {$levelId}", $userId);
            $response->setContent((string)json_encode($result));
            return $response;

        } catch (ValidationException $e) {
            $response->setStatusCode(400);
            $response->setContent((string)json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->getErrors()
            ]));
            return $response;
        } catch (\Exception $e) {
            $this->loggingService->logAudit('error', "Failed to create promotion criteria: " . $e->getMessage());
            $response->setStatusCode(500);
            $response->setContent((string)json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
            return $response;
        }
    }

    /**
     * Lists all promotion criteria.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    #[OA\Post(
        path: "/promotion-criteria/list",
        summary: "List all promotion criteria",
        description: "Lists all promotion criteria with specified parameters.",
        tags: ["Promotion Critera Setup"],
        security: [["ApiKeyAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "level_id", type: "string", example: "PS (for Pre School)"),
                    new OA\Property(property: "min_score", type: "integer", example: 50),
                    new OA\Property(property: "min_pass_mark", type: "integer", example: 40),
                    new OA\Property(property: "min_electives", type: "integer", example: 2),
                    new OA\Property(property: "number_of_terms", type: "integer", example: 3),
                    new OA\Property(property: "remarks", type: "string", example: "Standard promotion criteria")]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Promotion criteria created successfully"),
            new OA\Response(response: 400, description: "Validation error or criteria already exists")
        ]
    )]
    public function list(Request $request, Response $response): Response
    {
        try {
            $result = $this->service->listCriteria();
            $response->setContent((string)json_encode($result));
            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setContent((string)json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
            return $response;
        }
    }

    /**
     * Updates an existing promotion criteria.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function update(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();
            $id = $data['id'] ?? null;
            $userSession = (array)Session::get('user');
            $userId = (string)($userSession['user_id'] ?? 'system');

            if (!$id) {
                $response->setStatusCode(400);
                $response->setContent((string)json_encode([
                    'success' => false,
                    'message' => 'Missing ID'
                ]));
                return $response;
            }

            $result = $this->service->updateCriteria($id, $data, $userId);
            
            $this->loggingService->logAudit('promotion_criteria', "Updated promotion criteria ID {$id}", $userId);
            $response->setContent((string)json_encode($result));
            return $response;

        } catch (ValidationException $e) {
            $response->setStatusCode(400);
            $response->setContent((string)json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->getErrors()
            ]));
            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setContent((string)json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
            return $response;
        }
    }

    /**
     * Deletes a promotion criteria.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function delete(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();
            $id = $data['id'] ?? null;
            $userSession = (array)Session::get('user');
            $userId = (string)($userSession['user_id'] ?? 'system');

            if (!$id) {
                $response->setStatusCode(400);
                $response->setContent((string)json_encode([
                    'success' => false,
                    'message' => 'Missing ID'
                ]));
                return $response;
            }

            $result = $this->service->deleteCriteria($id);
            
            $this->loggingService->logAudit('promotion_criteria', "Deleted promotion criteria ID {$id}", $userId);
            $response->setContent((string)json_encode($result));
            return $response;

        } catch (ValidationException $e) {
            $response->setStatusCode(404);
            $response->setContent((string)json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
            return $response;
        } catch (\Exception $e) {
            $this->loggingService->logAudit('error', "Failed to delete promotion criteria: " . $e->getMessage());
            $response->setStatusCode(500);
            $response->setContent((string)json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
            return $response;
        }
    }
}
