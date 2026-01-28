catch (ValidationException $e) {
    // Extract first validation message
    $message = collect($e->errors())
        ->flatten()
        ->first() ?? __('Invalid promo code.');

    $this->logger->warning(
        "promo_code.apply.validation_failed",
        $message,
        $e->errors(),
        $user->id ?? null
    );

    $this->logger->flush();

    return parent::respond([
        'status'  => 'error',
        'message' => $message,
        'errors'  => $e->errors(),
    ], Response::HTTP_UNPROCESSABLE_ENTITY);
}
