<?php

namespace App\Http\Controllers;

use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {}

    /**
     * Pass the necessary data to the process order method
     * 
     * @param  Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->all();

        // Validate the payload and ensure it contains the required data
        if (!$this->isValidWebhookPayload($payload)) {
            return response()->json(['error' => 'Invalid webhook payload'], 400);
        }

        // Process the order using the OrderService
        try {
            $this->orderService->processOrder($payload);
        } catch (\Exception $e) {
            // Handle any exceptions that occur during order processing
            return response()->json(['error' => 'Failed to process the order'], 500);
        }

        return response()->json(['message' => 'Order processed successfully']);
    }

    /**
     * Validate the webhook payload.
     * 
     * @param  array $payload
     * @return bool
     */
    protected function isValidWebhookPayload(array $payload): bool
    {
        // Ensure the required fields are present in the payload
        if (!isset($payload['order_id'], $payload['subtotal_price'], $payload['merchant_domain'], $payload['discount_code'], $payload['customer_email'], $payload['customer_name'])) {
            return false;
        }
    
        // Perform additional validation if needed
        // For example, check if the values are of the expected types or meet certain criteria
    
        return true;
    }
}