<?php

namespace App\Controllers;

use App\Core\Queue;
use App\Core\Request;
use App\Core\Response;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payments\MomoService;
use Jobs\GenerateReportJob;
use Repositories\TransactionRepository;
use Services\EmailService;
use Services\Payments\PaymentFactory;
use Services\Payments\PaymentService;
use Services\Reports\ReportFactory;
use Services\SMSService;

class TestController
{
    protected EmailService $mail;
    protected SMSService $sms;
    protected Response $response;
    protected TransactionRepository $transactions;
    protected MomoService $momo;

    public function __construct(MoMoService $momo, TransactionRepository $transactions, SMSService $sms, Response $response) {
        $this->response = $response;
        $this->sms = $sms;
        $this->momo = $momo;
        $this->transactions = $transactions;
    }

    public function webhook(Request $request, Response $response)
    {
        $payload = $request()->json(); // assuming you added a json() method in Request class

        // Example payload from MoMo
        // {
        //   "transactionId": "momo_64f9e56c...",
        //   "status": "success",
        //   "amount": "50",
        //   "reference": "123456",
        //   "reason": null
        // }

        $transactionId = $payload['transactionId'] ?? null;
        $status = $payload['status'] ?? 'failed';
        $reference = $payload['reference'] ?? null;
        $reason = $payload['reason'] ?? null;

        if (!$transactionId) {
            return $response()->json([
                'success' => false,
                'message' => 'Invalid webhook payload'
            ], 400);
        }

        $payment = Payment::where('transaction_id', $transactionId)->first();

        if (!$payment) {
            return $response()->json([
                'success' => false,
                'message' => 'Transaction not found'
            ], 404);
        }

        $payment->status = $status;
        $payment->reference = $reference;
        $payment->reason = $reason;
        $payment->save();

        return $response()->json([
            'success' => true,
            'message' => "Payment status updated to {$status}"
        ]);
    }


    public function mail()
    {
        $sent = $this->mail->send('senzu.dogi23@gmail.com', 'You are receiving a test mail', 'Mail Testing');

        if ($sent) {
            return json_encode(['success' => true, 'message' => 'Email sent successfully']);
        }
        return json_encode(['success' => false, 'message' => 'Email not sent']);
    }

    
    public function sms()
    {
        $sent = $this->sms->send('+233242737120', 'You are receiving a test SMS');

        if ($sent) {
            return json_encode(['success' => true, 'message' => 'Email sent successfully']);
        }
        return json_encode(['success' => false, 'message' => 'Email not sent']);
    }

    public function pdfReport()
    {
        $data = [
            ['ID', 'Name', 'Score'],
            [1, 'Alice', 95],
            [2, 'Bob', 88],
        ];

    $type = $_GET['type'] ?? 'pdf';
    $filePath = __DIR__ . "/../../storage/report_$type." . $type;

    $job = new GenerateReportJob($data, $type, $filePath);

    $queue = new Queue();
    $queue->push($job);

    return "Report generation queued. Check later at /report/download?type=$type";
    }

    public function pay(Request $request)
    {
        $provider  = $request->input('provider'); // mtn, vodafone, airteltigo, paystack
        $phone     = $request->input('phone');
        $amount    = $request->input('amount');
        $reference = uniqid('txn_');

        $payment = new PaymentService($provider);
        $result = $payment->charge($phone, $amount, $reference);

        return response()->json($result);
    }

     public function stripe(Request $request)
    {
        $payload = @file_get_contents('php://input');
        $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        $endpointSecret = getenv('STRIPE_WEBHOOK_SECRET');

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sigHeader, $endpointSecret
            );
        } catch (\Exception $e) {
            http_response_code(400);
            return json_encode(['error' => 'Invalid payload/signature']);
        }

        $object = $event->data->object;
        $transactionId = $object->id ?? uniqid('stripe_', true);
        $amount = isset($object->amount) ? $object->amount / 100 : 0;
        $currency = strtoupper($object->currency ?? 'USD');

        if ($event->type === 'payment_intent.succeeded') {
            $this->transactions->create([
                'gateway' => 'stripe',
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'currency' => $currency,
                'status' => 'succeeded',
                'payload' => json_encode($object),
            ]);
        } elseif ($event->type === 'payment_intent.payment_failed') {
            $this->transactions->create([
                'gateway' => 'stripe',
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'currency' => $currency,
                'status' => 'failed',
                'payload' => json_encode($object),
            ]);
        }

        http_response_code(200);
        return json_encode(['status' => 'recorded']);
    }

    public function paypal(Request $request)
    {
        $payload = json_decode(file_get_contents("php://input"), true);
        $transactionId = $payload['resource']['id'] ?? uniqid('paypal_', true);
        $amount = $payload['resource']['amount']['value'] ?? 0;
        $currency = $payload['resource']['amount']['currency_code'] ?? 'USD';
        $eventType = $payload['event_type'] ?? 'unknown';

        $status = match ($eventType) {
            'PAYMENT.CAPTURE.COMPLETED' => 'succeeded',
            'PAYMENT.CAPTURE.DENIED' => 'failed',
            default => 'pending',
        };

        $this->transactions->create([
            'gateway' => 'paypal',
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'currency' => $currency,
            'status' => $status,
            'payload' => json_encode($payload),
        ]);

        http_response_code(200);
        return json_encode(['status' => 'recorded']);
    }

     public function initiateMoMo(Request $request)
    {
        $phone = $request->input('phone');
        $amount = $request->input('amount');
        $reference = uniqid('momo_');

        $result = $this->momo->requestToPay($phone, $amount, $reference);

        // log transaction
        $this->transactions->create([
            'gateway' => 'mtn_momo',
            'transaction_id' => $result['referenceId'],
            'amount' => $amount,
            'currency' => getenv('MOMO_CURRENCY'),
            'status' => 'pending',
            'payload' => json_encode($result['body'])
        ]);

        return response()->json($result);
    }

    public function momoPay(Request $request, Response $response)
    {
        $transactionId = uniqid('txn_');
        $amount = $request->input('amount');
        $phone = $request->input('phone');

        try {
            $result = $this->momo->requestToPay($transactionId, $amount, $phone);
            return $response()->json([
                'success' => true,
                'message' => 'Payment request sent',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return $response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
