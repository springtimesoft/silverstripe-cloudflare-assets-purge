# silverstripe-cloudflare-assets-purge

> **Beta:** This module is currently in beta for SilverStripe 5. A full release with SilverStripe 6 compatibility is planned.

Automatically purges the Cloudflare cache when SilverStripe assets are published, unpublished, or deleted, and after a `dev/build`.

## Requirements

- SilverStripe 5
- [symbiote/silverstripe-queuedjobs](https://github.com/symbiote/silverstripe-queuedjobs) ^5

## Installation

```bash
composer require springtimesoft/silverstripe-cloudflare-assets-purge
```

## Configuration

Add the following environment variables to your `.env`:

```
CLOUDFLARE_PURGE_ZONE_ID="your-zone-id"
CLOUDFLARE_PURGE_API_TOKEN="your-api-token"
```

The API token requires the **Cache Purge** permission on the target zone.

If either variable is absent the module silently does nothing, so it is safe to install in environments where Cloudflare is not configured (e.g. local development).

## How it works

- **File / Image publish** — queues a full-zone cache purge via `FileCachePurgeExtension::onAfterPublish`.
- **File / Image unpublish** — queues a purge via `FileCachePurgeExtension::onAfterUnpublish`.
- **File / Image delete** — queues a purge only when a live version exists (draft-only files have nothing cached).
- **dev/build** — queues a purge via `DevBuildCachePurgeExtension::onAfterBuild`.

Purges are handled asynchronously by `PurgeWebsiteAssetsJob`, which calls the [Cloudflare Cache Purge API](https://developers.cloudflare.com/api/resources/cache/methods/purge/) with `purge_everything: true`. This flushes the **entire zone cache** — not just assets — so pages, CSS, JS, and any other cached responses will also be invalidated.

## License

BSD-3-Clause
