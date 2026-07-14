<?php

namespace Springtimesoft\CloudflareAssetsPurge\Jobs;

use SilverStripe\Core\Environment;
use Symbiote\QueuedJobs\Services\AbstractQueuedJob;

/**
 * Queued job that purges the entire Cloudflare cache for the website zone.
 *
 * Calls the Cloudflare Cache Purge API with `purge_everything: true`, which
 * invalidates all cached assets across the zone rather than targeting specific
 * URLs or cache tags.
 *
 * @see https://developers.cloudflare.com/api/resources/cache/methods/purge/
 *
 * Required environment variables:
 *   CLOUDFLARE_PURGE_ZONE_ID    - The Cloudflare zone ID for this website
 *   CLOUDFLARE_PURGE_API_TOKEN  - A token with Cache Purge permission on the zone
 */
class PurgeWebsiteAssetsJob extends AbstractQueuedJob
{
    public function getTitle(): string
    {
        return 'Purge Website Assets from Cloudflare';
    }

    public function setup(): void
    {
        $this->totalSteps = 1;
    }

    /**
     * Posts a purge_everything request to the Cloudflare Cache Purge API.
     *
     * Exits early without error if credentials are missing, so the job can be
     * queued in non-production environments where Cloudflare is not configured.
     */
    public function process(): void
    {
        $zoneID   = Environment::getEnv('CLOUDFLARE_PURGE_ZONE_ID');
        $apiToken = Environment::getEnv('CLOUDFLARE_PURGE_API_TOKEN');

        // Skip gracefully in environments where Cloudflare is not configured.
        if (!$zoneID || !$apiToken) {
            $this->addMessage('Cloudflare credentials not found, skipping.');
            $this->isComplete = true;

            return;
        }
        $this->addMessage('Cloudflare environment credentials found.');

        // purge_everything invalidates the whole zone; no URL list needed.
        $data = json_encode(['purge_everything' => true]);
        $url  = "https://api.cloudflare.com/client/v4/zones/{$zoneID}/purge_cache";
        $ch   = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$apiToken}",
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // curl_errno only catches network-level failures (DNS, connection refused).
        // HTTP 4xx/5xx responses do not set a curl error.
        if (curl_errno($ch)) {
            $this->addMessage('Cloudflare curl error: ' . curl_error($ch));
            $this->isComplete = true;

            return;
        }

        $this->addMessage('Connected to Cloudflare.');

        $result = json_decode($response, true);

        // Guard against three failure modes:
        //   - non-200 HTTP code (e.g. a proxy or WAF returning a plain-text error page)
        //   - null result (non-JSON body, so json_decode returned null)
        //   - Cloudflare reporting failure in the JSON payload
        if (200 !== $httpCode || !$result || !$result['success']) {
            // Fall back to the raw HTTP code and body if Cloudflare did not return
            // a structured errors array (e.g. the response body was not JSON).
            $errors = $result['errors'] ?? ['HTTP ' . $httpCode . ': ' . $response];
            $this->addMessage('Failed to purge Cloudflare cache: ' . json_encode($errors));
            $this->isComplete = true;

            return;
        }

        $this->addMessage('Successfully purged Cloudflare cache.');
        $this->currentStep = 1;
        $this->isComplete  = true;
    }
}
