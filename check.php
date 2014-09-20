#!/usr/bin/php
<?php
	set_time_limit(0);

	class spaces {
		protected $sid;
		protected $count;
		protected $settings;
		protected $sounds;

		public function __construct($sid, $settings, $sounds) {
			$this->sid = $sid;
			$this->count = array(
				"mail" => 0,
				"journal" => 0,
				"feed" => 0
			);
			$this->settings = $settings;
			$this->sounds = $sounds;
		}

		public function alert($text, $sound) {
			exec("notify-send -i " . __DIR__ . "/info.png Spaces '$text'");
			if ($sound) exec("mplayer " . __DIR__ . "/info.mp3 1>/dev/null 2>/dev/null");
		}

		public function check() {
			$ch = curl_init("http://spaces.ru/mysite/?sid=" . $this->sid);
			curl_setopt_array($ch, array(
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_USERAGENT => "Mozilla 5.49 (Vodila 228 Edition)",
				CURLOPT_REFERER => "http://spaces.ru",
				CURLOPT_TIMEOUT => 3,
				CURLOPT_COOKIE => "sid=" . $this->sid
			));
			$e = curl_exec($ch);
			if (preg_match("@<p>The document has moved@si", $e)) exit("Не удалось войти на аккаунт. Проверьте SID.\r\n");

			$mail_new = array();
			$journal_new = array();
			$feed_new = array();

			if ($this->settings["mail"]) preg_match("@mailn.png\" alt=\"Почта\" \/>(.+?)<\/a@sui", $e, $mail_new);
			if ($this->settings["journal"]) preg_match("@journaln.png\" alt=\"Жур\" \/>(.+?)<\/a@sui", $e, $journal_new);
			if ($this->settings["feed"]) preg_match("@lenta2n.png\" alt=\"Лента\" \/>(.+?)<\/a@sui", $e, $feed_new);

			$mail_new = trim(end($mail_new));
			$journal_new = trim(end($journal_new));
			$feed_new = trim(end($feed_new));
			$new = array(
				"mail" => 0,
				"journal" => 0,
				"feed" => 0
			);

			if (preg_match("@([0-9]+)@si", $mail_new)) {
				preg_match("@([0-9]+)@si", $mail_new, $a);
				$count = end($a);
				if ($this->count["mail"] != $count) {
					$new["mail"] = $count;
					$this->count["mail"] = $count;
				}
			} else $this->count["mail"] = 0;

			if (preg_match("@([0-9]+)@si", $journal_new)) {
				preg_match("@([0-9]+)@si", $journal_new, $a);
				$count = end($a);
				if ($this->count["journal"] != $count) {
					$new["journal"] = $count;
					$this->count["journal"] = $count;
				}
			} else $this->count["journal"] = 0;

			if (preg_match("@([0-9]+)@si", $feed_new)) {
				preg_match("@([0-9]+)@si", $feed_new, $a);
				$count = end($a);
				if ($this->count["feed"] != $count) {
					$new["feed"] = $count;
					$this->count["feed"] = $count;
				}
			} else $this->count["feed"] = 0;

			$sound = 0;
			$text = "У вас есть новые события на Spaces в ";
			$textto = "";
			if ($new["mail"] > 0) {
				$textto .= "почте";
				if ($this->sounds["mail"]) $sound = 1;
			}
			if ($new["journal"] > 0) {
				if ($new["mail"] > 0) $textto .= ", журнале";
				else $textto .= "журнале";
				if ($this->sounds["journal"]) $sound = 1;
			}
			if ($new["feed"] > 0) {
				if ($new["mail"] > 0 || $new["journal"] > 0) $textto .= ", ленте";
				else $textto .= "ленте";
				if ($this->sounds["feed"]) $sound = 1;
			}
			$text .= $textto . ".";
			if (!empty($textto)) $this->alert($text, ($sound ? 1 : 0));
		}
	}

	$default_settings = array(
		"mail" => true,
		"journal" => true,
		"feed" => true
	);
	$default_sounds = array(
		"mail" => true,
		"journal" => true,
		"feed" => true
	);

	if (empty($argv[1])) exit("Укажите SID первым параметром.\r\n");
	$params = $argv;
	$pcount = count($params);
	for ($i = 2; $i < $pcount; $i++) {
		switch ($params[$i]) {
			case "-c":
				$all = explode(",", $params[$i + 1]);
				if (in_array("mail", $all)) $default_settings["mail"] = true;
					else $default_settings["mail"] = false;
				if (in_array("journal", $all)) $default_settings["journal"] = true;
					else $default_settings["journal"] = false;
				if (in_array("feed", $all)) $default_settings["feed"] = true;
					else $default_settings["feed"] = false;
				$i++;
				break;
			case "-s":
				$all = explode(",", $params[$i + 1]);
				if (in_array("mail", $all)) $default_sounds["mail"] = true;
					else $default_sounds["mail"] = false;
				if (in_array("journal", $all)) $default_sounds["journal"] = true;
					else $default_sounds["journal"] = false;
				if (in_array("feed", $all)) $default_sounds["feed"] = true;
					else $default_sounds["feed"] = false;
				$i++;
				break;
			default:
				exit("Неизвестный параметр $params[$i].\r\n");
		}
	}

	$spaces = new spaces($argv[1], $default_settings, $default_sounds);

	while (true) {
		$spaces->check();
		sleep(5);
	}
?>
