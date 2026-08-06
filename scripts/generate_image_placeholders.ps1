# Creates placeholder images under assets/images/
# Run: powershell -ExecutionPolicy Bypass -File scripts/generate_image_placeholders.ps1

$root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$base = Join-Path $root "assets\images"

$dirs = @("hero","bins","collectors","trucks","residents","gallery","services","team","logos","icons")
foreach ($d in $dirs) {
    New-Item -ItemType Directory -Force -Path (Join-Path $base $d) | Out-Null
}

Add-Type -AssemblyName System.Drawing

function New-PlaceholderJpg {
    param([string]$Path, [int]$W, [int]$H, [string]$Label)
    $bmp = New-Object System.Drawing.Bitmap $W, $H
    $g = [System.Drawing.Graphics]::FromImage($bmp)
    $g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
    $g.Clear([System.Drawing.Color]::FromArgb(226, 232, 240))
    $accent = [System.Drawing.Color]::FromArgb(16, 185, 129)
    $g.FillRectangle((New-Object System.Drawing.SolidBrush $accent), 0, 0, $W, 6)
    $dark = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(51, 65, 85))
    $muted = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(100, 116, 139))
    $fontLg = New-Object System.Drawing.Font "Segoe UI", 18, [System.Drawing.FontStyle]::Bold
    $fontMd = New-Object System.Drawing.Font "Segoe UI", 12
    $fontSm = New-Object System.Drawing.Font "Segoe UI", 9
    $name = [System.IO.Path]::GetFileName($Path)
    $g.DrawString("SmartWaste Placeholder", $fontLg, $dark, 20, ($H / 2) - 40)
    $g.DrawString($name, $fontMd, (New-Object System.Drawing.SolidBrush $accent), 20, ($H / 2) - 10)
    $g.DrawString($Label, $fontSm, $muted, 20, ($H / 2) + 18)
    $g.DrawString("$W x $H - replace with your photo", $fontSm, $muted, 20, $H - 32)
    $dir = Split-Path $Path -Parent
    if (-not (Test-Path $dir)) { New-Item -ItemType Directory -Force -Path $dir | Out-Null }
    $bmp.Save($Path, [System.Drawing.Imaging.ImageFormat]::Jpeg)
    $g.Dispose(); $bmp.Dispose()
    Write-Host "Created $Path"
}

function New-PlaceholderBinPng {
    param([string]$Path, [int]$W, [int]$H, [string]$Label, [int[]]$Rgb)
    $bmp = New-Object System.Drawing.Bitmap $W, $H
    $g = [System.Drawing.Graphics]::FromImage($bmp)
    $g.Clear([System.Drawing.Color]::Transparent)
    $body = [System.Drawing.Color]::FromArgb($Rgb[0], $Rgb[1], $Rgb[2])
    $dark = [System.Drawing.Color]::FromArgb([int]($Rgb[0]*0.65), [int]($Rgb[1]*0.65), [int]($Rgb[2]*0.65))
    $bodyW = [int]($W * 0.55); $bodyH = [int]($H * 0.72)
    $x = [int](($W - $bodyW) / 2); $y = [int]($H * 0.12)
    $g.FillRectangle((New-Object System.Drawing.SolidBrush $body), $x, $y, $bodyW, $bodyH)
    $g.FillRectangle((New-Object System.Drawing.SolidBrush $dark), ($x-4), ($y-10), ($bodyW+8), 10)
    $g.FillEllipse([System.Drawing.Brushes]::Black, ($x+5), ($y+$bodyH), 18, 18)
    $g.FillEllipse([System.Drawing.Brushes]::Black, ($x+$bodyW-23), ($y+$bodyH), 18, 18)
    $g.DrawString($Label, (New-Object System.Drawing.Font "Segoe UI", 10, [System.Drawing.FontStyle]::Bold), [System.Drawing.Brushes]::White, ($x+8), ($y+$bodyH/2))
    $bmp.Save($Path, [System.Drawing.Imaging.ImageFormat]::Png)
    $g.Dispose(); $bmp.Dispose()
    Write-Host "Created $Path"
}

function New-LogoPng {
    param([string]$Path, [int]$Size)
    $bmp = New-Object System.Drawing.Bitmap $Size, $Size
    $g = [System.Drawing.Graphics]::FromImage($bmp)
    $g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
    $g.Clear([System.Drawing.Color]::Transparent)
    $g.FillEllipse((New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(16,185,129))), 4, 4, ($Size-8), ($Size-8))
    $g.DrawString("SW", (New-Object System.Drawing.Font "Segoe UI", ($Size/4), [System.Drawing.FontStyle]::Bold), [System.Drawing.Brushes]::White, ($Size/2-20), ($Size/2-16))
    $bmp.Save($Path, [System.Drawing.Imaging.ImageFormat]::Png)
    $g.Dispose(); $bmp.Dispose()
    Write-Host "Created $Path"
}

$photos = @{
    "hero\hero-banner.jpg" = @(1400, 880, "Hero - clean Ghanaian neighbourhood")
    "hero\hero-truck.jpg" = @(800, 600, "Hero collage - collection truck")
    "hero\hero-collector.jpg" = @(800, 600, "Hero collage - uniformed collector")
    "hero\hero-resident.jpg" = @(800, 600, "Hero collage - resident at home")
    "hero\cta-background.jpg" = @(1600, 900, "Final CTA background")
    "collectors\collector-with-resident.jpg" = @(1200, 900, "Officer handing bin to resident")
    "collectors\collector-greeting-resident.jpg" = @(900, 675, "Collector greeting resident")
    "collectors\collector-at-home.jpg" = @(900, 675, "Collector with bin at home")
    "trucks\garbage-truck-1.jpg" = @(1000, 750, "Garbage collection truck")
    "trucks\garbage-truck-2.jpg" = @(1000, 750, "Fleet truck - alternate angle")
    "residents\ghana-family.jpg" = @(800, 600, "Ghanaian family / residents")
    "residents\resident-using-app.jpg" = @(900, 675, "Resident using phone or computer")
    "residents\happy-customers.jpg" = @(900, 675, "Happy customers after collection")
    "residents\clean-neighbourhood.jpg" = @(800, 600, "Clean neighbourhood street")
    "services\scheduling.jpg" = @(700, 525, "Scheduling and calendar")
    "services\inventory.jpg" = @(700, 525, "Warehouse inventory")
    "services\mobile-payments.jpg" = @(700, 525, "Mobile Money payments")
    "services\tracking.jpg" = @(700, 525, "GPS collection tracking")
    "services\notifications.jpg" = @(700, 525, "SMS and app notifications")
    "services\reports.jpg" = @(700, 525, "Reports and analytics")
    "services\route-optimisation.jpg" = @(700, 525, "Route optimisation map")
    "services\recycling.jpg" = @(700, 525, "Recycling and sorting")
    "services\waste-collection.jpg" = @(900, 675, "Waste collection in progress")
    "team\team-uniforms.jpg" = @(1000, 750, "Staff in safety uniforms")
    "team\testimonial-kwame.jpg" = @(300, 300, "Kwame Asante - testimonial")
    "team\testimonial-ama.jpg" = @(300, 300, "Ama Serwaa - testimonial")
    "team\testimonial-emmanuel.jpg" = @(300, 300, "Emmanuel Mensah - testimonial")
}

for ($i = 1; $i -le 8; $i++) {
    $photos["gallery\gallery-$i.jpg"] = @(700, 525, "Gallery image $i")
}

foreach ($rel in $photos.Keys) {
    $meta = $photos[$rel]
    New-PlaceholderJpg (Join-Path $base $rel) $meta[0] $meta[1] $meta[2]
}

New-PlaceholderBinPng (Join-Path $base "bins\small-bin.png") 280 360 "120L" @(34,197,94)
New-PlaceholderBinPng (Join-Path $base "bins\medium-bin.png") 320 400 "240L" @(59,130,246)
New-PlaceholderBinPng (Join-Path $base "bins\large-bin.png") 360 440 "360L" @(30,30,30)
New-LogoPng (Join-Path $base "logos\logo.png") 200
New-LogoPng (Join-Path $base "icons\favicon.png") 64

Write-Host "`nDone. Replace files in assets/images/ keeping the same names."
