# Downloads high-quality royalty-free stock images (Unsplash / Pexels) into assets/images/
# Run: powershell -ExecutionPolicy Bypass -File scripts/download_stock_images.ps1

$ErrorActionPreference = "Stop"
$root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$base = Join-Path $root "assets\images"
$manifestDir = Join-Path $base "placeholders"
New-Item -ItemType Directory -Force -Path $manifestDir | Out-Null

$downloads = @(
    @{ file = "hero/hero-banner.jpg";       url = "https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=1920&q=90&auto=format&fit=crop"; credit = "Unsplash tropical residential home" }
    @{ file = "hero/hero-truck.jpg";        url = "https://images.unsplash.com/photo-1530587191320-3e8797cab7d4?w=1400&q=90&auto=format&fit=crop"; credit = "Unsplash garbage collection truck" }
    @{ file = "hero/hero-collector.jpg";    url = "https://images.unsplash.com/photo-1581092160562-40aa08e78837?w=1400&q=90&auto=format&fit=crop"; credit = "Unsplash worker in safety vest" }
    @{ file = "hero/hero-resident.jpg";     url = "https://images.unsplash.com/photo-1609220667125-4b9c2a4d4a5e?w=1400&q=90&auto=format&fit=crop"; credit = "Unsplash resident at home" }
    @{ file = "hero/cta-background.jpg";    url = "https://images.unsplash.com/photo-1449844908441-8829872d2607?w=1920&q=90&auto=format&fit=crop"; credit = "Unsplash clean neighbourhood street" }

    @{ file = "collectors/collector-with-resident.jpg";     url = "https://images.unsplash.com/photo-1600880292203-757bb62b4baf?w=1600&q=90&auto=format&fit=crop"; credit = "Unsplash professional interaction" }
    @{ file = "collectors/collector-greeting-resident.jpg"; url = "https://images.unsplash.com/photo-1621451537084-315e6e4f6a2a?w=1400&q=90&auto=format&fit=crop"; credit = "Unsplash sanitation worker" }
    @{ file = "collectors/collector-at-home.jpg";           url = "https://images.unsplash.com/photo-1581578731548-c64695cc6952?w=1400&q=90&auto=format&fit=crop"; credit = "Unsplash cleaner at residential property" }

    @{ file = "trucks/garbage-truck-1.jpg"; url = "https://images.unsplash.com/photo-1530587191320-3e8797cab7d4?w=1600&q=90&auto=format&fit=crop"; credit = "Unsplash waste collection truck" }
    @{ file = "trucks/garbage-truck-2.jpg"; url = "https://images.unsplash.com/photo-1595278069443-859d4d0c5a0a?w=1600&q=90&auto=format&fit=crop"; credit = "Unsplash municipal truck on route" }

    @{ file = "residents/ghana-family.jpg";        url = "https://images.unsplash.com/photo-1531386140190-87db6b388329?w=1400&q=90&auto=format&fit=crop"; credit = "Unsplash community residents" }
    @{ file = "residents/resident-using-app.jpg"; url = "https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?w=1400&q=90&auto=format&fit=crop"; credit = "Unsplash resident using smartphone" }
    @{ file = "residents/happy-customers.jpg";     url = "https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=1400&q=90&auto=format&fit=crop"; credit = "Unsplash satisfied customers" }
    @{ file = "residents/clean-neighbourhood.jpg"; url = "https://images.unsplash.com/photo-1572120360610-d971b9d2a294?w=1400&q=90&auto=format&fit=crop"; credit = "Unsplash well-kept residential street" }

    @{ file = "bins/small-bin.jpg";  url = "https://images.unsplash.com/photo-1611284446314-60cd14764ed3?w=900&q=90&auto=format&fit=crop"; credit = "Unsplash colour-coded wheelie bins 120L" }
    @{ file = "bins/medium-bin.jpg"; url = "https://images.unsplash.com/photo-1611284446314-60cd14764ed3?w=1200&q=90&auto=format&fit=crop"; credit = "Unsplash municipal bins 240L" }
    @{ file = "bins/large-bin.jpg";  url = "https://images.pexels.com/photos/4099465/pexels-photo-4099465.jpeg?auto=compress&cs=tinysrgb&w=1200"; credit = "Pexels waste wheelie bins 360L" }

    @{ file = "services/scheduling.jpg";         url = "https://images.unsplash.com/photo-1506784365857-bbad939e9335?w=1200&q=90&auto=format&fit=crop"; credit = "Unsplash calendar scheduling" }
    @{ file = "services/inventory.jpg";          url = "https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?w=1200&q=90&auto=format&fit=crop"; credit = "Unsplash warehouse inventory" }
    @{ file = "services/mobile-payments.jpg";    url = "https://images.unsplash.com/photo-1563013544-824ae1b704d3?w=1200&q=90&auto=format&fit=crop"; credit = "Unsplash mobile payment" }
    @{ file = "services/tracking.jpg";            url = "https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=1200&q=90&auto=format&fit=crop"; credit = "Unsplash analytics GPS tracking" }
    @{ file = "services/notifications.jpg";       url = "https://images.unsplash.com/photo-1516321318423-f06f85b504e3?w=1200&q=90&auto=format&fit=crop"; credit = "Unsplash notifications on devices" }
    @{ file = "services/reports.jpg";             url = "https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=1200&q=90&auto=format&fit=crop"; credit = "Unsplash business reports" }
    @{ file = "services/route-optimisation.jpg"; url = "https://images.unsplash.com/photo-1524661135-423995f22d0b?w=1200&q=90&auto=format&fit=crop"; credit = "Unsplash route map" }
    @{ file = "services/recycling.jpg";           url = "https://images.unsplash.com/photo-1532996122724-e3c039a743b0?w=1200&q=90&auto=format&fit=crop"; credit = "Unsplash recycling sorting" }
    @{ file = "services/waste-collection.jpg";   url = "https://images.unsplash.com/photo-1621451539298-ee-c3fd06132f9?w=1400&q=90&auto=format&fit=crop"; credit = "Unsplash waste collection operations" }

    @{ file = "gallery/gallery-1.jpg"; url = "https://images.unsplash.com/photo-1530587191320-3e8797cab7d4?w=1400&q=90&auto=format&fit=crop"; credit = "Unsplash truck collecting waste" }
    @{ file = "gallery/gallery-2.jpg"; url = "https://images.unsplash.com/photo-1600880292203-757bb62b4baf?w=1400&q=90&auto=format&fit=crop"; credit = "Unsplash collector and resident" }
    @{ file = "gallery/gallery-3.jpg"; url = "https://images.unsplash.com/photo-1611284446314-60cd14764ed3?w=1400&q=90&auto=format&fit=crop"; credit = "Unsplash bin sizes and colours" }
    @{ file = "gallery/gallery-4.jpg"; url = "https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?w=1400&q=90&auto=format&fit=crop"; credit = "Unsplash online registration" }
    @{ file = "gallery/gallery-5.jpg"; url = "https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?w=1400&q=90&auto=format&fit=crop"; credit = "Unsplash warehouse bins" }
    @{ file = "gallery/gallery-6.jpg"; url = "https://images.unsplash.com/photo-1532996122724-e3c039a743b0?w=1400&q=90&auto=format&fit=crop"; credit = "Unsplash recycling" }
    @{ file = "gallery/gallery-7.jpg"; url = "https://images.unsplash.com/photo-1449844908441-8829872d2607?w=1400&q=90&auto=format&fit=crop"; credit = "Unsplash clean neighbourhood" }
    @{ file = "gallery/gallery-8.jpg"; url = "https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=1400&q=90&auto=format&fit=crop"; credit = "Unsplash happy customers" }

    @{ file = "team/team-uniforms.jpg";        url = "https://images.unsplash.com/photo-1581092160562-40aa08e78837?w=1600&q=90&auto=format&fit=crop"; credit = "Unsplash staff in safety gear" }
    @{ file = "team/testimonial-kwame.jpg";    url = "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=600&q=90&auto=format&fit=crop"; credit = "Unsplash portrait Kwame placeholder" }
    @{ file = "team/testimonial-ama.jpg";      url = "https://images.unsplash.com/photo-1594744803320-9d46c1d0e8c2?w=600&q=90&auto=format&fit=crop"; credit = "Unsplash portrait Ama placeholder" }
    @{ file = "team/testimonial-emmanuel.jpg"; url = "https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=600&q=90&auto=format&fit=crop"; credit = "Unsplash portrait Emmanuel placeholder" }
)

$manifest = @()
$headers = @{ "User-Agent" = "SmartWaste-ImageSetup/1.0" }

foreach ($item in $downloads) {
    $rel = $item.file
    $dest = Join-Path $base ($rel -replace "/", "\")
    $dir = Split-Path $dest -Parent
    if (-not (Test-Path $dir)) { New-Item -ItemType Directory -Force -Path $dir | Out-Null }

    Write-Host "Downloading $rel ..."
    try {
        Invoke-WebRequest -Uri $item.url -OutFile $dest -Headers $headers -UseBasicParsing -TimeoutSec 120
        $size = (Get-Item $dest).Length
        Write-Host "  OK ($size bytes)"
        $manifest += [pscustomobject]@{ file = $rel; source = $item.url; credit = $item.credit; bytes = $size }
    } catch {
        Write-Warning "  FAILED: $rel - $($_.Exception.Message)"
        $manifest += [pscustomobject]@{ file = $rel; source = $item.url; credit = $item.credit; bytes = 0; error = $_.Exception.Message }
    }
}

$manifest | ConvertTo-Json -Depth 3 | Set-Content (Join-Path $manifestDir "stock-sources.json") -Encoding UTF8
Write-Host "`nDone. $($manifest.Count) files processed."
