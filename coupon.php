 // Update PaymentIntent metadata
        $paymentIntent = $this->updatePaymentIntentMetadata(
            $finalInvoice->payment_intent,
            $data['trainer_name'],
            $product,
            $amount,
            $stripeAccount
        );


=///


protected function updatePaymentIntentMetadata(string $paymentIntentId, string $trainerName, $product, int $amount, string $stripeAccount)
    {
