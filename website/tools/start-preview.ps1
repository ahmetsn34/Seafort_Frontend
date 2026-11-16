[CmdletBinding()]
param(
    [ValidateRange(1024, 65535)]
    [int]$Port = 8123,
    [switch]$CheckOnly
)

$ErrorActionPreference = 'Stop'
$websiteRoot = Split-Path -Parent $PSScriptRoot
$exitCode = 0
$phpCandidates = @()
$pathPhp = Get-Command php -CommandType Application -ErrorAction SilentlyContinue | Select-Object -First 1
if ($null -ne $pathPhp) { $phpCandidates += $pathPhp.Source }
$xamppPhp = 'C:\xampp\php\php.exe'
if ((Test-Path -LiteralPath $xamppPhp -PathType Leaf) -and $phpCandidates -notcontains $xamppPhp) {
    $phpCandidates += $xamppPhp
}
$phpExecutable = $null
foreach ($candidate in $phpCandidates) {
    & $candidate -r 'exit(PHP_VERSION_ID >= 80200 ? 0 : 1);'
    if ($LASTEXITCODE -eq 0) { $phpExecutable = $candidate; break }
}
if ($null -eq $phpExecutable) {
    Write-Host 'PHP 8.2+ bulunamadi. PHP PATH ayarini veya C:\xampp\php\php.exe dosyasini kontrol edin.' -ForegroundColor Red
    exit 1
}

Push-Location -LiteralPath $websiteRoot
try {
    $modules = @(& $phpExecutable -m)
    if ($LASTEXITCODE -ne 0) { throw 'PHP eklenti listesi okunamadi.' }
    $missingModules = @('fileinfo', 'json', 'session', 'openssl') | Where-Object { $modules -notcontains $_ }
    if ($missingModules) { throw ('Gerekli PHP eklentileri eksik: ' + ($missingModules -join ', ')) }

    Write-Host ('PHP: ' + $phpExecutable)
    & $phpExecutable (Join-Path $PSScriptRoot 'setup.php')
    if ($LASTEXITCODE -ne 0) { throw 'Ilk kurulum tamamlanamadi. Mevcut dosyalar silinmedi.' }
    & $phpExecutable (Join-Path $PSScriptRoot 'migrate-forms.php')
    if ($LASTEXITCODE -ne 0) { throw 'Form metni gecisi tamamlanamadi.' }

    if ($CheckOnly) {
        Write-Host 'Kontrol tamamlandi. Mevcut hesaplar ve kayitlar korundu; sunucu baslatilmadi.' -ForegroundColor Green
    } else {
        $portProbe = New-Object System.Net.Sockets.TcpListener([System.Net.IPAddress]::Loopback, $Port)
        try { $portProbe.Start() }
        catch { throw ('127.0.0.1:' + $Port + ' kullanimda. Acik onizlemeyi kullanin veya -Port ile baska port secin.') }
        finally { $portProbe.Stop() }

        $previewUrl = 'http://127.0.0.1:' + $Port
        Write-Host ''
        Write-Host ('Seafort onizleme: ' + $previewUrl) -ForegroundColor Cyan
        Write-Host ('Yonetim paneli:  ' + $previewUrl + '/admin/')
        Write-Host 'Bu pencere acik kalmalidir. Durdurmak icin Ctrl+C.'
        Write-Host 'Formlarin varsayilan onizleme modunda e-posta gonderilmez.'
        Write-Host ''
        & $phpExecutable -S ('127.0.0.1:' + $Port) -t 'public_html' 'tools/router.php'
        if ($LASTEXITCODE -ne 0) { $exitCode = $LASTEXITCODE }
    }
} catch {
    Write-Host $_.Exception.Message -ForegroundColor Red
    $exitCode = 1
} finally {
    Pop-Location
}
exit $exitCode
