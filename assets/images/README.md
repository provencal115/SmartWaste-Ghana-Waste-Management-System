# SmartWaste Image Assets (Ghana)

All image paths are managed in **`includes/images.php`**. Views use helpers like `img()`, `landingImages()`, and `siteLogo()` — never hardcoded URLs in HTML/CSS.

## Replace any image (for lecturers / demos)

1. Open the folder under `assets/images/`
2. Replace the file **keeping the same filename**
3. Refresh the browser — no code changes needed

Example: swap `testimonials/testimonial-kwame.jpg` with your own Ghanaian resident photo using the same filename.

## Ghana-focused content

Stock images are curated for a **Ghana Smart Waste Management** context:

- Black / African residents, families, and staff portraits
- Waste collectors and collection trucks
- Tropical neighbourhoods and community scenes
- Warehouse inventory and mobile payment workflows

Place your own Ghana photos in `gallery/` — the download script copies `gallery/WhatsApp*.jpeg` to `collectors/collector-with-resident.jpg` when present.

## Folder structure

```text
assets/images/
├── ghana/             Ghana-focused branding (collectors, families, trucks, offices)
├── hero/              Landing hero & CTA backgrounds
├── bins/              Wheelie bin photos (120L / 240L / 360L)
├── collectors/        Collector–resident interactions
├── trucks/            Collection fleet
├── residents/         Residents & neighbourhoods
├── gallery/           gallery-1.jpg … gallery-8.jpg
├── services/          Feature & workflow photos
├── team/              Staff uniforms
├── testimonials/      Customer portrait photos
├── community/         Community impact section
├── dashboard/         Role dashboard banners
├── login/             Auth panel background
├── register/          Registration panel background
├── errors/            404 illustration
├── empty-states/      Dashboard empty-state illustrations
├── logos/             logo.png
├── icons/             favicon.png
└── ghana/             Ghana workflow & branding photos (see below)
```

### Ghana folder (`assets/images/ghana/`)

Curated images for homepage, dashboards, and workflow sections. Replace any file keeping the same filename:

| File | Used for |
|------|----------|
| `collector-uniform-1.jpg` | Collector dashboard banner, workflow |
| `collector-uniform-2.jpg` | Community impact |
| `family-neighbourhood.jpg` | Hero, resident dashboard |
| `truck-community.jpg` | Fleet, gallery, tracking |
| `dustbin-delivery.jpg` | Bin delivery stories |
| `collection-activity.jpg` | Waste collection steps |
| `warehouse-ops.jpg` | Inventory dashboard & features |
| `office-admin.jpg` | Admin dashboard & reports |
| `office-finance.jpg` | Finance dashboard |
| `resident-collector.jpg` | Collector–resident interactions |
| `resident-mobile.jpg` | Mobile app / registration |
| `clean-street.jpg` | CTA, completion steps |
| `happy-residents.jpg` | Community cards |
| `mobile-money.jpg` | Payments feature |
| `modern-home.jpg` | Gallery |
| `recycling-ghana.jpg` | Recycling gallery |

Your WhatsApp Ghana photo is auto-copied to `collectors/collector-with-resident.jpg` and propagated to key slots when present.

## Download / refresh stock photos

```bash
C:\xampp\php\php.exe scripts/download_site_images.php
```

Downloads high-quality royalty-free images into the folders above.

## Videos

Fleet background video lives at `assets/videos/fleet/garbage-truck-ghana.mp4`.  
See **`assets/videos/README.md`** and run `php scripts/download_site_videos.php` for a placeholder clip.

## Bin colours in the UI

Registration and dashboards use **CSS wheelie bins** (`uiMiniBin()`) driven by `binColors()` in `includes/helpers.php`. The selected colour is saved to `residents.selected_bin_color` and shown consistently via `residentBinColor()`.

## Step 2 Bin Assignment

The Bin Assignment section (Step 2) uses `collectors/collector-with-resident.jpg` for the handover photo. Replace that file to change the image without editing code. CSS wheelie bins in Step 2 are rendered in code (not image files).
