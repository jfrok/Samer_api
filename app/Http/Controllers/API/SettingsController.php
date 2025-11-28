<?php

namespace App\Http\Controllers\API;

use App\Models\AppSetting;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /**
     * Get all settings
     */
    public function index()
    {
        try {
            $settings = AppSetting::all();
            return response()->json([
                'success' => true,
                'data' => $settings,
                'message' => 'Settings retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific setting by key
     */
    public function show($key)
    {
        try {
            $setting = AppSetting::where('key', $key)->first();

            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Setting not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $setting,
                'message' => 'Setting retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve setting: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create or update a setting
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'key' => 'required|string|unique:app_settings,key',
                'value' => 'nullable',
                'description' => 'nullable|string',
                'type' => 'nullable|string|in:string,boolean,integer,json'
            ]);

            $validated['type'] = $validated['type'] ?? 'string';
            $setting = AppSetting::create($validated);

            return response()->json([
                'success' => true,
                'data' => $setting,
                'message' => 'Setting created successfully'
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create setting: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a setting by key
     */
    public function update(Request $request, $key)
    {
        try {
            $setting = AppSetting::where('key', $key)->first();

            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Setting not found'
                ], 404);
            }

            $validated = $request->validate([
                'value' => 'nullable',
                'description' => 'nullable|string',
                'type' => 'nullable|string|in:string,boolean,integer,json'
            ]);

            $setting->update($validated);

            return response()->json([
                'success' => true,
                'data' => $setting,
                'message' => 'Setting updated successfully'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update setting: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a setting by key
     */
    public function destroy($key)
    {
        try {
            $setting = AppSetting::where('key', $key)->first();

            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Setting not found'
                ], 404);
            }

            $setting->delete();

            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'Setting deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete setting: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update settings
     */
    public function bulkUpdate(Request $request)
    {
        try {
            $validated = $request->validate([
                'settings' => 'required|array',
                'settings.*.key' => 'required|string',
                'settings.*.value' => 'nullable',
                'settings.*.type' => 'nullable|string|in:string,boolean,integer,json'
            ]);

            $results = [];
            foreach ($validated['settings'] as $settingData) {
                $setting = AppSetting::updateOrCreate(
                    ['key' => $settingData['key']],
                    [
                        'value' => $settingData['value'] ?? null,
                        'type' => $settingData['type'] ?? 'string'
                    ]
                );
                $results[] = $setting;
            }

            return response()->json([
                'success' => true,
                'data' => $results,
                'message' => 'Settings updated successfully'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update settings: ' . $e->getMessage()
            ], 500);
        }
    }
}
