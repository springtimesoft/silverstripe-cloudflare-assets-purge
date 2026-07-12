<?php

namespace Springtimesoft\CloudflareAssetsPurge\Extensions;

use SilverStripe\Core\Environment;
use SilverStripe\Core\Extension;
use Springtimesoft\CloudflareAssetsPurge\Jobs\PurgeWebsiteAssetsJob;
use Symbiote\QueuedJobs\Services\QueuedJobService;

/**
 * Queues a Cloudflare cache purge after a dev/build.
 *
 * Applied to SilverStripe\ORM\DatabaseAdmin.
 */
class DevBuildCachePurgeExtension extends Extension
{
    public function onAfterBuild(): void
    {
        if (!$this->hasCloudflareCredentials()) {
            return;
        }
        QueuedJobService::singleton()->queueJob(new PurgeWebsiteAssetsJob());
    }

    private function hasCloudflareCredentials(): bool
    {
        return (bool) Environment::getEnv('CLOUDFLARE_PURGE_ZONE_ID')
            && (bool) Environment::getEnv('CLOUDFLARE_PURGE_API_TOKEN');
    }
}
