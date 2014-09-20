#!/usr/bin/php
<?php
	set_time_limit(0);

	class spaces {
		public $sid;
		public $count;

		public function __construct($sid) {
			$this->sid = $sid;
			$this->count = array(
				"mail" => 0,
				"journal" => 0,
				"feed" => 0
			);
		}

		public function alert($text) { 
            exec("notify-send -i /home/efog/Dev/info.png Spaces '$text'");
			exec("mplayer /home/efog/Dev/info.mp3 1>/dev/null 2>/dev/null");
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

			file_put_contents("page.html", $e);

			preg_match("@mailn.png\" alt=\"Почта\" \/>(.+?)<\/a@sui", $e, $mail_new);
			preg_match("@journaln.png\" alt=\"Жур\" \/>(.+?)<\/a@sui", $e, $journal_new);
			preg_match("@lenta2n.png\" alt=\"Лента\" \/>(.+?)<\/a@sui", $e, $feed_new);

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

			$text = "У вас есть новые события на Spaces в ";
			$textto = "";
			if ($new["mail"] > 0) $textto .= "почте";
			if ($new["journal"] > 0) {
				if ($new["mail"] > 0) $textto .= ", журнале";
				else $textto .= "журнале";
			}
			if ($new["feed"] > 0) {
				if ($new["mail"] > 0 || $new["journal"] > 0) $textto .= ", ленте";
				else $textto .= "ленте";
			}
			$text .= $textto . ".";
			if (!empty($textto)) $this->alert($text);
		}
	}

	if (empty($argv[1])) exit("Укажите SID первым параметром.\r\n");
	$spaces = new spaces($argv[1]);

	while (true) {
		$spaces->check();
		sleep(5);
	}
?>
