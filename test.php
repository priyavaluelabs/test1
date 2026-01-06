 try {
            $user = User::findOrFail($this->userId);
            $customerService = new PTBillingCustomerService;
            $data = ['user_id' => $this->userId, 'limit' => $this->limit];
            $payments = $customerService->getPaymentHistory($data);

            // Ensure the exports directory exists in storage
            // Create it with proper permissions if it doesn't exist
            // This prevents errors when trying to save PDF files
            $exportPath = storage_path('app/exports');
            if (!file_exists($exportPath)) {
                mkdir($exportPath, 0755, true);
            }

            $pdfPath = $exportPath . '/stripe_payment_history_'.$user->id.'.pdf';
            
            Pdf::loadView('pdf.stripe-payment-history', compact('user', 'payments'))->save($pdfPath);

            $mailerService->sendReport($this->email, 'Stripe Payment History', $pdfPath);
            
        } catch(\Exception $e) {
            Log::error('Error generating stripe payment history: '.$e->getMessage(), [
                'user_id' => $this->userId,
                'email' => $this->email
            ]);
        }  