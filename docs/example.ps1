class axow {
    [string]$BaseUrl
    [string]$Token = $null

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
}

# $client = [axow]::new('http://localhost/proj/axow_se_two/backend/site')
# $client.auth('single','admin','admin')
# $client.validate()