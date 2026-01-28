 public function applyPromoCode(PTBillingApplyPromocodeRequest $request)
    {
        $user = parent::getAuth();

        $this->logger->info(
            "promo_code.apply",
            "Applying promo code",
            [
                'trainer_id' => $this->trainer->id,
                'product_id'  => $request->product_id,
                'promo_code' => $request->promo_code,
                'invoice_id'  => $request->invoice_id,
            ],
            $user->id ?? null
        );

        try {
            $data = [
                'stripe_account_id' => $this->trainer->stripe_account_id,
                'promo_code'        => $request->promo_code,
                'product_id'        => $request->product_id,
                'invoice_id'        => $request->invoice_id,
            ];

            // Only validate promo & calculate discount
            $response = $this->customerService->applyPromoCode($data);

            $this->logger->info(
                "promo_code.apply.success",
                "Promo code applied successfully",
                [],
                $user->id ?? null
            );

            $this->logger->flush();

            return parent::respond([
                'status'        => 'success',
                'promo_details' => $response['promo_details'],
                'currency'      => $this->getCurrencySymbol($this->trainer)
            ], Response::HTTP_OK);

        } catch (HttpResponseException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error(
                "promo_code.apply.error",
                $e->getMessage(),
                [],
                $user->id ?? null
            );

            $this->logger->flush();

            return parent::respondError(
                __('klyp.nomergy::fod.stripe_unexpected_error'),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }
