<?php
namespace App\Helpers;
use Illuminate\Support\Facades\Session;

class ModAuth {
    public $userData;
    public $userName;
    public $oAuthVerifier;
    public $oAuthChallenge;
    public $oAuthChallengeMethod;
    public $userRoles;

    public function prepareForLogin() {
        $url = str_replace('www.','',route('getMicrosofftResponse'));
        $this->oAuthChallenge();
        return $oAuthURL = 'https://login.microsoftonline.com/' . env('_OAUTH_TENANTID') . '/oauth2/v2.0/' . 'authorize?response_type=code&client_id=' . env('_OAUTH_CLIENTID') . '&redirect_uri=' . urlencode($url) . '&scope=' . env('_OAUTH_SCOPE') . '&code_challenge=' . $this->oAuthChallenge . '&code_challenge_method=' . $this->oAuthChallengeMethod;
    }

    public function authorizeCode($code) {
        $oAuthVerifier= Session::get('authverifier');
        $url = str_replace('www.','',route('getMicrosofftResponse'));
        $oauthRequest = $this->generateRequest('grant_type=authorization_code&client_id=' . env('_OAUTH_CLIENTID') . '&redirect_uri=' . urlencode($url) . '&code=' . $code . '&code_verifier=' . $oAuthVerifier);
        $response = $this->postRequest('token', $oauthRequest);
        H::ExcelLog(print_r($response, true),'sso_login.log');
        if (!$response) {
            H::ExcelLog("response null",'sso_login.log');
            return "";
        }
        $reply = json_decode($response);
        H::ExcelLog(print_r($reply, true),'sso_login.log');
        if(!isset($reply->error)) {
            $idToken = base64_decode(explode('.', $reply->id_token)[1]);
            return $username = json_decode($idToken)->preferred_username;
        } else {
            H::ExcelLog("Check Error",'sso_login.log');
            return "";
        }
    }

    public function checkUserRole($role) {
	    // Check that the requested role has been assigned to the user
        if (in_array($role, $this->userRoles)) {
            return 1;
        }
        return 0;
    }
    
    public function oAuthChallenge() {
        // Function to generate code verifier and code challenge for oAuth login. See RFC7636 for details.
        $verifier = $this->oAuthVerifier;
        if (!$this->oAuthVerifier) {
            $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-._~';
            $charLen = strlen($chars) - 1;
            $verifier = '';
            for ($i = 0; $i < 128; $i++) {
                $verifier .= $chars[mt_rand(0, $charLen)];
            }
            $this->oAuthVerifier = $verifier;
            
            Session::put("authverifier",$this->oAuthVerifier);
        }
        $this->oAuthChallenge = str_replace('=', '', strtr(base64_encode(pack('H*', hash('sha256', $verifier))), '+/', '-_'));
        $this->oAuthChallengeMethod = 'S256';
        Session::put("authchallenge",$this->oAuthChallenge);
        Session::put("authchallengemethod",$this->oAuthChallengeMethod);
    }
	
    public function generateRequest($data) {
           return $data . '&client_secret=' . urlencode(env('_OAUTH_SECRET'));
	}

	public function postRequest($endpoint, $data) {
		$ch = curl_init('https://login.microsoftonline.com/' . env('_OAUTH_TENANTID') . '/oauth2/v2.0/' . $endpoint);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$response = curl_exec($ch);
		if ($cError = curl_error($ch)) {
			echo $cError;
			exit;
		}
		curl_close($ch);
		return $response;

	}
	public function base64UrlEncode($toEncode) {
        return str_replace('=', '', strtr(base64_encode($toEncode), '+/', '-_'));
    }

}
?>
