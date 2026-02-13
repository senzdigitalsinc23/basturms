<?php

namespace App\Controllers\Api\v1;

use Services\Payments\PaymentService;
use App\Core\Request;
use App\Core\Response;
use PDO;

class PaymentController
{
    /**
     * Initiates a payment transaction.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function initiate(Request $request, Response $response): Response
    {
        $data = (array)$request->getPost();
        $gateway = new PaymentService((string)($data['gateway'] ?? 'momo'));
        // You may need to adapt the following line to your gateway interface
        $result = $gateway->charge((string)($data['phone'] ?? ''), (float)($data['amount'] ?? 0), (string)($data['reference'] ?? ''));

        // Save to DB (optional)
        $pdo = db();
        $stmt = $pdo->prepare("INSERT INTO payments (user_id, amount, reference, status, gateway, purpose) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['user_id'] ?? null,
            $data['amount'] ?? null,
            $data['reference'] ?? null,
            $result['status'] ?? null,
            $data['gateway'] ?? null,
            $data['purpose'] ?? null
        ]);

        $response->setContent((string)json_encode([
            'success' => ($result['status'] ?? 0) === 200,
            'message' => ($result['status'] ?? 0) === 200 ? 'Payment initiated' : 'Payment failed',
            'data' => $result
        ]));
        return $response;
    }

    /**
     * Verifies a payment transaction reference.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function verify(Request $request, Response $response): Response
    {
        $reference = (string)$request->getQuery('reference');
        $gatewayName = (string)($request->getQuery('gateway') ?? 'momo');
        $gateway = new PaymentService($gatewayName);
        // You may need to implement a verify method in your PaymentService or gateway
        $result = ['status' => 501, 'message' => 'Verification not implemented', 'reference' => $reference];

        // Update DB (if verification implemented)
        // $pdo = db();
        // $stmt = $pdo->prepare("UPDATE payments SET status = ? WHERE reference = ?");
        // $stmt->execute([$result['status'], $reference]);

        $response->setContent((string)json_encode([
            'success' => ($result['status'] ?? 0) === 200,
            'message' => $result['message'],
            'data' => $result
        ]));
        return $response;
    }
}
