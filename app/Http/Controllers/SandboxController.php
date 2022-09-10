<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SandboxController extends Controller
{
    private $referenceId;
    private $apiKey;
    private $accessToken;

    public function initialiaze(string $type)
    {
        $this->referenceId = Str::uuid()->toString();
        try {
            // Initialize transaction
            $response = Http::withHeaders([
                'Ocp-Apim-Subscription-Key' => $type === 'DISBURSEMENT' ? env('MOMO_PRIMARY_KEY_DISBURSEMENT') : env('MOMO_PRIMARY_KEY_COLLECTION'),
                'X-Reference-Id' => $this->referenceId,
                'Content-Type' => 'application/json'
            ])->post('https://sandbox.momodeveloper.mtn.com/v1_0/apiuser', [
                "providerCallbackHost" => "gomapguide.com"
            ]);

            // Create ApiKey
            $response = Http::withHeaders([
                'Ocp-Apim-Subscription-Key' => $type === 'DISBURSEMENT' ? env('MOMO_PRIMARY_KEY_DISBURSEMENT') : env('MOMO_PRIMARY_KEY_COLLECTION'),
            ])->post("https://sandbox.momodeveloper.mtn.com/v1_0/apiuser/{$this->referenceId}/apikey");
            $bodyResponse = json_decode($response->body());
            $this->apiKey = $bodyResponse->apiKey;

            // Create Acess Token
            $response = Http::withHeaders([
                'Ocp-Apim-Subscription-Key' => $type === 'DISBURSEMENT' ? env('MOMO_PRIMARY_KEY_DISBURSEMENT') : env('MOMO_PRIMARY_KEY_COLLECTION'),
                'Authorization' => "Basic " . base64_encode("$this->referenceId:$this->apiKey"),
            ])->post($type === 'DISBURSEMENT' ? 'https://sandbox.momodeveloper.mtn.com/disbursement/token/' : 'https://sandbox.momodeveloper.mtn.com/collection/token/');
            $bodyResponse = json_decode($response->body());
            $this->accessToken = $bodyResponse->access_token;
        } catch (HttpException $ex) {
            return $ex;
        }
    }

    public function collect(Request $request)
    {
        // initialiaze
        $this->initialiaze('COLLECTION');
        // Collect Money
        $uuid = Str::uuid()->toString();
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer $this->accessToken",
                'Ocp-Apim-Subscription-Key' => env('MOMO_PRIMARY_KEY_COLLECTION'),
                'X-Reference-Id' => $uuid,
                'X-Target-Environment' => 'sandbox',
                'Content-Type' => 'application/json'
            ])->post('https://sandbox.momodeveloper.mtn.com/collection/v1_0/requesttopay', [
                "amount" => "100",
                "currency" => "EUR",
                "externalId" => $uuid,
                "payer" => [
                    "partyIdType" => "MSISDN",
                    "partyId" => "237675856306",
                ],
                "payerMessage" => "Premier Text Payment",
                "payeeNote" => "Je Paye Bien",
            ]);
            return $response->status();
        } catch (HttpException $ex) {
            return $ex;
        }
    }

    public function deposit(Request $request)
    {
        // initialiaze
        $this->initialiaze('DISBURSEMENT');
        // Collect Money
        $uuid = Str::uuid()->toString();
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer $this->accessToken",
                'Ocp-Apim-Subscription-Key' => env('MOMO_PRIMARY_KEY_DISBURSEMENT'),
                'X-Reference-Id' => $uuid,
                'X-Target-Environment' => 'sandbox',
                'Content-Type' => 'application/json'
            ])->post('https://sandbox.momodeveloper.mtn.com/disbursement/v2_0/deposit', [
                "amount" => "100",
                "currency" => "EUR",
                "externalId" => $uuid,
                "payee" => [
                    "partyIdType" => "MSISDN",
                    "partyId" => "237675856306",
                ],
                "payerMessage" => "Premier Text Payment",
                "payeeNote" => "Je Paye Bien",
            ]);
            return $response->status();
        } catch (HttpException $ex) {
            return $ex;
        }
    }
}
