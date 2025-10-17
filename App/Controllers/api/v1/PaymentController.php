<?php

namespace App\Controllers\Api\v1;

use Services\Payments\PaymentService;
use App\Core\Request;
use App\Core\Response;
use PDO;

class PaymentController
{
    public function initiate(Request $request, Response $response)
    {
        $data = $request->getPost();
        $gateway = new PaymentService($data['gateway'] ?? 'momo');
        // You may need to adapt the following line to your gateway interface
        $result = $gateway->charge($data['phone'] ?? '', (float)($data['amount'] ?? 0), $data['reference'] ?? '');

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

        return $response->json([
            'success' => $result['status'] === 200,
            'message' => $result['status'] === 200 ? 'Payment initiated' : 'Payment failed',
            'data' => $result
        ]);
    }

    public function verify(Request $request, Response $response)
    {
        $reference = $request->getQuery('reference');
        $gatewayName = $request->getQuery('gateway') ?? 'momo';
        $gateway = new PaymentService($gatewayName);
        // You may need to implement a verify method in your PaymentService or gateway
        $result = ['status' => 501, 'message' => 'Verification not implemented', 'reference' => $reference];

        // Update DB (if verification implemented)
        // $pdo = db();
        // $stmt = $pdo->prepare("UPDATE payments SET status = ? WHERE reference = ?");
        // $stmt->execute([$result['status'], $reference]);

        return $response->json([
            'success' => $result['status'] === 200,
            'message' => $result['message'],
            'data' => $result
        ]);
    }
}
