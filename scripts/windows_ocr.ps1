param(
    [Parameter(Mandatory=$true)]
    [string]$ImagePath
)

try {
    Add-Type -AssemblyName System.Drawing
    Add-Type -AssemblyName System.Runtime.WindowsRuntime

    $asTaskGeneric = ([System.WindowsRuntimeSystemExtensions].GetMethods() | Where-Object { 
        $_.Name -eq 'AsTask' -and $_.GetParameters().Count -eq 1 -and $_.GetParameters()[0].ParameterType.Name -eq 'IAsyncOperation`1' 
    })[0]

    function Await-Operation($WinRtTask, $ResultType) {
        $asTask = $asTaskGeneric.MakeGenericMethod($ResultType)
        $netTask = $asTask.Invoke($null, @($WinRtTask))
        $netTask.Wait(-1) | Out-Null
        return $netTask.Result
    }

    [Windows.Storage.StorageFile, Windows.Storage, ContentType = WindowsRuntime] | Out-Null
    [Windows.Media.Ocr.OcrEngine, Windows.Media.Ocr, ContentType = WindowsRuntime] | Out-Null
    [Windows.Graphics.Imaging.BitmapDecoder, Windows.Graphics.Imaging, ContentType = WindowsRuntime] | Out-Null

    $absPath = (Resolve-Path $ImagePath).Path
    $targetPath = $absPath
    $tempFileCreated = $false

    # Smart Preprocessing: upscale low/medium resolution passport scans to optimal OCR size (~2200px width)
    try {
        $img = [System.Drawing.Image]::FromFile($absPath)
        $origW = $img.Width
        $origH = $img.Height
        if ($origW -gt 100 -and $origW -lt 2000) {
            $scale = [Math]::Min(2.5, 2200.0 / $origW)
            $newW = [int]($origW * $scale)
            $newH = [int]($origH * $scale)
            $bmp = New-Object System.Drawing.Bitmap $newW, $newH
            $g = [System.Drawing.Graphics]::FromImage($bmp)
            $g.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
            $g.DrawImage($img, 0, 0, $newW, $newH)
            $g.Dispose()

            $tmpFile = [System.IO.Path]::Combine([System.IO.Path]::GetTempPath(), "ocr_scale_" + [System.Guid]::NewGuid().ToString() + ".png")
            $bmp.Save($tmpFile, [System.Drawing.Imaging.ImageFormat]::Png)
            $bmp.Dispose()
            $targetPath = $tmpFile
            $tempFileCreated = $true
        }
        $img.Dispose()
    } catch {
        # Fallback directly to original file if GDI+ pre-scale encounters any error
        $targetPath = $absPath
    }

    $file = Await-Operation ([Windows.Storage.StorageFile]::GetFileFromPathAsync($targetPath)) ([Windows.Storage.StorageFile])
    $stream = Await-Operation ($file.OpenAsync([Windows.Storage.FileAccessMode]::Read)) ([Windows.Storage.Streams.IRandomAccessStream])
    $decoder = Await-Operation ([Windows.Graphics.Imaging.BitmapDecoder]::CreateAsync($stream)) ([Windows.Graphics.Imaging.BitmapDecoder])
    $bitmap = Await-Operation ($decoder.GetSoftwareBitmapAsync()) ([Windows.Graphics.Imaging.SoftwareBitmap])
    
    $ocrEngine = [Windows.Media.Ocr.OcrEngine]::TryCreateFromUserProfileLanguages()
    if ($null -eq $ocrEngine) {
        $ocrEngine = [Windows.Media.Ocr.OcrEngine]::TryCreateFromLanguage([Windows.Globalization.Language]::new("en-US"))
    }

    $ocrResult = Await-Operation ($ocrEngine.RecognizeAsync($bitmap)) ([Windows.Media.Ocr.OcrResult])

    $lines = @()
    $words = @()
    foreach ($line in $ocrResult.Lines) {
        $lines += $line.Text
        foreach ($word in $line.Words) {
            $words += @{
                text = $word.Text
                x = [int]$word.BoundingRect.X
                y = [int]$word.BoundingRect.Y
                w = [int]$word.BoundingRect.Width
                h = [int]$word.BoundingRect.Height
            }
        }
    }

    $stream.Dispose()
    if ($tempFileCreated -and (Test-Path $targetPath)) {
        try { [System.IO.File]::Delete($targetPath) } catch {}
    }

    $output = @{
        success = $true
        text = $ocrResult.Text
        lines = $lines
        words = $words
    }

    $output | ConvertTo-Json -Depth 5
} catch {
    if ($tempFileCreated -and (Test-Path $targetPath)) {
        try { [System.IO.File]::Delete($targetPath) } catch {}
    }
    $errOutput = @{
        success = $false
        error = $_.Exception.Message
    }
    $errOutput | ConvertTo-Json
}
