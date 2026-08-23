# Silverstripe Cloudflare Assets Purge

Automatically purges the Cloudflare cache when Silverstripe assets are published, unpublished, or deleted, and after a `dev/build`.

## Requirements

- Silverstripe ^5
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

The API token requires the **Cache Purge** permission on the target zone. [See below](#creating-a-cloudflare-api-key) for instructions on creating a Cloudflare API token (either account level, or using personal access token).

If either variable is absent the module silently does nothing, so it is safe to install in environments where Cloudflare is not configured (e.g. local development).

Additionally, you must have the [queued jobs](https://github.com/symbiote/silverstripe-queuedjobs) module installed, with a cron job configured to process its queue.

## How it works

- **File / Image publish** - queues a full-zone cache purge via `FileCachePurgeExtension::onAfterPublish`.
- **File / Image unpublish** - queues a purge via `FileCachePurgeExtension::onAfterUnpublish`.
- **File / Image delete** - queues a purge only when a live version exists (draft-only files have nothing cached).
- **dev/build** - queues a purge via `DevBuildCachePurgeExtension::onAfterBuild`.

Purges are handled asynchronously by `PurgeWebsiteAssetsJob`, which calls the [Cloudflare Cache Purge API](https://developers.cloudflare.com/api/resources/cache/methods/purge/) with `purge_everything: true`. This flushes the **entire zone cache** - not just assets - so pages, CSS, JS, and any other cached responses will also be invalidated.

## Creating a Cloudflare API Key

You will need to configure this module with an existing Cloudflare API token. This API token can either be an Account API token, or a personal access token.

### Creating an account API token

1. Log in to your Cloudflare account.
2. Navigate to your account → `Manage account` → `Account API tokens`.
3. Click `Create Token`.
4. Provide a reference token name.
5. Select `Start from scratch`.
6. In the `Edit policy` section, change `Entire account` to `Specified Domains`, and select your domain from the list.
7. In the `Search for permission groups` search for `Cache` and select the `Purge` option.
8. `Token expiration`: No expiration.
9. `Client IP address filtering`: leave blank.
10. Click `Review token`, then `Create token`
11. Make a note of the API token (this will be displayed only once). Note: that the account ID is not the same as the zone ID. You can find your zone ID in the Cloudflare dashboard under `Overview` for your domain (bottom right of page).
12. Add the token to your `.env` file as `CLOUDFLARE_PURGE_API_TOKEN` and the zone ID as `CLOUDFLARE_PURGE_ZONE_ID`.

### Creating a personal access token

1. Within your Cloudflare account, navigate to `My Profile` → [`API Tokens`](https://dash.cloudflare.com/profile/api-tokens).
2. Select `Create Custom Token`.
3. Provide a reference token name.
4. Under permissions, select `Zone` → `Cache Purge` → `Purge`.
5. Under `Zone Resources`, select `Include` → `Specific zone` and select your domain from the list.
6. Leave the remaining fields blank, and click `Continue to summary`.
7. Click "Create Token" and make a note of the API token (this will be displayed only once).
8. You can find your zone ID in the Cloudflare dashboard under `Overview` for your domain (bottom right of page).
9. Add the token to your `.env` file as `CLOUDFLARE_PURGE_API_TOKEN` and the zone ID as `CLOUDFLARE_PURGE_ZONE_ID`.

## License

MIT License. See [LICENSE](LICENSE) for details.
