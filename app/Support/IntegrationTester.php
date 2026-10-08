<?php

namespace App\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Sheriff → APIs → Configure → Test Connection. Each integration makes the lightest
 * read-only call that proves its settings work; ones without such a call check the
 * service can be reached. Nothing is charged, sent or changed.
 */
class IntegrationTester
{
    /** @return array{ok: bool, message: string, ms: int|null} */
    public static function test(string $key): array
    {
        $i = config('admin.integrations.'.$key);
        $env = $i['env'];
        $missing = array_keys(array_filter($env, fn ($v) => blank($v)));
        if ($missing) {
            return ['ok' => false, 'message' => 'Not set in .env: '.implode(', ', $missing).'. Upload them first.', 'ms' => null];
        }
        $start = microtime(true);
        try {
            [$ok, $message] = self::run($key, $env);
        } catch (ConnectionException $e) {
            [$ok, $message] = [false, 'Could not reach '.$i['name'].': '.self::short($e->getMessage())];
        } catch (\Throwable $e) {
            [$ok, $message] = [false, self::short($e->getMessage())];
        }

        return ['ok' => $ok, 'message' => $message, 'ms' => (int) round((microtime(true) - $start) * 1000)];
    }

    /** @return array{0: bool, 1: string} */
    private static function run(string $key, array $env): array
    {
        $http = Http::timeout(10)->acceptJson();

        return match ($key) {
            'stripe' => self::answer($http->withToken($env['STRIPE_SECRET'])->get('https://api.stripe.com/v1/balance'), 'Stripe accepted the secret key'),
            'sms' => self::answer($http->withBasicAuth($env['TWILIO_SID'], $env['TWILIO_TOKEN'])->get('https://api.twilio.com/2010-04-01/Accounts/'.rawurlencode($env['TWILIO_SID']).'.json'), 'Twilio accepted the account SID and token'),
            'authnet' => self::authnet($http, $env),
            'plaid' => self::answer($http->post('https://'.$env['PLAID_ENV'].'.plaid.com/categories/get', ['client_id' => $env['PLAID_CLIENT_ID'], 'secret' => $env['PLAID_SECRET']]), 'Plaid answered for the '.$env['PLAID_ENV'].' environment'),
            'salesforce' => self::answer($http->post('https://'.$env['SALESFORCE_SUBDOMAIN'].'.auth.marketingcloudapis.com/v2/token', ['grant_type' => 'client_credentials', 'client_id' => $env['SALESFORCE_CLIENT_ID'], 'client_secret' => $env['SALESFORCE_CLIENT_SECRET']]), 'Marketing Cloud issued a token'),
            'open_models' => self::openModels($http, $env['OPEN_MODELS_URL'], $env['OPEN_MODELS_API_KEY']),
            'local_models' => self::localModels($http, $env['LOCAL_MODELS_URL']),
            'amazon' => self::reach($http, 'https://s3.'.$env['AWS_DEFAULT_REGION'].'.amazonaws.com', 'Amazon S3 in '.$env['AWS_DEFAULT_REGION']),
            'apple' => self::reach($http, 'https://api.push.apple.com', 'Apple Push'),
            'quickbooks' => self::reach($http, 'https://quickbooks.api.intuit.com', 'QuickBooks'),
            default => self::reach($http, (string) collect($env)->first(fn ($v, $k) => str_ends_with($k, '_ENDPOINT')), config('admin.integrations.'.$key.'.name')),
        };
    }

    private static function answer(Response $r, string $success): array
    {
        if ($r->successful()) {
            return [true, $success.' (HTTP '.$r->status().').'];
        }

        return [false, match (true) {
            in_array($r->status(), [401, 403], true) => 'Rejected the credentials (HTTP '.$r->status().'). Check the uploaded values.',
            default => 'Answered HTTP '.$r->status().': '.self::short((string) ($r->json('error.message') ?? $r->json('error_description') ?? $r->json('message') ?? $r->body())),
        }];
    }

    /** Services we can't call without side effects: is the server there and answering? */
    private static function reach($http, string $url, string $name): array
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return [false, 'The endpoint "'.$url.'" is not a URL.'];
        }
        $r = $http->get($url);

        return $r->serverError()
            ? [false, $name.' answered with a server error (HTTP '.$r->status().').']
            : [true, $name.' is reachable (HTTP '.$r->status().'). The credentials are checked on the first real call.'];
    }

    private static function authnet($http, array $env): array
    {
        $r = $http->post('https://api.authorize.net/xml/v1/request.api', ['authenticateTestRequest' => ['merchantAuthentication' => ['name' => $env['AUTHNET_LOGIN_ID'], 'transactionKey' => $env['AUTHNET_TRANSACTION_KEY']]]]);
        $result = json_decode(preg_replace('/^\xEF\xBB\xBF/', '', $r->body()), true)['messages'] ?? null;

        return ($result['resultCode'] ?? null) === 'Ok'
            ? [true, 'Auth.net accepted the login ID and transaction key.']
            : [false, 'Auth.net: '.($result['message'][0]['text'] ?? 'HTTP '.$r->status())];
    }

    /** OpenAI-compatible endpoint: list the models it serves (needs a valid key, costs nothing). */
    private static function openModels($http, string $url, string $key): array
    {
        $r = $http->withToken($key)->get(rtrim($url, '/').'/models');
        if (! $r->successful()) {
            return self::answer($r, '');
        }
        $ids = collect($r->json('data', []))->pluck('id');

        return [true, 'The open models API accepted the key and serves '.$ids->count().' '.str('model')->plural($ids->count()).($ids->isNotEmpty() ? ', e.g. '.$ids->take(5)->implode(', ') : '').'.'];
    }

    /** Ollama-style runtime: list the downloaded models. */
    private static function localModels($http, string $url): array
    {
        $r = $http->get(rtrim($url, '/').'/api/tags');
        if (! $r->successful()) {
            return [false, 'The local model server answered HTTP '.$r->status().'.'];
        }
        $names = collect($r->json('models', []))->pluck('name');

        return [true, 'The local model server is up with '.$names->count().' '.str('model')->plural($names->count()).($names->isNotEmpty() ? ': '.$names->take(6)->implode(', ') : '').'.'];
    }

    private static function short(string $s): string
    {
        return mb_strimwidth(trim(preg_replace('/\s+/', ' ', strip_tags($s))), 0, 300, '…');
    }
}
