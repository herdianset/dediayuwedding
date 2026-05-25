param(
  [string]$SourceDir = "foto",
  [string]$OutputDir = "foto/optimized",
  [int]$MaxLongEdge = 1920,
  [int]$Quality = 72
)

$ErrorActionPreference = "Stop"
Add-Type -AssemblyName System.Drawing

if ($Quality -lt 1 -or $Quality -gt 100) {
  throw "Quality harus antara 1-100."
}

New-Item -ItemType Directory -Path $OutputDir -Force | Out-Null

$jpegEncoder = [System.Drawing.Imaging.ImageCodecInfo]::GetImageEncoders() |
  Where-Object { $_.MimeType -eq "image/jpeg" }

Get-ChildItem -Path $SourceDir -File -Filter *.jpg | ForEach-Object {
  $srcPath = $_.FullName
  $dstPath = Join-Path $OutputDir $_.Name

  $img = [System.Drawing.Image]::FromFile($srcPath)
  try {
    $w = $img.Width
    $h = $img.Height

    if ($w -ge $h) {
      $newW = [int]([math]::Min($w, $MaxLongEdge))
      $newH = [int]([math]::Round($h * $newW / $w))
    } else {
      $newH = [int]([math]::Min($h, $MaxLongEdge))
      $newW = [int]([math]::Round($w * $newH / $h))
    }

    $bmp = New-Object System.Drawing.Bitmap $newW, $newH
    $g = [System.Drawing.Graphics]::FromImage($bmp)
    try {
      $g.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
      $g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::HighQuality
      $g.PixelOffsetMode = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality
      $g.CompositingQuality = [System.Drawing.Drawing2D.CompositingQuality]::HighQuality
      $g.DrawImage($img, 0, 0, $newW, $newH)

      $ep = New-Object System.Drawing.Imaging.EncoderParameters(1)
      $ep.Param[0] = New-Object System.Drawing.Imaging.EncoderParameter(
        [System.Drawing.Imaging.Encoder]::Quality,
        [long]$Quality
      )

      $bmp.Save($dstPath, $jpegEncoder, $ep)
    } finally {
      $g.Dispose()
      $bmp.Dispose()
    }
  } finally {
    $img.Dispose()
  }
}

Write-Host "Selesai: gambar terkompresi ada di '$OutputDir'"
