// API Routes with documentation
Route::post('/login', [App\Http\Controllers\Api\AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [App\Http\Controllers\Api\AuthController::class, 'logout']);
    
    // Projects
    Route::apiResource('projects', App\Http\Controllers\Api\ProjectController::class);
    
    // Clients
    Route::apiResource('clients', App\Http\Controllers\Api\ClientController::class);
    
    // Equipment
    Route::get('/mechanical/equipment', [App\Http\Controllers\Api\MechanicalController::class, 'equipment']);
    Route::get('/mechanical/equipment/{equipment}', [App\Http\Controllers\Api\MechanicalController::class, 'showEquipment']);
    
    // Rental Agreements
    Route::apiResource('rentals/agreements', App\Http\Controllers\Api\RentalAgreementController::class);
});