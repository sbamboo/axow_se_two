class axow {
    [string]$BaseUrl
    [string]$Token = $null

    axow([string]$baseUrl) {
        $this.BaseUrl = $baseUrl.TrimEnd('/')
    }

    [void] auth([string]$token_type, [string]$username, [string]$password) {
        $url = "$($this.BaseUrl)/auth/index.php?token_type=$token_type&username=$username&password=$password"
        $response = curl -s -X GET $url 2>&1
        Write-Host $response

        try {
            $json = $response | ConvertFrom-Json
            if ($json.status -eq "success" -and $json.token) {
                $this.Token = $json.token
            }
        } catch {
            # Ignore parse errors
        }
    }

    [void] validate() {
        if (-not $this.Token) {
            Write-Host "No token available. Please authenticate first."
            return
        }
        $url = "$($this.BaseUrl)/auth/validate/index.php"
        $response = curl -s -X GET -H @{"Authorization"="Bearer $($this.Token)"} $url 2>&1
        Write-Host $response
    }

    [void] unauth() {
        if (-not $this.Token) {
            Write-Host "No token available. Please authenticate first."
            return
        }
        $url = "$($this.BaseUrl)/unauth/index.php"
        $response = curl -s -X GET -H @{"Authorization"="Bearer $($this.Token)"} $url 2>&1
        Write-Host $response
    }

    [void] change_username([string]$new_username) {
        if (-not $this.Token) {
            Write-Host "No token available. Please authenticate first."
            return
        }
        $url = "$($this.BaseUrl)/users/change_username/index.php"
        $body = @{ new_username = $new_username } | ConvertTo-Json -Compress
        $response = curl -s -X GET -H @{"Authorization"="Bearer $($this.Token)"} -Body $body $url 2>&1
        Write-Host $response
    }

    [void] change_password([string]$old_password, [string]$new_password) {
        if (-not $this.Token) {
            Write-Host "No token available. Please authenticate first."
            return
        }
        $url = "$($this.BaseUrl)/users/change_password/index.php"
        $body = @{ old_password = $old_password; new_password = $new_password } | ConvertTo-Json -Compress
        $response = curl -s -X GET -H @{"Authorization"="Bearer $($this.Token)"} -Body $body $url 2>&1
        Write-Host $response
    }
}

# $client = [axow]::new('http://localhost/proj/axow_se_two/backend/site')
# $client.auth('single','admin','admin')
# $client.validate()