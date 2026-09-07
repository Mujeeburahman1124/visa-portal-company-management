$loginPage = Invoke-WebRequest -Uri 'http://localhost:8000/auth/login' -SessionVariable session
$csrfToken = ($loginPage.InputFields | Where-Object { $_.name -eq 'csrf_token' }).value

$postData = @{
    email = 'manager@visatrack.com'
    password = 'password123'
    csrf_token = $csrfToken
}

$loginResult = Invoke-WebRequest -Uri 'http://localhost:8000/auth/login' -WebSession $session -Method Post -Body $postData

$appShow = Invoke-WebRequest -Uri 'http://localhost:8000/applications/show?id=4' -WebSession $session
$html = $appShow.Content

Write-Host "Page size:" $html.Length
Write-Host "Contains bootstrap bundle:" $html.Contains('bootstrap.bundle.min.js')
Write-Host "Contains stageTransitionModal:" $html.Contains('id="stageTransitionModal"')
Write-Host "Contains approveVisaModal:" $html.Contains('id="approveVisaModal"')
Write-Host "Contains rejectVisaModal:" $html.Contains('id="rejectVisaModal"')
Write-Host "Contains returnVisaModal:" $html.Contains('id="returnVisaModal"')
Write-Host "Contains openModalById script:" $html.Contains('window.openModalById')
Write-Host "Script tag count:" ([regex]::Matches($html, '<script').Count)
Write-Host "Closing script tag count:" ([regex]::Matches($html, '</script>').Count)
