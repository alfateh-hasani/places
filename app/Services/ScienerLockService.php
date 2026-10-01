<?php

namespace App\Services;

use App\Models\ScienerToken;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ScienerLockService
{
    protected $client;

    protected $baseUri;

    protected $clientId;

    protected $clientSecret;

    protected $username;

    protected $password;

    /** @var array{errcode:int|null,errmsg:string|null}|null */
    protected ?array $lastError = null;

    public function __construct($username, $password)
    {
        $this->baseUri = 'https://euapi.sciener.com';

        $this->client = new Client([
            'base_uri' => $this->baseUri,
            'timeout' => 10.0,
        ]);

        $this->clientId = config('services.sciener.client_id');
        $this->clientSecret = config('services.sciener.client_secret');
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * دالة رئيسية للحصول على توكن صالح
     */
    public function getValidAccessToken()
    {
        Log::info('Attempting to get valid access token for user: '.$this->username);

        // التحقق من الكاش
        $cachedToken = Cache::get('sciener_access_token_'.$this->username);

        if ($cachedToken && ! $this->isTokenExpired($cachedToken)) {
            Log::info('Using cached access token');

            return $cachedToken['access_token'];
        }

        // التحقق من قاعدة البيانات
        $tokenRecord = ScienerToken::where('username', $this->username)->first();

        if ($tokenRecord) {
            Log::info('Found token record in database');

            if (! $this->isRecordExpired($tokenRecord)) {
                Log::info('Using valid token from database');
                $this->putTokenRecordInCache($tokenRecord);

                return $tokenRecord->access_token;
            }

            Log::info('Token expired, attempting to refresh');
            if ($tokenRecord->refresh_token) {
                $refreshData = $this->refreshAccessToken($tokenRecord->refresh_token);
                if ($refreshData && isset($refreshData['access_token'])) {
                    Log::info('Successfully refreshed access token');
                    $this->putTokenRecordInCache($tokenRecord->fresh());

                    return $refreshData['access_token'];
                } else {
                    // إذا فشل تجديد التوكن، احذف السجل القديم
                    $tokenRecord->delete();
                    Cache::forget('sciener_access_token_'.$this->username);
                    Log::warning('Deleted invalid refresh token and will request new token');
                }
            } else {
                Log::warning('No refresh token available, will request new token');
            }
        } else {
            Log::info('No token record found in database');
        }

        // جلب توكن جديد إذا لم يكن هناك سجل أو لم ننجح في التحديث
        Log::info('Requesting new access token');
        $newTokenData = $this->getAccessToken();
        if ($newTokenData && isset($newTokenData['access_token'])) {
            Log::info('Successfully obtained new access token');
            $tokenRecord = ScienerToken::where('username', $this->username)->first();
            if ($tokenRecord) {
                $this->putTokenRecordInCache($tokenRecord);

                return $newTokenData['access_token'];
            }
        } else {
            Log::error('Failed to get new access token after refresh token failure');
        }

        Log::error('All token acquisition methods failed');

        return null;
    }

    /**
     * جلب الـ Access Token لأول مرة باستخدام (Resource Owner Password Grant).
     */
    public function getAccessToken()
    {
        try {
            $response = $this->client->post('/oauth2/token', [
                'form_params' => [
                    'clientId' => $this->clientId,
                    'clientSecret' => $this->clientSecret,
                    'username' => $this->username,
                    // التوثيق ذكر أن الباسوورد تكون md5 (منخفضة الأحرف) قبل الإرسال
                    'password' => md5($this->password),
                ],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            // التحقق من وجود خطأ في الرد
            if (isset($data['errcode']) && $data['errcode'] !== 0) {
                Log::error('Sciener API access token request failed', $data);
                $this->lastError = ['errcode' => $data['errcode'], 'errmsg' => $data['errmsg'] ?? null];

                return null;
            }

            // التحقق من وجود access_token في الرد
            if (! isset($data['access_token'])) {
                Log::error('Sciener API access token response missing access_token', $data);
                $this->lastError = ['errcode' => null, 'errmsg' => 'Response missing access_token'];

                return null;
            }

            // تخزين البيانات في قاعدة البيانات
            $this->storeTokenData($data);

            return $data;
        } catch (\Exception $e) {
            Log::error('Error fetching access token: '.$e->getMessage());
            $this->lastError = ['errcode' => null, 'errmsg' => $e->getMessage()];

            // في حالة حدوث خطأ، احذف أي توكن قديم موجود
            ScienerToken::where('username', $this->username)->delete();
            Cache::forget('sciener_access_token_'.$this->username);

            return null;
        }
    }

    /**
     * آخر خطأ حدث أثناء طلب/تجديد التوكن أو عملية على القفل (إن وُجد).
     *
     * @return array{errcode:int|null,errmsg:string|null}|null
     */
    public function getLastError(): ?array
    {
        return $this->lastError;
    }

    /**
     * تجديد الـ Access Token بواسطة الـ Refresh Token.
     */
    public function refreshAccessToken($refreshToken)
    {
        try {
            $response = $this->client->post('/oauth2/token', [
                'form_params' => [
                    'clientId' => $this->clientId,
                    'clientSecret' => $this->clientSecret,
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken,
                ],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            // تحقق من وجود خطأ في الرد
            if (isset($data['errcode']) && $data['errcode'] !== 0) {
                Log::error('Sciener API refresh token failed', $data);
                $this->lastError = ['errcode' => $data['errcode'], 'errmsg' => $data['errmsg'] ?? null];

                // إذا كان الخطأ متعلق بـ refresh_token غير صالح، احذف السجل
                if ($data['errcode'] == 10011) {
                    Log::warning('Invalid refresh_token detected, will delete token record');
                    ScienerToken::where('refresh_token', $refreshToken)->delete();
                    Cache::forget('sciener_access_token_'.$this->username);
                }

                return null;
            }

            // تحقق من وجود access_token في الرد
            if (! isset($data['access_token'])) {
                Log::error('Sciener API refresh token response missing access_token', $data);
                $this->lastError = ['errcode' => null, 'errmsg' => 'Response missing access_token'];

                return null;
            }

            // تحديث السجل في قاعدة البيانات
            $this->updateTokenData($refreshToken, $data);

            return $data;
        } catch (\Exception $e) {
            Log::error('Error refreshing access token: '.$e->getMessage());
            $this->lastError = ['errcode' => null, 'errmsg' => $e->getMessage()];

            // في حالة حدوث خطأ أثناء التواصل مع API، احذف التوكن القديم
            ScienerToken::where('refresh_token', $refreshToken)->delete();
            Cache::forget('sciener_access_token_'.$this->username);

            return null;
        }
    }

    /**
     * تخزين بيانات التوكن في الجدول الخاص بها.
     */
    private function storeTokenData($tokenData)
    {
        try {
            $expiresAt = now()->addSeconds($tokenData['expires_in'] ?? 0);

            $record = ScienerToken::updateOrCreate(
                ['username' => $this->username],
                [
                    'access_token' => $tokenData['access_token'],
                    'refresh_token' => $tokenData['refresh_token'] ?? null,
                    'openid' => $tokenData['openid'] ?? null,
                    'scope' => $tokenData['scope'] ?? null,
                    'token_type' => $tokenData['token_type'] ?? null,
                    'expires_in' => $tokenData['expires_in'] ?? null,
                    'expires_at' => $expiresAt,
                ]
            );

            Log::info('Token data stored successfully in database');

            // بعد تخزين السجل، حدث الكاش مباشرة لضمان التزامن
            $this->putTokenRecordInCache($record);
        } catch (\Exception $e) {
            Log::error('Error storing token data: '.$e->getMessage());
            throw new \Exception('Failed to store token data');
        }
    }

    private function updateTokenData($refreshToken, $tokenData)
    {
        $expiresAt = now()->addSeconds($tokenData['expires_in'] ?? 0);

        // تحديث السجل بناءً على refresh_token
        $updatedRecords = \App\Models\ScienerToken::where('refresh_token', $refreshToken)->update([
            'access_token' => $tokenData['access_token'],
            'refresh_token' => $tokenData['refresh_token'] ?? $refreshToken, // تحديث refresh_token إذا كان موجوداً
            'expires_in' => $tokenData['expires_in'] ?? null,
            'expires_at' => $expiresAt,
        ]);

        // التحقق من أن التحديث تم بنجاح
        if ($updatedRecords > 0) {
            // تحديث الكاش أيضًا لضمان تحسين الأداء
            $this->updateCache($tokenData['access_token'], $tokenData['refresh_token'] ?? $refreshToken, $expiresAt);
        } else {
            Log::warning('No token records were updated during refresh');
        }
    }

    private function updateCache($accessToken, $refreshToken, $expiresAt)
    {
        $secondsRemaining = $expiresAt->diffInSeconds(now());
        $cacheTtl = $secondsRemaining > 10 ? $secondsRemaining - 10 : $secondsRemaining;

        Cache::put('sciener_access_token_'.$this->username, [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_at' => $expiresAt,
        ], $cacheTtl);
    }

    /**
     * تخزين التوكن في الكاش.
     * نحدد مدة انتهاء الكاش بحيث تنتهي قبل قليل من انتهاء صلاحية التوكن لتجنب أي تعارض.
     */
    private function putTokenRecordInCache(ScienerToken $tokenRecord)
    {
        try {
            $secondsRemaining = $tokenRecord->expires_at
                ? $tokenRecord->expires_at->diffInSeconds(now())
                : 0;

            $cacheTtl = $secondsRemaining > 10 ? $secondsRemaining - 10 : $secondsRemaining;

            $dataToCache = [
                'access_token' => $tokenRecord->access_token,
                'expires_at' => $tokenRecord->expires_at,
                'refresh_token' => $tokenRecord->refresh_token,
                'uid' => $tokenRecord->uid,
            ];

            Cache::put('sciener_access_token_'.$this->username, $dataToCache, $cacheTtl);
            Log::info('Token cached successfully with TTL: '.$cacheTtl.' seconds');
        } catch (\Exception $e) {
            Log::error('Error caching token: '.$e->getMessage());
            // لا نرمي استثناء هنا لأن الكاش ليس ضروريًا للعمل
        }
    }

    /**
     * التحقق من صلاحية التوكن الموجود في الكاش
     */
    private function isTokenExpired(array $token)
    {
        if (! isset($token['expires_at'])) {
            return true;
        }
        $expiresAt = \Carbon\Carbon::parse($token['expires_at']);

        return $expiresAt->isPast();
    }

    /**
     * التحقق من انتهاء صلاحية السجل المخزّن في قاعدة البيانات
     */
    private function isRecordExpired(ScienerToken $tokenRecord)
    {
        if (! $tokenRecord->expires_at) {
            return true;
        }

        return $tokenRecord->expires_at->isPast();
    }

    public function getPasscode($lockId, $startDate, $endDate, $name = '')
    {
        try {
            Log::info('Generating passcode for lock: '.$lockId);

            if (! $name) {
                $name = time();
            }

            // الحصول على التوكن الصالح
            $accessToken = $this->getValidAccessToken();

            if (! $accessToken) {
                Log::error('Unable to fetch a valid access token for passcode generation');
                throw new \Exception('Unable to fetch a valid access token.');
            }

            // تحويل التواريخ إلى timestamp (بصيغة millisecond)
            $startDateTimestamp = $this->convertToMilliseconds($startDate);
            $endDateTimestamp = $this->convertToMilliseconds($endDate);
            $currentTimestamp = $this->getCurrentTimestamp();

            $payLoad = [
                'form_params' => [
                    'clientId' => $this->clientId,
                    'accessToken' => $accessToken,
                    'lockId' => $lockId,
                    'keyboardPwdType' => 3, // نوع الرمز: Period
                    'keyboardPwdName' => $name, // يمكن تغييره عند الحاجة
                    'startDate' => $startDateTimestamp,
                    'endDate' => $endDateTimestamp,
                    'date' => $currentTimestamp,
                ],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
            ];

            \Log::info('Passcode generation payload: '.json_encode($payLoad));
            // طلب الـ API
            $response = $this->client->post('/v3/keyboardPwd/get', $payLoad);
            \Log::info('Passcode generation response: '.$response->getBody());

            // تفريغ البيانات القادمة من الاستجابة
            $data = json_decode($response->getBody(), true);

            // التحقق من وجود رمز المرور في الرد
            if (! isset($data['keyboardPwd'])) {
                Log::error('Failed to generate passcode', $data);
                throw new \Exception('Passcode generation failed.');
            }

            return $data;

        } catch (\Exception $e) {
            Log::error('Error generating passcode: '.$e->getMessage());

            return null;
        }
    }

    public function addCustomPasscode($lockId, $keyboardPwd, $startDate, $endDate, $keyboardPwdName = 'Custom Passcode')
    {
        try {
            Log::info('Adding custom passcode for lock: '.$lockId.' with code: '.$keyboardPwd);

            // الحصول على Access Token صالح
            $accessToken = $this->getValidAccessToken();

            if (! $accessToken) {
                Log::error('Unable to fetch a valid access token for adding custom passcode');
                throw new \Exception('Unable to fetch a valid access token.');
            }

            // تحويل التواريخ إلى Timestamp بالميلي ثانية
            $startDateTimestamp = $startDate ? $this->convertToMilliseconds($startDate) : null;
            $endDateTimestamp = $endDate ? $this->convertToMilliseconds($endDate) : null;
            $currentTimestamp = $this->getCurrentTimestamp();

            // إعداد البيانات لإرسالها إلى الـ API
            $formParams = [
                'clientId' => $this->clientId,
                'accessToken' => $accessToken,
                'lockId' => $lockId,
                'keyboardPwd' => $keyboardPwd, // رمز مخصص (6 أرقام)
                'keyboardPwdName' => $keyboardPwdName,
                'addType' => 2, // الطريقة: WiFi أو Gateway
                'date' => $currentTimestamp,
            ];

            $formParams['keyboardPwdType'] = 3; // نوع Period
            $formParams['startDate'] = $startDateTimestamp;
            $formParams['endDate'] = $endDateTimestamp;
            \Log::info('addCustomPasscode payload: '.json_encode($formParams));

            // إرسال الطلب إلى API
            $response = $this->client->post('/v3/keyboardPwd/add', [
                'form_params' => $formParams,
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
            ]);

            \Log::info('addCustomPasscode response: '.$response->getBody());

            // تحليل الاستجابة
            $data = json_decode($response->getBody(), true);

            // التحقق من وجود keyboardPwdId في الاستجابة
            if (! isset($data['keyboardPwdId'])) {
                Log::error('Failed to add custom passcode', $data);
                $this->lastError = ['errcode' => $data['errcode'] ?? null, 'errmsg' => $data['errmsg'] ?? 'Failed to add custom passcode.'];
                throw new \Exception('Failed to add custom passcode.');
            }

            return $data;

        } catch (\Exception $e) {
            // dd($e->getMessage());
            Log::error('Error adding custom passcode: '.$e->getMessage());
            // لا نستبدل lastError إن كان مُعيَّناً بالفعل من فشل جلب الـ access token (أدق سبباً)
            $this->lastError = $this->lastError ?? ['errcode' => null, 'errmsg' => $e->getMessage()];

            return null;
        }
    }

    /**
     * تحويل التاريخ إلى timestamp بالميلي ثانية.
     */
    private function convertToMilliseconds($date)
    {
        try {
            return \Carbon\Carbon::parse($date, 'Asia/Riyadh')->timestamp * 1000;
        } catch (\Exception $e) {
            Log::error('Error converting date to milliseconds: '.$e->getMessage().' for date: '.$date);
            throw new \Exception('Invalid date format: '.$date);
        }
    }

    /**
     * الحصول على الوقت الحالي بصيغة timestamp بالميلي ثانية.
     */
    private function getCurrentTimestamp()
    {
        return now('UTC')->timestamp * 1000;
    }

    public function deletePasscode($lockId, $keyboardPwdId, $deleteType = 2)
    {
        try {
            Log::info('Deleting passcode for lock: '.$lockId.' with passcode ID: '.$keyboardPwdId);

            // الحصول على Access Token صالح
            $accessToken = $this->getValidAccessToken();

            if (! $accessToken) {
                Log::error('Unable to fetch a valid access token for deleting passcode');
                throw new \Exception('Unable to fetch a valid access token.');
            }

            // الحصول على الوقت الحالي بصيغة timestamp بالميلي ثانية
            $currentTimestamp = $this->getCurrentTimestamp();

            // إعداد البيانات لإرسالها إلى الـ API
            $formParams = [
                'clientId' => $this->clientId,
                'accessToken' => $accessToken,
                'lockId' => $lockId,
                'keyboardPwdId' => $keyboardPwdId,
                'deleteType' => $deleteType, // الطريقة: 1 أو 2
                'date' => $currentTimestamp,
            ];

            // إرسال الطلب إلى API
            $response = $this->client->post('/v3/keyboardPwd/delete', [
                'form_params' => $formParams,
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
            ]);

            // تحليل الاستجابة
            $data = json_decode($response->getBody(), true);

            // التحقق من وجود errcode في الرد
            if (! isset($data['errcode']) || $data['errcode'] !== 0) {
                Log::error('Failed to delete passcode', $data);
                $this->lastError = ['errcode' => $data['errcode'] ?? null, 'errmsg' => $data['errmsg'] ?? 'Failed to delete passcode.'];
                throw new \Exception($data['errmsg'] ?? 'Failed to delete passcode.');
            }

            return $data;

        } catch (\Exception $e) {
            Log::error('Error deleting passcode: '.$e->getMessage());
            $this->lastError = $this->lastError ?? ['errcode' => null, 'errmsg' => $e->getMessage()];

            return null;
        }
    }

    /**
     * قائمة بكل الأقفال التي يديرها هذا الحساب على Sciener (لأغراض التشخيص فقط).
     *
     * @return array<int, string> lockId لكل قفل
     */
    public function listLockIds(int $pageSize = 100): array
    {
        $accessToken = $this->getValidAccessToken();

        if (! $accessToken) {
            return [];
        }

        $ids = [];
        $pageNo = 1;

        do {
            try {
                $response = $this->client->post('/v3/lock/list', [
                    'form_params' => [
                        'clientId' => $this->clientId,
                        'accessToken' => $accessToken,
                        'pageNo' => $pageNo,
                        'pageSize' => $pageSize,
                        'date' => $this->getCurrentTimestamp(),
                    ],
                    'headers' => [
                        'Content-Type' => 'application/x-www-form-urlencoded',
                    ],
                ]);
            } catch (\Exception $e) {
                Log::error('Error listing locks: '.$e->getMessage());
                $this->lastError = ['errcode' => null, 'errmsg' => $e->getMessage()];
                break;
            }

            $data = json_decode($response->getBody(), true);

            if (isset($data['errcode']) && $data['errcode'] !== 0) {
                Log::error('Sciener API lock list failed', $data);
                $this->lastError = ['errcode' => $data['errcode'], 'errmsg' => $data['errmsg'] ?? null];
                break;
            }

            foreach (($data['list'] ?? []) as $lock) {
                if (isset($lock['lockId'])) {
                    $ids[] = (string) $lock['lockId'];
                }
            }

            $pages = (int) ($data['pages'] ?? 1);
            $pageNo++;
        } while ($pageNo <= $pages);

        return $ids;
    }
}
