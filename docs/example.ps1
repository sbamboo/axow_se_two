class axow {
    [string]$BaseUrl
    [string]$Token = $null
    [string]$RefreshToken = $null # Add property for refresh token

    axow([string]$baseUrl) {
        $this.BaseUrl = $baseUrl.TrimEnd('/')
    }

    [void] auth([string]$token_type, [string]$username, [string]$password) {
        $url = "$($this.BaseUrl)/auth/index.php?token_type=$token_type&username=$username&password=$password"
        # Using curl with -s (silent), -X GET, and capturing stderr to allow JSON parsing
        $response = curl -s -X GET $url 2>&1
        Write-Host $response

        try {
            $json = $response | ConvertFrom-Json
            if ($json.status -eq "success" -and $json.token) {
                $this.Token = $json.token
            }
            # Collect refresh_token if token_type is 'pair' and it exists
            if ($token_type -eq 'pair' -and $json.refresh_token) {
                $this.RefreshToken = $json.refresh_token
            }
        } catch {
            # Ignore parse errors or handle them if necessary
        }
    }

    [void] validate() {
        if (-not $this.Token) {
            Write-Host "No token available. Please authenticate first."
            return
        }
        $url = "$($this.BaseUrl)/auth/validate/index.php"
        # Using curl with -s (silent), -X GET, and -H for the Authorization header
        $response = curl -s -X GET -H "Authorization: Bearer $($this.Token)" $url 2>&1
        Write-Host $response
    }

    [void] unauth() {
        if (-not $this.Token) {
            Write-Host "No token available. Please authenticate first."
            return
        }
        $url = "$($this.BaseUrl)/unauth/index.php"
        # Using curl with -s (silent), -X GET, and -H for the Authorization header
        $response = curl -s -X GET -H "Authorization: Bearer $($this.Token)" $url 2>&1
        Write-Host $response
    }

    [void] change_username([string]$new_username) {
        if (-not $this.Token) {
            Write-Host "No token available. Please authenticate first."
            return
        }
        $url = "$($this.BaseUrl)/users/change_username/index.php"
        $body = @{ new_username = $new_username } | ConvertTo-Json -Compress
        # Using curl with -s (silent), -X POST (assuming POST for change_username),
        # -H for Authorization and Content-Type, and -d for the body
        $response = curl -s -X POST -H "Authorization: Bearer $($this.Token)" -H "Content-Type: application/json" -d $body $url 2>&1
        Write-Host $response
    }

    [void] change_password([string]$old_password, [string]$new_password) {
        if (-not $this.Token) {
            Write-Host "No token available. Please authenticate first."
            return
        }
        $url = "$($this.BaseUrl)/users/change_password/index.php"
        $body = @{ old_password = $old_password; new_password = $new_password } | ConvertTo-Json -Compress
        # Using curl with -s (silent), -X POST (assuming POST for change_password),
        # -H for Authorization and Content-Type, and -d for the body
        $response = curl -s -X POST -H "Authorization: Bearer $($this.Token)" -H "Content-Type: application/json" -d $body $url 2>&1
        Write-Host $response
    }

    [void] refresh() {
        $url = "$($this.BaseUrl)/auth/refresh/index.php"
        $body = @{ refresh_token = $this.RefreshToken } | ConvertTo-Json -Compress
        # Using curl with -s (silent), -X POST, -H for Content-Type, and -d for the body
        $response = curl -s -X POST -H "Content-Type: application/json" -d $body $url 2>&1
        Write-Host $response

        try {
            $json = $response | ConvertFrom-Json
            if ($json.status -eq "success" -and $json.token) {
                $this.Token = $json.token
                # Optionally, update the refresh token if the response includes a new one
                if ($json.refresh_token) {
                    $this.RefreshToken = $json.refresh_token
                }
            }
        } catch {
            # Ignore parse errors or handle them if necessary
        }
    }


    [void] preview([string]$url) {
        $this.preview($url, $false, $null, $false, $false)
    }

    [void] preview([string]$url, [bool]$client_user_agent) {
         $this.preview($url, $client_user_agent, $null, $false, $false)
    }

    [void] preview([string]$url, [bool]$client_user_agent, [bool]$unescape) {
         $this.preview($url, $client_user_agent, $null, $unescape, $false)
    }

    [void] preview([string]$url, [bool]$client_user_agent, [bool]$unescape, [bool]$unescaped_unicode) {
         $this.preview($url, $client_user_agent, $null, $unescape, $unescaped_unicode)
    }

    [void] preview([string]$url, [bool]$client_user_agent=$false, [Nullable[int]]$ttl=$null, [bool]$unescape=$false, [bool]$unescaped_unicode=$false) {
        if (-not $this.Token) {
            Write-Host "No token available. Please authenticate first."
            return
        }

        $queryString = "url=$([uri]::EscapeDataString($url))"

        if ($client_user_agent) {
            $queryString += "&client_user_agent"
        }

        if ($ttl -ne $null) {
            $queryString += "&cache-ttl=$ttl"
        }

        if ($unescape) {
            $queryString += "&unescape"
        }

        if ($unescaped_unicode) {
            $queryString += "&unescaped_unicode"
        }

        # Combine the base URL and the query string
        $urlWithParams = "$($this.BaseUrl)/url_preview/index.php?$queryString"

        # Build the arguments array for the curl command
        $curlArgs = @(
            "-s"
            "-X"
            "GET"
            "-H"
            "Authorization: Bearer $($this.Token)"
            "-H"
            "Content-Type: application/json"
            $urlWithParams
        )

        # Execute the curl command
        $response = curl @curlArgs 2>&1
        Write-Host $response
    }
}
$client = [axow]::new('http://localhost/proj/axow_se_two/backend/site')
$client.auth('single','admin','admin')
$client.preview('https%3A%2F%2Fwww.youtube.com%2Fwatch%3Fv%3DjPJCYrxqyT8',$true,-1)