<?php

namespace Springtimesoft\CloudflareAssetsPurge\Extensions;

use SilverStripe\Core\Environment;
use SilverStripe\Core\Extension;
use Springtimesoft\CloudflareAssetsPurge\Jobs\PurgeWebsiteAssetsJob;
use Symbiote\QueuedJobs\Services\QueuedJobService;

/**
 * Queues a Cloudflare cache purge whenever a File or Image is published or deleted.
 *
 * Applied to SilverStripe\Assets\File, which covers both File and Image subclasses.
 * Draft-only saves do not trigger a purge — only publishing to the live stage does.
 * Deleting a draft-only file is also skipped since nothing is cached yet.
 */
class FileCachePurgeExtension extends Extension
{
    public function onAfterPublish(): void
    {
        if (!$this->hasCloudflareCredentials()) {
            return;
        }
        QueuedJobService::singleton()->queueJob(new PurgeWebsiteAssetsJob());
    }

    public function onBeforeDelete(): void
    {
        if (!$this->hasCloudflareCredentials()) {
            return;
        }
        // Only purge if a live version exists — draft-only files have nothing cached.
        if ($this->owner->isPublished()) {
            QueuedJobService::singleton()->queueJob(new PurgeWebsiteAssetsJob());
        }
    }

    public function onAfterUnpublish(): void
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
