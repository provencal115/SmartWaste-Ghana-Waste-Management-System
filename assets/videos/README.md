# SmartWaste Video Assets

Videos are managed in **`includes/images.php`** via `video()` and `fleetOperationsVideo()`.

## Replace the fleet background video

1. Record or obtain your Ghana waste collection footage (MP4, H.264 recommended)
2. Save it as:

```text
assets/videos/fleet/garbage-truck-ghana.mp4
```

3. Refresh the home page — no code changes required.

Optional: update the poster frame by replacing `assets/images/collectors/collector-with-resident.jpg`.

## Download placeholder stock video

```bash
C:\xampp\php\php.exe scripts/download_site_videos.php
```

This downloads royalty-free placeholder footage until you add your own Ghana recording.

## Fleet section behaviour

- Autoplay, muted, loop, playsinline
- Playback speed ~0.65× (premium slow motion) via `data-playback-rate` in `main.js`
- Poster image while loading
- Respects `prefers-reduced-motion` (shows static Ghana image instead)
