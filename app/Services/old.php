<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use Exception;

class ScienerLockService
{
    protected $clientId;
    protected $accessToken;
    protected $baseUrl = 'https://euapi.sciener.com/';

    const ENDPOINT_GET_ACCESS_TOKEN = 'oauth2/token';   
    const ENDPOINT_RENAME = 'v3/lock/rename';
    const ENDPOINT_GET_PASSCODE = 'v3/keyboardPwd/get';
    const ENDPOINT_ADD_PASSCODE = 'v3/keyboardPwd/add';
    const ENDPOINT_UNLOCK = 'v3/lock/unlock';
    
    
    const ADD_TYPE_BLUETOOTH = 1;
    const ADD_TYPE_GATEWAY_OR_WIFI = 2;


    public function __construct()
    {
        $this->clientId = config('services.sciener.client_id');
        $this->clientSecret = config('services.sciener.client_secret');
        $this->accessToken = config('services.sciener.access_token');
    }

    public function getAccessToken(string $username, string $password): array
    {
        $url = $this->baseUrl . self::ENDPOINT_GET_ACCESS_TOKEN;

        try {
            $response = Http::asForm()->post($url, [
                'clientId' => $this->clientId,
                'clientSecret' => $this->clientSecret,
                'username' => $username,
                'password' => md5($password),
            ]);

            if ($response->successful()) {
                $responseData = $response->json();

                config(['services.sciener.access_token' => $responseData['access_token']]);

                return [
                    'success' => true,
                    'access_token' => $responseData['access_token'],
                    'refresh_token' => $responseData['refresh_token'],
                    'expires_in' => $responseData['expires_in'],
                    'uid' => $responseData['uid'],
                ];
            }

            return [
                'success' => false,
                'message' => $response->json()['errmsg'] ?? 'Failed to obtain access token.',
            ];
        } catch (RequestException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'An error occurred while obtaining the access token: ' . $e->getMessage(),
            ];
        }
    }

    public function refreshAccessToken(): array
    {
        $url = $this->baseUrl . self::ENDPOINT_GET_ACCESS_TOKEN;

        try {
            $response = Http::asForm()->post($url, [
                'clientId' => $this->clientId,
                'clientSecret' => $this->clientSecret,
                'grant_type' => 'refresh_token',
                'refresh_token' => $this->refreshToken,
            ]);

            if ($response->successful()) {
                $responseData = $response->json();

                // Update the access token and refresh token
                config(['services.sciener.access_token' => $responseData['access_token']]);
                config(['services.sciener.refresh_token' => $responseData['refresh_token']]);

                return [
                    'success' => true,
                    'access_token' => $responseData['access_token'],
                    'refresh_token' => $responseData['refresh_token'],
                    'expires_in' => $responseData['expires_in'],
                ];
            }

            return [
                'success' => false,
                'message' => $response->json()['errmsg'] ?? 'Failed to refresh access token.',
            ];
        } catch (RequestException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'An error occurred while refreshing the access token: ' . $e->getMessage(),
            ];
        }
    }



    protected function getCommonParameters(): array
    {
        return [
            'clientId' => $this->clientId,
            'accessToken' => $this->accessToken,
            'date' => now()->timestamp * 1000,
        ];
    }

    protected function makeRequest(string $endpoint, array $parameters, string $method = 'POST'): array
    {
        $url = $this->baseUrl . $endpoint;

        try {
            $response = Http::asForm()->{$method}($url, $parameters);
            return $this->handleResponse($response->json());
        } catch (RequestException $e) {
            return $this->handleError('HTTP request failed: ' . $e->getMessage(), $e->getCode());
        } catch (Exception $e) {
            return $this->handleError('An unexpected error occurred: ' . $e->getMessage(), $e->getCode());
        }
    }

    protected function handleResponse(array $responseBody): array
    {
 
        if (isset($responseBody['errcode']) and $responseBody['errcode'] === 0) {
            return [
                'success' => true,
                'message' => 'Operation successful.',
                'data' => $responseBody,
            ];
        }elseif(isset($responseBody['keyboardPwdId'])){

            return [
                'success' => true,
                'message' => 'Operation successful.',
                'data' => $responseBody,
            ];
            
        }

        return $this->handleError($responseBody['errmsg'] ?? 'Operation failed.', $responseBody['errcode'] ?? null);
    }

    protected function handleError(string $message, ?int $errorCode): array
    {
        return [
            'success' => false,
            'message' => $message,
            'error_code' => $errorCode,
        ];
    }

    public function changeLockName(int $lockId, string $lockAlias): array
    {
        $parameters = array_merge($this->getCommonParameters(), [
            'lockId' => $lockId,
            'lockAlias' => $lockAlias,
        ]);

        return $this->makeRequest(self::ENDPOINT_RENAME, $parameters);
    }

    public function getPasscode(int $lockId, int $keyboardPwdType, string $keyboardPwdName = null, int $startDate, int $endDate = null): array
    {
        $parameters = array_merge($this->getCommonParameters(), [
            'lockId' => $lockId,
            'keyboardPwdType' => $keyboardPwdType,
            'startDate' => $startDate,
        ]);

        if ($keyboardPwdName) {
            $parameters['keyboardPwdName'] = $keyboardPwdName;
        }

        if ($endDate) {
            $parameters['endDate'] = $endDate;
        }

        return $this->makeRequest(self::ENDPOINT_GET_PASSCODE, $parameters);
    }

    public function addCustomPasscode(int $lockId, int $keyboardPwd, string $keyboardPwdName = null, int $startDate = null, int $endDate = null, int $addType = self::ADD_TYPE_GATEWAY_OR_WIFI): array
    {
        $parameters = array_merge($this->getCommonParameters(), [
            'lockId' => $lockId,
            'keyboardPwd' => $keyboardPwd,
            'addType' => $addType,
            'keyboardPwdType'=>2
        ]);

        if ($keyboardPwdName) {
            $parameters['keyboardPwdName'] = $keyboardPwdName;
        }else{
            $parameters['keyboardPwdName'] = $keyboardPwd;
        }   

        if ($startDate) {
            $parameters['startDate'] = $startDate;
        }

        if ($endDate) {
            $parameters['endDate'] = $endDate;
        }

        return $this->makeRequest(self::ENDPOINT_ADD_PASSCODE, $parameters);
    }


    public function unlock(int $lockId): array
    {
        $parameters = array_merge($this->getCommonParameters(), [
            'lockId' => $lockId
        ]);
 
        return $this->makeRequest(self::ENDPOINT_UNLOCK, $parameters);
    }


    
}
