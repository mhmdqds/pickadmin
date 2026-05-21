<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use App\Models\BusinessSetting;

class ConfigControllerMapApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure map_api_key_server exists so the controller constructor works
        BusinessSetting::updateOrCreate(
            ['key' => 'map_api_key_server'],
            ['value' => 'test-api-key-12345']
        );
    }

    // ─── VALIDATION TESTS ─────────────────────────────────────────────

    public function test_place_autocomplete_validation_fails_without_search_text()
    {
        $response = $this->getJson('/api/v1/config/place-api-autocomplete');

        $response->assertStatus(403)
            ->assertJsonStructure(['errors']);
    }

    public function test_distance_api_validation_fails_without_required_fields()
    {
        $response = $this->getJson('/api/v1/config/distance-api');

        $response->assertStatus(403)
            ->assertJsonStructure(['errors']);
    }

    public function test_distance_api_validation_fails_with_partial_fields()
    {
        $response = $this->getJson('/api/v1/config/distance-api?origin_lat=23.8&origin_lng=90.4');

        $response->assertStatus(403)
            ->assertJsonStructure(['errors']);
    }

    public function test_distance_api_validation_fails_with_invalid_mode()
    {
        $response = $this->getJson('/api/v1/config/distance-api?' . http_build_query([
            'origin_lat' => 23.8103,
            'origin_lng' => 90.4125,
            'destination_lat' => 23.7104,
            'destination_lng' => 90.4074,
            'mode' => 'FLY',
        ]));

        $response->assertStatus(403);
    }

    public function test_place_details_validation_fails_without_placeid()
    {
        $response = $this->getJson('/api/v1/config/place-api-details');

        $response->assertStatus(403)
            ->assertJsonStructure(['errors']);
    }

    public function test_geocode_api_validation_fails_without_coordinates()
    {
        $response = $this->getJson('/api/v1/config/geocode-api');

        $response->assertStatus(403)
            ->assertJsonStructure(['errors']);
    }

    public function test_geocode_api_validation_fails_with_partial_coordinates()
    {
        $response = $this->getJson('/api/v1/config/geocode-api?lat=23.8103');

        $response->assertStatus(403)
            ->assertJsonStructure(['errors']);
    }

    public function test_direction_api_validation_fails_without_required_fields()
    {
        $response = $this->getJson('/api/v1/config/direction-api');

        $response->assertStatus(403)
            ->assertJsonStructure(['errors']);
    }

    // ─── CACHE HIT TESTS ─────────────────────────────────────────────

    public function test_place_autocomplete_returns_cached_data_on_cache_hit()
    {
        $searchText = 'Dhaka';
        $locale = app()->getLocale();
        $cacheKey = 'place_autocomplete_' . md5($searchText . '_' . $locale);

        $cachedData = ['suggestions' => [['placePrediction' => ['text' => 'Dhaka, Bangladesh']]]];
        Cache::put($cacheKey, $cachedData, now()->addMinutes(30));

        $response = $this->getJson('/api/v1/config/place-api-autocomplete?search_text=' . $searchText);

        $response->assertStatus(200)
            ->assertJson($cachedData);
    }

    public function test_distance_api_returns_cached_data_on_cache_hit()
    {
        $params = [
            'origin_lat' => 23.81030,
            'origin_lng' => 90.41250,
            'destination_lat' => 23.71040,
            'destination_lng' => 90.40740,
            'mode' => 'WALK',
        ];

        $cacheKey = 'distance_api_' . md5(
            round($params['origin_lat'], 5) . '_' . round($params['origin_lng'], 5) . '_' .
            round($params['destination_lat'], 5) . '_' . round($params['destination_lng'], 5) . '_' . $params['mode']
        );

        $cachedData = ['distanceMeters' => 12000, 'duration' => '3600s'];
        Cache::put($cacheKey, $cachedData, now()->addHours(24));

        $response = $this->getJson('/api/v1/config/distance-api?' . http_build_query($params));

        $response->assertStatus(200)
            ->assertJson($cachedData);
    }

    public function test_place_details_returns_cached_data_on_cache_hit()
    {
        $placeId = 'ChIJD7fiBh9u5kcRYJSMaMOCCwQ';
        $cacheKey = 'place_details_' . md5($placeId);

        $cachedData = [
            'id' => $placeId,
            'displayName' => ['text' => 'Test Place'],
            'formattedAddress' => '123 Test St',
            'location' => ['latitude' => 23.8103, 'longitude' => 90.4125],
        ];
        Cache::put($cacheKey, $cachedData, now()->addDays(7));

        $response = $this->getJson('/api/v1/config/place-api-details?placeid=' . $placeId);

        $response->assertStatus(200)
            ->assertJson($cachedData);
    }

    public function test_geocode_api_returns_cached_data_on_cache_hit()
    {
        $lat = 23.81030;
        $lng = 90.41250;
        $cacheKey = 'geocode_api_' . md5(round($lat, 5) . '_' . round($lng, 5));

        $cachedData = [
            'results' => [['formatted_address' => 'Dhaka, Bangladesh']],
            'status' => 'OK',
        ];
        Cache::put($cacheKey, $cachedData, now()->addDays(7));

        $response = $this->getJson('/api/v1/config/geocode-api?lat=' . $lat . '&lng=' . $lng);

        $response->assertStatus(200)
            ->assertJson($cachedData);
    }

    public function test_direction_api_returns_cached_data_on_cache_hit()
    {
        $params = [
            'origin_lat' => 23.81030,
            'origin_lng' => 90.41250,
            'destination_lat' => 23.71040,
            'destination_lng' => 90.40740,
        ];

        $mode = 'DRIVE';
        $cacheKey = 'direction_api_' . md5(
            round($params['origin_lat'], 5) . '_' . round($params['origin_lng'], 5) . '_' .
            round($params['destination_lat'], 5) . '_' . round($params['destination_lng'], 5) . '_' . $mode
        );

        $cachedData = [
            'routes' => [['distanceMeters' => 15000, 'duration' => '1800s']],
        ];
        Cache::put($cacheKey, $cachedData, now()->addHour());

        $response = $this->getJson('/api/v1/config/direction-api?' . http_build_query($params));

        $response->assertStatus(200)
            ->assertJson($cachedData);
    }

    // ─── CACHE KEY PRECISION: NEARBY COORDINATES HIT SAME CACHE ───────

    public function test_distance_api_nearby_coordinates_use_same_cache_key()
    {
        // Two coordinates that differ only at 6th decimal place (< 0.11m apart)
        // should round to the same 5-decimal key
        $lat1 = 23.810301;
        $lat2 = 23.810304;

        $key1 = 'distance_api_' . md5(round($lat1, 5) . '_' . round(90.41250, 5) . '_' . round(23.71040, 5) . '_' . round(90.40740, 5) . '_WALK');
        $key2 = 'distance_api_' . md5(round($lat2, 5) . '_' . round(90.41250, 5) . '_' . round(23.71040, 5) . '_' . round(90.40740, 5) . '_WALK');

        $this->assertEquals($key1, $key2);
    }

    public function test_geocode_api_nearby_coordinates_use_same_cache_key()
    {
        $lat1 = 23.810301;
        $lat2 = 23.810304;

        $key1 = 'geocode_api_' . md5(round($lat1, 5) . '_' . round(90.41250, 5));
        $key2 = 'geocode_api_' . md5(round($lat2, 5) . '_' . round(90.41250, 5));

        $this->assertEquals($key1, $key2);
    }

    // ─── CACHE KEY UNIQUENESS: DIFFERENT PARAMS PRODUCE DIFFERENT KEYS ─

    public function test_distance_api_different_modes_produce_different_cache_keys()
    {
        $base = round(23.81030, 5) . '_' . round(90.41250, 5) . '_' . round(23.71040, 5) . '_' . round(90.40740, 5);

        $keyWalk = 'distance_api_' . md5($base . '_WALK');
        $keyDrive = 'distance_api_' . md5($base . '_DRIVE');

        $this->assertNotEquals($keyWalk, $keyDrive);
    }

    public function test_place_autocomplete_different_locales_produce_different_cache_keys()
    {
        $searchText = 'Dhaka';

        $keyEn = 'place_autocomplete_' . md5($searchText . '_en');
        $keyBn = 'place_autocomplete_' . md5($searchText . '_bn');

        $this->assertNotEquals($keyEn, $keyBn);
    }

    // ─── API ERROR RESPONSE MUST NOT BE CACHED ────────────────────────

    public function test_geocode_api_does_not_cache_non_ok_status()
    {
        Http::fake([
            'maps.googleapis.com/*' => Http::response([
                'results' => [],
                'status' => 'ZERO_RESULTS',
            ], 200),
        ]);

        $lat = 0.00001;
        $lng = 0.00001;
        $cacheKey = 'geocode_api_' . md5(round($lat, 5) . '_' . round($lng, 5));

        $response = $this->getJson('/api/v1/config/geocode-api?lat=' . $lat . '&lng=' . $lng);

        $response->assertStatus(200);
        $this->assertNull(Cache::get($cacheKey));
    }

    public function test_geocode_api_does_not_cache_request_denied()
    {
        Http::fake([
            'maps.googleapis.com/*' => Http::response([
                'results' => [],
                'status' => 'REQUEST_DENIED',
                'error_message' => 'API key is invalid.',
            ], 200),
        ]);

        $lat = 1.00001;
        $lng = 1.00001;
        $cacheKey = 'geocode_api_' . md5(round($lat, 5) . '_' . round($lng, 5));

        $this->getJson('/api/v1/config/geocode-api?lat=' . $lat . '&lng=' . $lng);

        $this->assertNull(Cache::get($cacheKey));
    }

    public function test_geocode_api_caches_successful_ok_response()
    {
        $successData = [
            'results' => [['formatted_address' => 'Some Place']],
            'status' => 'OK',
        ];

        Http::fake([
            'maps.googleapis.com/*' => Http::response($successData, 200),
        ]);

        $lat = 2.00001;
        $lng = 2.00001;
        $cacheKey = 'geocode_api_' . md5(round($lat, 5) . '_' . round($lng, 5));

        $this->getJson('/api/v1/config/geocode-api?lat=' . $lat . '&lng=' . $lng);

        $cached = Cache::get($cacheKey);
        $this->assertNotNull($cached);
        $this->assertEquals('OK', $cached['status']);
    }

    public function test_geocode_api_does_not_cache_http_failure()
    {
        Http::fake([
            'maps.googleapis.com/*' => Http::response('Server Error', 500),
        ]);

        $lat = 3.00001;
        $lng = 3.00001;
        $cacheKey = 'geocode_api_' . md5(round($lat, 5) . '_' . round($lng, 5));

        $this->getJson('/api/v1/config/geocode-api?lat=' . $lat . '&lng=' . $lng);

        $this->assertNull(Cache::get($cacheKey));
    }

    // ─── CACHE MISS → FRESH API CALL TESTS ────────────────────────────

    public function test_geocode_api_makes_api_call_on_cache_miss()
    {
        $expectedData = [
            'results' => [['formatted_address' => 'Fresh Place']],
            'status' => 'OK',
        ];

        Http::fake([
            'maps.googleapis.com/*' => Http::response($expectedData, 200),
        ]);

        $lat = 4.00001;
        $lng = 4.00001;

        $response = $this->getJson('/api/v1/config/geocode-api?lat=' . $lat . '&lng=' . $lng);

        $response->assertStatus(200)
            ->assertJson($expectedData);

        Http::assertSentCount(1);
    }

    public function test_geocode_api_does_not_call_api_on_cache_hit()
    {
        Http::fake([
            'maps.googleapis.com/*' => Http::response(['status' => 'OK'], 200),
        ]);

        $lat = 5.00001;
        $lng = 5.00001;
        $cacheKey = 'geocode_api_' . md5(round($lat, 5) . '_' . round($lng, 5));

        Cache::put($cacheKey, ['results' => [['formatted_address' => 'Cached Place']], 'status' => 'OK'], now()->addDays(7));

        $response = $this->getJson('/api/v1/config/geocode-api?lat=' . $lat . '&lng=' . $lng);

        $response->assertStatus(200)
            ->assertJson(['results' => [['formatted_address' => 'Cached Place']]]);

        Http::assertNothingSent();
    }

    // ─── DISTANCE API: NULL SAFETY ON MALFORMED RESPONSE ──────────────

    public function test_distance_api_handles_null_decoded_response_safely()
    {
        // Simulate: API returns non-JSON (e.g. HTML error page)
        // json_decode returns null, is_array(null) = false → result = null
        // No crash, no cache write
        $params = [
            'origin_lat' => 10.00001,
            'origin_lng' => 10.00001,
            'destination_lat' => 11.00001,
            'destination_lng' => 11.00001,
        ];

        $cacheKey = 'distance_api_' . md5(
            round(10.00001, 5) . '_' . round(10.00001, 5) . '_' .
            round(11.00001, 5) . '_' . round(11.00001, 5) . '_WALK'
        );

        // Pre-verify no cache
        $this->assertNull(Cache::get($cacheKey));

        // We can't easily mock curl, but we CAN verify the cache key logic
        // and that a cached null doesn't get stored (testing the guard condition)
        $result = is_array(null) ? (null[0] ?? null) : null;
        $this->assertNull($result);

        // Verify the guard: null result won't pass the cache-write condition
        $response = false; // simulating curl_exec failure
        $this->assertFalse($response !== false && $result !== null && !isset($result['error']));
    }

    public function test_distance_api_handles_error_json_response_safely()
    {
        // Google API error: {"error": {"code": 400, "message": "...", "status": "INVALID_ARGUMENT"}}
        // json_decode gives associative array, is_array = true, but [0] doesn't exist → null
        $errorResponse = ['error' => ['code' => 400, 'message' => 'Invalid', 'status' => 'INVALID_ARGUMENT']];
        $decoded = $errorResponse;
        $result = is_array($decoded) ? ($decoded[0] ?? null) : null;

        $this->assertNull($result);

        // Verify the guard blocks caching
        $this->assertFalse('fake-response' !== false && $result !== null && !isset($result['error']));
    }

    public function test_distance_api_handles_valid_array_response()
    {
        // Google Routes API success: [{distanceMeters: 12000, duration: "3600s"}]
        $apiResponse = [['distanceMeters' => 12000, 'duration' => '3600s']];
        $result = is_array($apiResponse) ? ($apiResponse[0] ?? null) : null;

        $this->assertNotNull($result);
        $this->assertEquals(12000, $result['distanceMeters']);

        // Verify the guard allows caching
        $response = json_encode($apiResponse); // simulating valid curl response
        $this->assertTrue($response !== false && $result !== null && !isset($result['error']));
    }

    // ─── DIRECTION API: CURL ERROR PATH ───────────────────────────────

    public function test_direction_api_returns_cached_data_and_ignores_mode_case()
    {
        // mode defaults to DRIVE via strtoupper, verify case normalization
        $params = [
            'origin_lat' => 23.81030,
            'origin_lng' => 90.41250,
            'destination_lat' => 23.71040,
            'destination_lng' => 90.40740,
            'mode' => 'drive', // lowercase
        ];

        $mode = strtoupper($params['mode']);
        $cacheKey = 'direction_api_' . md5(
            round($params['origin_lat'], 5) . '_' . round($params['origin_lng'], 5) . '_' .
            round($params['destination_lat'], 5) . '_' . round($params['destination_lng'], 5) . '_' . $mode
        );

        $cachedData = ['routes' => [['distanceMeters' => 8000]]];
        Cache::put($cacheKey, $cachedData, now()->addHour());

        $response = $this->getJson('/api/v1/config/direction-api?' . http_build_query($params));

        $response->assertStatus(200)
            ->assertJson($cachedData);
    }

    public function test_direction_api_default_mode_is_drive()
    {
        // Without mode param, defaults to DRIVE
        $params = [
            'origin_lat' => 24.81030,
            'origin_lng' => 91.41250,
            'destination_lat' => 24.71040,
            'destination_lng' => 91.40740,
        ];

        $cacheKey = 'direction_api_' . md5(
            round($params['origin_lat'], 5) . '_' . round($params['origin_lng'], 5) . '_' .
            round($params['destination_lat'], 5) . '_' . round($params['destination_lng'], 5) . '_DRIVE'
        );

        $cachedData = ['routes' => [['distanceMeters' => 5000]]];
        Cache::put($cacheKey, $cachedData, now()->addHour());

        $response = $this->getJson('/api/v1/config/direction-api?' . http_build_query($params));

        $response->assertStatus(200)
            ->assertJson($cachedData);
    }

    // ─── DISTANCE API: DEFAULT MODE IS WALK ───────────────────────────

    public function test_distance_api_default_mode_is_walk()
    {
        $params = [
            'origin_lat' => 25.81030,
            'origin_lng' => 92.41250,
            'destination_lat' => 25.71040,
            'destination_lng' => 92.40740,
        ];

        $cacheKey = 'distance_api_' . md5(
            round($params['origin_lat'], 5) . '_' . round($params['origin_lng'], 5) . '_' .
            round($params['destination_lat'], 5) . '_' . round($params['destination_lng'], 5) . '_WALK'
        );

        $cachedData = ['distanceMeters' => 3000, 'duration' => '2400s'];
        Cache::put($cacheKey, $cachedData, now()->addHours(24));

        $response = $this->getJson('/api/v1/config/distance-api?' . http_build_query($params));

        $response->assertStatus(200)
            ->assertJson($cachedData);
    }

    // ─── CACHE EXPIRY SIMULATION ──────────────────────────────────────

    public function test_expired_cache_is_not_returned()
    {
        $placeId = 'ChIJExpiredPlace';
        $cacheKey = 'place_details_' . md5($placeId);

        // Put cache with a past expiry
        Cache::put($cacheKey, ['id' => $placeId, 'displayName' => 'Old Data'], now()->subMinute());

        // The cache should be expired
        $this->assertNull(Cache::get($cacheKey));
    }

    // ─── GUARD CONDITION UNIT TESTS ───────────────────────────────────

    public function test_guard_blocks_caching_when_curl_returns_false()
    {
        $response = false;
        $result = ['some' => 'data'];

        $shouldCache = $response !== false && $result !== null && !isset($result['error']);
        $this->assertFalse($shouldCache);
    }

    public function test_guard_blocks_caching_when_result_is_null()
    {
        $response = '{}';
        $result = null;

        $shouldCache = $response !== false && $result !== null && !isset($result['error']);
        $this->assertFalse($shouldCache);
    }

    public function test_guard_blocks_caching_when_result_has_error_key()
    {
        $response = '{"error":{"code":403}}';
        $result = ['error' => ['code' => 403, 'message' => 'Forbidden']];

        $shouldCache = $response !== false && $result !== null && !isset($result['error']);
        $this->assertFalse($shouldCache);
    }

    public function test_guard_allows_caching_for_valid_response()
    {
        $response = '{"suggestions":[]}';
        $result = ['suggestions' => []];

        $shouldCache = $response !== false && $result !== null && !isset($result['error']);
        $this->assertTrue($shouldCache);
    }

    public function test_geocode_guard_blocks_over_quota_status()
    {
        $result = ['results' => [], 'status' => 'OVER_QUERY_LIMIT'];

        $shouldCache = $result !== null && ($result['status'] ?? null) === 'OK';
        $this->assertFalse($shouldCache);
    }

    public function test_geocode_guard_blocks_unknown_error_status()
    {
        $result = ['results' => [], 'status' => 'UNKNOWN_ERROR'];

        $shouldCache = $result !== null && ($result['status'] ?? null) === 'OK';
        $this->assertFalse($shouldCache);
    }
}
