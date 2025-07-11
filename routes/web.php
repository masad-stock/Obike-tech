// Feature Management Routes
Route::middleware(['auth', 'can:manage-features'])->prefix('admin')->group(function () {
    Route::get('/features', [FeatureController::class, 'index'])->name('features.index');
    Route::get('/features/{feature}', [FeatureController::class, 'show'])->name('features.show');
    Route::put('/features/{feature}', [FeatureController::class, 'update'])->name('features.update');
    Route::post('/features/toggle-user', [FeatureController::class, 'toggleForUser'])->name('features.toggle-user');
    Route::get('/features/{feature}/reset', [FeatureController::class, 'resetFeature'])->name('features.reset');
});

// API Documentation
Route::get('/api/documentation', [App\Http\Controllers\Api\ApiDocController::class, 'index'])
    ->name('api.documentation');
Route::get('/api/documentation/download', [App\Http\Controllers\Api\ApiDocController::class, 'download'])
    ->name('api.documentation.download');

// Payment routes
Route::get('/payments/{agreement}/form', [App\Http\Controllers\PaymentController::class, 'showPaymentForm'])
    ->name('payments.form');
Route::post('/payments/{agreement}/process', [App\Http\Controllers\PaymentController::class, 'processPayment'])
    ->name('payments.process');
Route::get('/payments/history', [App\Http\Controllers\PaymentController::class, 'paymentHistory'])
    ->name('payments.history');

// M-PESA Payment routes
Route::get('/payments/{agreement}/mpesa', [App\Http\Controllers\MpesaController::class, 'showPaymentForm'])
    ->name('mpesa.form');
Route::post('/payments/{agreement}/mpesa/initiate', [App\Http\Controllers\MpesaController::class, 'initiatePayment'])
    ->name('mpesa.initiate');
Route::get('/payments/mpesa/status/{transaction}', [App\Http\Controllers\MpesaController::class, 'checkStatus'])
    ->name('mpesa.status');


