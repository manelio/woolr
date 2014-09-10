<?PHP
// The source code packaged with this file is Free Software, Copyright (C) 2005 by
// Ricardo Galli <gallir at uib dot es>.
// It's licensed under the AFFERO GENERAL PUBLIC LICENSE unless stated otherwise.
// You can get copies of the licenses here:
// 		http://www.affero.org/oagpl.html
// AFFERO GENERAL PUBLIC LICENSE is also included in the file called "COPYING".



class UserAuth {
	const CURRENT_VERSION = '7';
	const KEY_MAX_TTL = 2592000; // Expire key in 30 days
	const KEY_TTL = 86400; // Renew every 24 hours
	const HASH_ALGORITHM = 'sha256';

	/* https://crackstation.net/hashing-security.htm
	 * https://crackstation.net/hashing-security.htm#phpsourcecode
	*/
	static function hash($pass, $salt = false, $alg = false) {
		if (! $salt) $salt = base64_encode(mcrypt_create_iv(24, MCRYPT_DEV_URANDOM));
		if (! $alg) $alg = self::HASH_ALGORITHM;
		return $alg.':'.$salt.':'.hash($alg, $salt.$pass);
	}

	static function check_hash($hash, $pass) {
		$a = explode(':', $hash);
		if (!$a) return false;
		switch ($a[0]) {
			case 'sha256':
				$h = self::hash($pass, $a[1], $a[0]);
				break;
			default:
				$h = md5($pass);
		}
		return $hash == $h;
	}

	static function domain() {
		global $globals;

		if (isset($globals['cookies_domain']) && ! empty($globals['cookies_domain'])) {
			return $globals['cookies_domain'];
		} else {
			return $domain = null;
		}
	}

	function __construct() {
		global $db, $site_key, $globals;

		$this->user_id = 0;
		$this->user_login = '';
		$this->authenticated = false;
		$this->admin = false;

		if(!isset($globals['no_auth']) && isset($_COOKIE['ukey']) && isset($_COOKIE['u'])
					&& ($this->u = explode(":", $_COOKIE['u']))
					&& $this->u[0] > 0
					) {
			$userInfo=explode(":", base64_decode($_COOKIE['ukey']));
			if($this->u[0] == $userInfo[0]) {
				$this->version = $userInfo[2];
				$cookietime = intval($userInfo[3]);
				if (($globals['now'] - $cookietime) > self::KEY_MAX_TTL) $cookietime = 'expired'; // expiration is forced

				$user_id = intval($this->u[0]);
				$user=$db->get_row("SELECT SQL_CACHE user_id, user_login, substring(user_pass, 8, 10) as pass_frag, user_level, UNIX_TIMESTAMP(user_validated_date) as user_date, user_karma, user_email, user_avatar, user_comment_pref FROM users WHERE user_id = $user_id");

				if ($this->version == self::CURRENT_VERSION) {
					$key = md5($site_key.$user->user_login.$user->user_id.$user->pass_frag.$cookietime);
				} else  {
					$key = md5($user->user_email.$site_key.$user->user_login.$user->user_id.$cookietime);
				}

				if ( !$user || !$user->user_id > 0 || $key !== $userInfo[1] ||
					$user->user_level == 'disabled' || $user->user_level == 'autodisabled' ||
					empty($user->user_date)) {
						$this->Logout();
						// Make sure mysql @user_id is reset
						$db->query("set @user_id = 0");
						return;
				}

				foreach(get_object_vars($user) as $var => $value) $this->$var = $value;
				if ($this->user_level == 'admin' || $this->user_level == 'god') $this->admin = true;
				elseif ($this->user_level == 'special' || $this->user_level == 'blogger') $this->special = true;
				$this->authenticated = true;

				$remember = $userInfo[4] > 0;

				if ($this->version != self::CURRENT_VERSION) { // Update the key
					$this->SetIDCookie(2, $remember);
					$this->SetUserCookie();
				} elseif ($globals['now'] - $cookietime >  self::KEY_TTL) {
					$this->SetIDCookie(2, $remember);
				}
				// Set the sticky cookie for use un LoadBalancers that allows it (as Amazon ELB)
				setcookie ('sticky', '1', 0, $globals['base_url']);
			}
		}
		// Mysql variables to use en join queries
		$db->initial_query("set @user_id = $this->user_id, @ip_int = ".$globals['user_ip_int'].
			", @enabled_votes = date_sub(now(), interval ". intval($globals['time_enabled_votes']/3600). " hour)"
			// ", @site = " . SitesMgr::my_id() 
			);
	}


	function SetIDCookie($what, $remember = false) {
		global $site_key, $globals;
		switch ($what) {
			case 0:	// Expires cookie,logout
				$this->user_id = 0;
				setcookie ('ukey', '', $globals['now'] - 3600, $globals['base_url'], self::domain());
				$this->SetUserCookie();
				setcookie ('sticky', '', $globals['now'] - 3600,  $globals['base_url']);
				break;
			case 1: // User is logged, update the cookie
				$this->AddClone();
				$this->SetUserCookie();
			case 2: // Only update the key
				if($remember) $time = $globals['now'] + self::KEY_MAX_TTL;
				else $time = 0;
				$strCookie=base64_encode(
						$this->user_id.':'
						.md5($site_key.$this->user_login.$this->user_id.$this->pass_frag.$globals['now']).':'
						.self::CURRENT_VERSION.':' // Version number
						.$globals['now'].':'
						.$time);
				setcookie('ukey', $strCookie, $time, $globals['base_url'], self::domain(), false, true);
				break;
		}
	}

	function Authenticate($username, $pass = false, $remember = false/* Just this session */) {
		global $db, $globals;

		$dbusername=$db->escape($username);
		if (preg_match('/.+@.+\..+/', $username)) {
			// It's an email address, get
			$user=$db->get_row("SELECT user_id, user_login, user_pass, substring(user_pass, 8, 10) as pass_frag, user_level, UNIX_TIMESTAMP(user_validated_date) as user_date, user_karma, user_email FROM users WHERE user_email = '$dbusername'");
		} else {
			$user=$db->get_row("SELECT user_id, user_login, user_pass, substring(user_pass, 8, 10) as pass_frag, user_level, UNIX_TIMESTAMP(user_validated_date) as user_date, user_karma, user_email FROM users WHERE user_login = '$dbusername'");
		}
		if ($user->user_level == 'disabled' || $user->user_level == 'autodisabled' || ! $user->user_date) return false;
		if ($user->user_id > 0 && ($pass === false || self::check_hash($user->user_pass, $pass))) {

			if ($pass && strlen($pass) > 3 && strlen($user->user_pass) < 64) { // It's an old md5 pass, upgrade it
				$user->user_pass = self::hash($pass);
				$db->query("UPDATE users SET user_pass = '$user->user_pass' WHERE user_id = $user->user_id LIMIT 1");
			}

			foreach(get_object_vars($user) as $var => $value) $this->$var = $value;
			$this->authenticated = true;
			$this->SetIDCookie(1, $remember);
			return true;
		}
		return false;
	}

	function Logout($url='./') {
		$this->user_id = 0;
		$this->user_login = '';
		$this->admin = false;
		$this->authenticated = false;
		$this->SetIDCookie (0, false);

		//header("Pragma: no-cache");
		header ('HTTP/1.1 303 Load');
		header("Cache-Control: no-cache, must-revalidate");
		header("Location: $url");
		header("Expires: " . gmdate("r", $globals['now'] - 3600));
		header('ETag: "logingout' . $globals['now'] . '"');
		die;
	}

	function Date() {
		global $db;
		return (int) $this->user_date;
	}

	function SetUserCookie() {
		global $globals;
		$expiration = $globals['now'] + 86400 * 1000;
		setcookie('u',
					$this->user_id.
					':'.$this->u[1].
					':'.$globals['now'].
					':'.$this->signature($this->user_id.$this->u[1].$globals['now']),
					$expiration, $globals['base_url'], self::domain(), false, true);
	}

	function AddClone() {
		global $globals;

		$this->u = self::user_cookie_data(); // Get the previous user cookie which shouldn't be modified at this time
		if ($this->u && $globals['now'] - $this->u[2] < 86400 * 5) { // Only if it was stored recently
			$ids = explode("x", $this->u[1]);
			while(count($ids) > 4) {
				array_shift($ids);
			}
		} else {
			$ids = array();
		}
		$ids[] = $this->user_id;
		$this->u[1] = implode('x', $ids);
	}

	function GetClones() {
		$clones = array();
		foreach (explode('x', $this->u[1]) as $id) {
			$id = intval($id);
			if ($id > 0) {
				$clones[] = $id;
			}
		}
		return $clones;
	}

	function GetOAuthIds($service = false) {
		global $db;
		if (! $this->user_id) return false;
		if (! $service) {
			$sql = "select service, uid from auths where user_id = $this->user_id";
			$res = $db->get_results($sql);
		} else {
			$sql = "select uid from auths where user_id = $this->user_id and service = '$service'";
			$res = $db->get_var($sql);
		}
		return $res;
	}

	static function signature($str) {
		global $site_key;
		return substr(md5($str.$site_key), 0, 8);
	}


	static function user_cookie_data() {
		// Return an array with u only if the signature is valid
		if ($_COOKIE['u'] && ($u = explode(":", $_COOKIE['u']))
			&&  $u[3] == self::signature($u[0].$u[1].$u[2]) ) {
			return $u;
		}
		return false;
	}

	function get_clones($hours=24, $all = false) {
		// Return the count of cookies clones that voted before a given link, comment, note
		global $db;

		if (! $all) $extra = "and clon_ip like 'COOK:%'";
		else $extra = '';

		// This as from
		$a = $db->get_col("select clon_to as clon from clones where clon_from = $this->user_id and clon_date > date_sub(now(), interval $hours hour) $extra");
		// This as to
		$b = $db->get_col("select clon_from as clon from clones where clon_to = $this->user_id and clon_date > date_sub(now(), interval $hours hour) $extra");
		return array_unique(array_merge($a, $b));
	}

	static function check_clon_from_cookies() {
		global $current_user, $globals;
		// Check the cookies and store clones
		$clones = array_reverse($current_user->GetClones()); // First item is the current login, second is the previous
		if (count($clones) > 1 && $clones[0] != $clones[1]) { // Ignore if last two logins are the same user
			$visited = array();
			foreach ($clones as $id) {
				if ($current_user->user_id != $id && !in_array($id, $visited)) {
					$visited[] = $id;
					if ($globals['form_user_ip']) $ip = $globals['form_user_ip']; // Used in SSL forms
					else $ip = $globals['user_ip'];
					self::insert_clon($current_user->user_id, $id, 'COOK:'.$ip);
				}
			}
		}
	}

	static function insert_clon($last, $previous, $ip='') {
		global $globals, $db;
		if ($last == $previous) return false;
		$db->query("REPLACE INTO clones (clon_from, clon_to, clon_ip) VALUES ($last, $previous, '$ip')");
		$db->query("INSERT IGNORE INTO clones (clon_to, clon_from, clon_ip) VALUES ($last, $previous, '$ip')");
	}

	static function check_clon_votes($from, $id, $days=7, $type='links') {
		// Return the count of cookies clones that voted before a given link, comment, note
		global $db;

		$c = (int) $db->get_var("select count(*) from votes, clones where vote_type='$type' and vote_link_id = $id and clon_from = $from and clon_to = vote_user_id and clon_date > date_sub(now(), interval $days day) and clon_ip like 'COOK:%'");
		if ($c > 0) {
			syslog(LOG_INFO, "Meneame: clon vote $type, id: $id, user: $from ");
		}
		return $c;
	}



}

$current_user = new UserAuth();
?>
