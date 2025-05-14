## PleTrakt

Automatically track movies and TV shows on Trakt using Plex webhooks.

Why not just use the official Plex Scrobbler?

While the official Plex Scrobbler works well, it sends a broader set of data to Trakt. This project uses Plex webhooks instead, giving you more control over what gets shared — keeping your privacy in your own hands.

Features:

    ✅ Tracks both Movies and TV episodes

    ✅ Uses Plex webhook events

    ✅ Minimal data shared with Trakt — just what’s necessary

    ✅ Customizable and self-hosted

How it works

Plex sends a webhook when playback starts. This script parses the event and sends the appropriate scrobble or start watching request to Trakt — all without revealing unnecessary personal metadata.
