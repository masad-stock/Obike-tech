<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Pennant\Feature;
use Laravel\Pennant\PennantFeaturesTable;
use Illuminate\Support\Facades\Gate;

class FeatureController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!Gate::allows('manage-features')) {
                abort(403, 'Unauthorized action.');
            }
            return $next($request);
        });
    }

    /**
     * Display a listing of the features.
     */
    public function index()
    {
        // Get all defined features
        $features = [
            'enhanced-rental-dashboard' => [
                'name' => 'Enhanced Rental Dashboard',
                'description' => 'Advanced analytics and insights for the rental dashboard',
            ],
            'streamlined-procurement' => [
                'name' => 'Streamlined Procurement',
                'description' => 'New procurement workflow with automated reordering and supplier analytics',
            ],
            'mobile-optimized-ui' => [
                'name' => 'Mobile-Optimized UI',
                'description' => 'Responsive interface optimized for mobile devices',
            ],
            'advanced-reporting' => [
                'name' => 'Advanced Reporting',
                'description' => 'Additional detailed reports and analytics',
            ],
            'maintenance-scheduling' => [
                'name' => 'Maintenance Scheduling',
                'description' => 'Equipment maintenance scheduling and tracking',
            ],
        ];

        // Get feature usage statistics
        $featureStats = DB::table('features')
            ->select('name', DB::raw('COUNT(*) as total_users'))
            ->groupBy('name')
            ->get()
            ->keyBy('name');

        // Get total users count
        $totalUsers = User::count();

        // Combine feature definitions with usage statistics
        foreach ($features as $key => &$feature) {
            $feature['total_users'] = $featureStats[$key]->total_users ?? 0;
            $feature['percentage'] = $totalUsers > 0 ? round(($feature['total_users'] / $totalUsers) * 100, 1) : 0;
        }

        return view('admin.features.index', compact('features', 'totalUsers'));
    }

    /**
     * Show the form for managing a specific feature.
     */
    public function show($feature)
    {
        // Validate feature exists
        if (!in_array($feature, [
            'enhanced-rental-dashboard',
            'streamlined-procurement',
            'mobile-optimized-ui',
            'advanced-reporting',
            'maintenance-scheduling',
        ])) {
            return redirect()->route('features.index')->with('error', 'Feature not found.');
        }

        // Get feature name and description
        $featureInfo = [
            'enhanced-rental-dashboard' => [
                'name' => 'Enhanced Rental Dashboard',
                'description' => 'Advanced analytics and insights for the rental dashboard',
            ],
            'streamlined-procurement' => [
                'name' => 'Streamlined Procurement',
                'description' => 'New procurement workflow with automated reordering and supplier analytics',
            ],
            'mobile-optimized-ui' => [
                'name' => 'Mobile-Optimized UI',
                'description' => 'Responsive interface optimized for mobile devices',
            ],
            'advanced-reporting' => [
                'name' => 'Advanced Reporting',
                'description' => 'Additional detailed reports and analytics',
            ],
            'maintenance-scheduling' => [
                'name' => 'Maintenance Scheduling',
                'description' => 'Equipment maintenance scheduling and tracking',
            ],
        ][$feature];

        // Get users with this feature
        $usersWithFeature = DB::table('features')
            ->where('name', $feature)
            ->where('value', true)
            ->pluck('scope_id')
            ->toArray();

        // Get users with feature enabled
        $users = User::select('id', 'name', 'email')
            ->orderBy('name')
            ->get()
            ->map(function ($user) use ($usersWithFeature) {
                $user->has_feature = in_array($user->id, $usersWithFeature);
                return $user;
            });

        return view('admin.features.show', compact('feature', 'featureInfo', 'users'));
    }

    /**
     * Update feature status for specific users.
     */
    public function update(Request $request, $feature)
    {
        $validated = $request->validate([
            'action' => 'required|in:enable-all,disable-all,enable-selected,disable-selected',
            'selected_users' => 'array',
            'selected_users.*' => 'exists:users,id',
        ]);

        switch ($validated['action']) {
            case 'enable-all':
                Feature::activateForEveryone($feature);
                $message = 'Feature enabled for all users.';
                break;
            
            case 'disable-all':
                Feature::deactivateForEveryone($feature);
                $message = 'Feature disabled for all users.';
                break;
            
            case 'enable-selected':
                if (!empty($validated['selected_users'])) {
                    foreach ($validated['selected_users'] as $userId) {
                        $user = User::find($userId);
                        Feature::for($user)->activate($feature);
                    }
                    $message = 'Feature enabled for selected users.';
                } else {
                    return redirect()->back()->with('error', 'No users selected.');
                }
                break;
            
            case 'disable-selected':
                if (!empty($validated['selected_users'])) {
                    foreach ($validated['selected_users'] as $userId) {
                        $user = User::find($userId);
                        Feature::for($user)->deactivate($feature);
                    }
                    $message = 'Feature disabled for selected users.';
                } else {
                    return redirect()->back()->with('error', 'No users selected.');
                }
                break;
        }

        return redirect()->route('features.show', $feature)->with('success', $message);
    }

    /**
     * Toggle feature for a specific user.
     */
    public function toggleForUser(Request $request)
    {
        $validated = $request->validate([
            'feature' => 'required|string',
            'user_id' => 'required|exists:users,id',
            'status' => 'required|boolean',
        ]);

        $user = User::find($validated['user_id']);
        
        if ($validated['status']) {
            Feature::for($user)->activate($validated['feature']);
            $message = 'Feature enabled for user.';
        } else {
            Feature::for($user)->deactivate($validated['feature']);
            $message = 'Feature disabled for user.';
        }

        return response()->json(['success' => true, 'message' => $message]);
    }

    /**
     * Reset feature to use the default definition.
     */
    public function resetFeature(Request $request, $feature)
    {
        // Clear all stored values for this feature
        DB::table('features')->where('name', $feature)->delete();
        
        return redirect()->route('features.show', $feature)
            ->with('success', 'Feature reset to use default definition for all users.');
    }
}
