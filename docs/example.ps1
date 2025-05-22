class axow {
    [string]$BaseUrl
    [string]$Token = $null
    [string]$RefreshToken = $null # Add property for refresh token

    axow([string]$baseUrl) {
        $this.BaseUrl = $baseUrl.TrimEnd("/")
    }
    

    [void] auth([string]$token_type, [string]$username, [string]$password) {
        $url = "$($this.BaseUrl)/auth/index.php?token_type=$token_type&username=$username&password=$password"
        # Using curl with -s (silent), -X GET, and capturing stderr to allow JSON parsing
        $origOutEnc = [Console]::OutputEncoding
        $origPSNativeEnc = $global:PSNativeCommandEncoding
        $global:PSNativeCommandEncoding = [System.Text.Encoding]::UTF8
        $response = curl -s -X GET $url 2>&1
        Write-Host $response
        [Console]::OutputEncoding = $origOutEnc
        $global:PSNativeCommandEncoding = $origPSNativeEnc

        try {
            $json = $response | ConvertFrom-Json
            if ($json.status -eq "success" -and $json.token) {
                $this.Token = $json.token
            }
            # Collect refresh_token if token_type is "pair" and it exists
            if ($token_type -eq "pair" -and $json.refresh_token) {
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
        $origOutEnc = [Console]::OutputEncoding
        $origPSNativeEnc = $global:PSNativeCommandEncoding
        [Console]::OutputEncoding = [System.Text.Encoding]::UTF8
        $global:PSNativeCommandEncoding = [System.Text.Encoding]::UTF8
        $response = curl -s -X GET -H "Authorization: Bearer $($this.Token)" $url 2>&1
        Write-Host $response
        [Console]::OutputEncoding = $origOutEnc
        $global:PSNativeCommandEncoding = $origPSNativeEnc
    }

    [void] unauth() {
        if (-not $this.Token) {
            Write-Host "No token available. Please authenticate first."
            return
        }
        $url = "$($this.BaseUrl)/unauth/index.php"
        # Using curl with -s (silent), -X GET, and -H for the Authorization header
        $origOutEnc = [Console]::OutputEncoding
        $origPSNativeEnc = $global:PSNativeCommandEncoding
        [Console]::OutputEncoding = [System.Text.Encoding]::UTF8
        $global:PSNativeCommandEncoding = [System.Text.Encoding]::UTF8
        $response = curl -s -X GET -H "Authorization: Bearer $($this.Token)" $url 2>&1
        Write-Host $response
        [Console]::OutputEncoding = $origOutEnc
        $global:PSNativeCommandEncoding = $origPSNativeEnc
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
        $origOutEnc = [Console]::OutputEncoding
        $origPSNativeEnc = $global:PSNativeCommandEncoding
        [Console]::OutputEncoding = [System.Text.Encoding]::UTF8
        $global:PSNativeCommandEncoding = [System.Text.Encoding]::UTF8
        $response = curl -s -X POST -H "Authorization: Bearer $($this.Token)" -H "Content-Type: application/json" -d $body $url 2>&1
        Write-Host $response
        [Console]::OutputEncoding = $origOutEnc
        $global:PSNativeCommandEncoding = $origPSNativeEnc
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
        $origOutEnc = [Console]::OutputEncoding
        $origPSNativeEnc = $global:PSNativeCommandEncoding
        [Console]::OutputEncoding = [System.Text.Encoding]::UTF8
        $global:PSNativeCommandEncoding = [System.Text.Encoding]::UTF8
        $response = curl -s -X POST -H "Authorization: Bearer $($this.Token)" -H "Content-Type: application/json" -d $body $url 2>&1
        Write-Host $response
        [Console]::OutputEncoding = $origOutEnc
        $global:PSNativeCommandEncoding = $origPSNativeEnc
    }

    [void] refresh() {
        $url = "$($this.BaseUrl)/auth/refresh/index.php"
        $body = @{ refresh_token = $this.RefreshToken } | ConvertTo-Json -Compress
        # Using curl with -s (silent), -X POST, -H for Content-Type, and -d for the body
        $origOutEnc = [Console]::OutputEncoding
        $origPSNativeEnc = $global:PSNativeCommandEncoding
        [Console]::OutputEncoding = [System.Text.Encoding]::UTF8
        $global:PSNativeCommandEncoding = [System.Text.Encoding]::UTF8
        $response = curl -s -X POST -H "Content-Type: application/json" -d $body $url 2>&1
        Write-Host $response
        [Console]::OutputEncoding = $origOutEnc
        $global:PSNativeCommandEncoding = $origPSNativeEnc

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
        $this.preview($url, $false, $null, $false, $null)
    }

    [void] preview([string]$url, [bool]$client_user_agent) {
         $this.preview($url, $client_user_agent, $null, $false, $null)
    }

    [void] preview([string]$url, [bool]$client_user_agent, [Nullable[int]]$ttl=$null) {
         $this.preview($url, $client_user_agent, $ttl, $false, $null)
    }

    [void] preview([string]$url, [bool]$client_user_agent, [Nullable[int]]$ttl=$null, [bool]$escape_unicode=$false) {
         $this.preview($url, $client_user_agent, $ttl, $escape_unicode, $null)
    }

    [void] preview([string]$url, [bool]$client_user_agent=$false, [Nullable[int]]$ttl=$null, [bool]$escape_unicode=$false, [string]$oEmbed_url=$null) {
        if (-not $this.Token) {
            Write-Host "No token available. Please authenticate first."
            return
        }

        $queryString = "urls=$([uri]::EscapeDataString($url))"

        if ($client_user_agent) {
            $queryString += "&client_user_agent"
        }

        if ($ttl -ne $null) {
            $queryString += "&cache-ttl=$ttl"
        }

        if ($escape_unicode -eq $true) {
            $queryString += "&escape_unicode"
        }

        if ($oEmbed_url -ne $null) {
            $queryString += "&oembed_url=$([uri]::EscapeDataString($oEmbed_url))"
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
        $origOutEnc = [Console]::OutputEncoding
        $origPSNativeEnc = $global:PSNativeCommandEncoding
        [Console]::OutputEncoding = [System.Text.Encoding]::UTF8
        $global:PSNativeCommandEncoding = [System.Text.Encoding]::UTF8
        $response = curl @curlArgs 2>&1
        Write-Host $response
        [Console]::OutputEncoding = $origOutEnc
        $global:PSNativeCommandEncoding = $origPSNativeEnc
    }
}

#$client = [axow]::new("http://localhost/proj/axow_se_two/backend/site")
#$client.auth("single","username","password") # "single", "single_use" or "pair"
#$client.validate()
#$client.change_username("new_username")
#$client.change_password("password","new_password")
#$client.preview("https%3A%2F%2Fwww.youtube.com%2Fwatch%3Fv%3DjPJCYrxqyT8",$true,-1)
#$client.unauth()