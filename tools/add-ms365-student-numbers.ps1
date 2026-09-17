param(
    [Parameter(Mandatory = $true)]
    [string] $UsersCsv,

    [Parameter(Mandatory = $true)]
    [string] $MasterlistCsv,

    [Parameter(Mandatory = $true)]
    [string] $OutputDirectory
)

$ErrorActionPreference = 'Stop'

function Normalize-NamePart {
    param([AllowNull()][string] $Value)

    if ([string]::IsNullOrWhiteSpace($Value)) {
        return ''
    }

    return (($Value.Trim().TrimStart('`').ToLowerInvariant()) -replace '[^\p{L}\p{Nd}]', '')
}

function Get-FirstLastKey {
    param(
        [AllowNull()][string] $FirstName,
        [AllowNull()][string] $LastName
    )

    $first = Normalize-NamePart $FirstName
    $last = Normalize-NamePart $LastName

    if ($first -and $last) {
        return "$first|$last"
    }

    return $null
}

function Get-FullNameKey {
    param(
        [AllowNull()][string] $FirstName,
        [AllowNull()][string] $MiddleName,
        [AllowNull()][string] $LastName
    )

    $name = (Normalize-NamePart $FirstName) + (Normalize-NamePart $MiddleName) + (Normalize-NamePart $LastName)

    if ($name) {
        return $name
    }

    return $null
}

function Add-ToNameIndex {
    param(
        [hashtable] $Index,
        [AllowNull()][string] $Key,
        [pscustomobject] $Entry
    )

    if (-not $Key) {
        return
    }

    if (-not $Index.ContainsKey($Key)) {
        $Index[$Key] = @()
    }

    $Index[$Key] += $Entry
}

if (-not (Test-Path -LiteralPath $UsersCsv)) {
    throw "MS365 users CSV was not found: $UsersCsv"
}

if (-not (Test-Path -LiteralPath $MasterlistCsv)) {
    throw "BSIT student masterlist CSV was not found: $MasterlistCsv"
}

$users = @(Import-Csv -LiteralPath $UsersCsv)
$masterlist = @(Import-Csv -LiteralPath $MasterlistCsv)

if ($users.Count -eq 0 -or $masterlist.Count -eq 0) {
    throw 'Both CSV files must contain at least one data row.'
}

foreach ($column in @('First name', 'Last name', 'User principal name')) {
    if ($column -notin $users[0].PSObject.Properties.Name) {
        throw "The MS365 users CSV is missing the required column: $column"
    }
}

foreach ($column in @('Student ID', 'First Name', 'Last Name')) {
    if ($column -notin $masterlist[0].PSObject.Properties.Name) {
        throw "The BSIT masterlist CSV is missing the required column: $column"
    }
}

$firstLastIndex = @{}
$fullNameIndex = @{}

foreach ($row in $masterlist) {
    $studentId = $row.'Student ID'.Trim()

    if (-not $studentId) {
        continue
    }

    $entry = [pscustomobject]@{
        StudentNumber = $studentId
        FirstName = $row.'First Name'
        MiddleName = $row.'Middle Name'
        LastName = $row.'Last Name'
    }

    Add-ToNameIndex -Index $firstLastIndex -Key (Get-FirstLastKey $entry.FirstName $entry.LastName) -Entry $entry
    Add-ToNameIndex -Index $fullNameIndex -Key (Get-FullNameKey $entry.FirstName $entry.MiddleName $entry.LastName) -Entry $entry
}

$headers = @($users[0].PSObject.Properties.Name)
$results = [System.Collections.Generic.List[object]]::new()
$unmatched = [System.Collections.Generic.List[object]]::new()
$matched = 0
$ambiguous = 0
$duplicateMs365Assignments = 0

foreach ($user in $users) {
    $firstLastKey = Get-FirstLastKey $user.'First name' $user.'Last name'
    $fullNameKey = Normalize-NamePart $user.'Display name'
    $candidates = @()
    $matchMethod = ''

    if ($firstLastKey -and $firstLastIndex.ContainsKey($firstLastKey)) {
        $candidates = @($firstLastIndex[$firstLastKey])
        $matchMethod = 'First and last name'
    }

    if ($candidates.Count -ne 1 -and $fullNameKey -and $fullNameIndex.ContainsKey($fullNameKey)) {
        $candidates = @($fullNameIndex[$fullNameKey])
        $matchMethod = 'Full display name'
    }

    $studentNumber = ''
    $reason = ''

    if ($candidates.Count -eq 1) {
        $studentNumber = $candidates[0].StudentNumber
        $matched++
    } elseif ($candidates.Count -gt 1) {
        $ambiguous++
        $reason = 'Multiple masterlist students have the same matching name.'
    } else {
        $reason = 'No matching student name was found in the BSIT masterlist.'
    }

    $outputRow = [ordered]@{}
    foreach ($header in $headers) {
        $outputRow[$header] = $user.$header
        if ($header -eq 'User principal name') {
            $outputRow['Student Number'] = $studentNumber
        }
    }
    $results.Add([pscustomobject] $outputRow)

    if (-not $studentNumber) {
        $unmatched.Add([pscustomobject]@{
            'User principal name' = $user.'User principal name'
            'Display name' = $user.'Display name'
            'First name' = $user.'First name'
            'Last name' = $user.'Last name'
            Reason = $reason
        })
    }
}

$duplicateStudentNumbers = @(
    $results |
        Where-Object { -not [string]::IsNullOrWhiteSpace($_.'Student Number') } |
        Group-Object -Property 'Student Number' |
        Where-Object { $_.Count -gt 1 } |
        Select-Object -ExpandProperty Name
)

foreach ($result in $results) {
    $studentNumber = $result.'Student Number'

    if (-not $studentNumber -or $studentNumber -notin $duplicateStudentNumbers) {
        continue
    }

    $result.'Student Number' = ''
    $matched--
    $duplicateMs365Assignments++
    $unmatched.Add([pscustomobject]@{
        'User principal name' = $result.'User principal name'
        'Display name' = $result.'Display name'
        'First name' = $result.'First name'
        'Last name' = $result.'Last name'
        Reason = 'More than one MS365 account matched this student number by name.'
    })
}

New-Item -ItemType Directory -Path $OutputDirectory -Force | Out-Null
$outputCsv = Join-Path $OutputDirectory 'users_with_student_numbers.csv'
$unmatchedCsv = Join-Path $OutputDirectory 'users_without_student_number_match.csv'
$summaryJson = Join-Path $OutputDirectory 'student_number_match_summary.json'

$results | Export-Csv -LiteralPath $outputCsv -NoTypeInformation -Encoding utf8
$unmatched | Export-Csv -LiteralPath $unmatchedCsv -NoTypeInformation -Encoding utf8

[pscustomobject]@{
    UsersInMs365Export = $users.Count
    StudentsInBsitMasterlist = $masterlist.Count
    StudentNumbersMatched = $matched
    AmbiguousNameMatches = $ambiguous
    DuplicateMs365AccountAssignments = $duplicateMs365Assignments
    Unmatched = $unmatched.Count
    OutputCsv = $outputCsv
    UnmatchedReportCsv = $unmatchedCsv
} | ConvertTo-Json | Set-Content -LiteralPath $summaryJson -Encoding utf8

Get-Content -LiteralPath $summaryJson
