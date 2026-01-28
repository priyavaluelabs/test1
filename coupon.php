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

        } catch (ValidationException $e) {
            // Extract first validation message
            $message = collect($e->errors())
                ->flatten()
                ->first();

            $this->logger->warning(
                "promo_code.apply.validation_failed",
                $message,
                $e->errors(),
                $user->id ?? null
            );

            $this->logger->flush();

            return parent::respondError(
                $message,
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }  catch (HttpResponseException $e) {
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
===========

protected function applyPromoCodeToInvoice(?string $promoCode, string $productId, string $invoiceId, string $stripeAccount): void
    {
        if (!$promoCode) return;

        $promoCodes = $this->stripe()->promotionCodes->all(
            ['code' => $promoCode, 'active' => true, 'limit' => 1, 'expand' => ['data.coupon']],
            ['stripe_account' => $stripeAccount]
        );

        if (empty($promoCodes->data)) {
            $this->throwPromoError(__('klyp.nomergy::fod.invalid_promo_code'));
        }

        $promoCode = $promoCodes->data[0];
        $coupon    = $promoCode->coupon;

        // Metadata is ALWAYS string → decode it
        $allowedProducts = [];
        if (!empty($coupon->metadata->product_ids)) {
            $allowedProducts = array_map(
                'trim',
                explode(',', $coupon->metadata->product_ids)
            );
        }

        // Validate product
        if ($allowedProducts && !in_array($productId, $allowedProducts, true)) {
            $this->throwPromoError(
                __('klyp.nomergy::fod.promo_code_not_applicable_for_product')
            );
        }
        
        $this->stripe()->invoices->update(
            $invoiceId,
            ['discounts' => [['promotion_code' => $promoCodes->data[0]->id]]],
            ['stripe_account' => $stripeAccount]
        );
    }

