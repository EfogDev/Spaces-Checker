#!/usr/bin/php
<?php
	set_time_limit(0);

	class spaces {
		protected $sid;
		protected $count;
		protected $settings;
		protected $sounds;
		protected $groups;
		protected $groups_ids;
		protected $group_sound;

		public function __construct($sid, $settings, $sounds, $groups = array(), $group_sound = 1) {
			$this->sid = $sid;
			$this->count = array(
				"mail" => 0,
				"journal" => 0,
				"feed" => 0
			);
			$this->settings = $settings;
			$this->sounds = $sounds;
			$this->groups = $groups;
			$this->group_sound = $group_sound;
			foreach ($groups as $cid) {
				$this->groups_ids[$cid] = 0;
			}
		}

		public function init_groups() {
			foreach ($this->groups as $cid) {
				$ch = curl_init("http://spaces.ru/forums/?com_cat_id=$cid&last=6&sid=" . $this->sid);
				curl_setopt_array($ch, array(
					CURLOPT_RETURNTRANSFER => 1,
					CURLOPT_USERAGENT => "Mozilla 5.49 (Vodila 228 Edition)",
					CURLOPT_REFERER => "http://spaces.ru",
					CURLOPT_TIMEOUT => 3,
					CURLOPT_COOKIE => "sid=" . $this->sid
				));
				$e = curl_exec($ch);
				preg_match("@<a href=\"http:\/\/spaces\.ru\/forums\/\?r=([0-9]+?)\&@sui", $e, $found);
				$id = end($found);
				$this->groups_ids[$cid] = $id;
				usleep(500);
			}

			var_dump($this->groups_ids);
		}

		public function check_groups() {
			foreach ($this->groups as $cid) {
				$ch = curl_init("http://spaces.ru/forums/?com_cat_id=$cid&last=6&sid=" . $this->sid);
				curl_setopt_array($ch, array(
					CURLOPT_RETURNTRANSFER => 1,
					CURLOPT_USERAGENT => "Mozilla 5.49 (Vodila 228 Edition)",
					CURLOPT_REFERER => "http://spaces.ru",
					CURLOPT_TIMEOUT => 3,
					CURLOPT_COOKIE => "sid=" . $this->sid
				));
				$e = curl_exec($ch);
				$prid = $this->groups_ids[$cid];
				$pos = mb_strpos($e, '<a href="http://spaces.ru/forums/?r=' . $prid . '&', 0, "UTF-8");
				if ($pos < 0) echo("Что-то пошло не так.\r\n");
				$string = mb_substr($e, 0, $pos, "UTF-8");
				preg_match_all("@<a href=\"http:\/\/spaces\.ru\/forums\/\?r=([0-9]+?)\&(.+?)<b>(.+?)<\/b>(.+?)<small class=\"grey\">(.+?)\/@sui", $string, $new);
				preg_match("@r=comm\/comm_show\&amp;sid=([0-9]+?)\">(.+?)<\/a> \/ <a href=\"http:\/\/spaces\.ru\/forums\/\?@sui", $string, $grp_name);
				$grp_name = end($grp_name);
				if (count($new[1]) > 0) {
					$i = 0;
					foreach ($new[1] as $value) {
						$text = "В группе $grp_name пользователем " . trim($new[5][$i]) . " была создана новая тема: \"{$new[3][$i]}\".";
						$this->alert($text, ($this->group_sound ? 1 : 0));
						$i++;
					}
					$this->groups_ids[$cid] = $new[1][0];
				}
				usleep(500);
			}
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

	$sid = 0;
	$params = $argv;
	$pcount = count($params);
	$groups = array();
	$interval = 5;
	$group_sound = 1;
	for ($i = 1; $i < $pcount; $i++) {
		switch ($params[$i]) {
			case "-c":
			case "--check":
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
			case "--sound":
				$all = explode(",", $params[$i + 1]);
				if (in_array("mail", $all)) $default_sounds["mail"] = true;
					else $default_sounds["mail"] = false;
				if (in_array("journal", $all)) $default_sounds["journal"] = true;
					else $default_sounds["journal"] = false;
				if (in_array("feed", $all)) $default_sounds["feed"] = true;
					else $default_sounds["feed"] = false;
				$i++;
				break;
			case "-a":
			case "--sid":
				if (!isset($params[$i + 1])) exit("Не указан SID.\r\n");
				$sid = $params[$i + 1];
				$i++;
				break;
			case "-f":
			case "--file":
				if (!isset($params[$i + 1])) exit("Не указано имя файла с SID.\r\n");
				$fname = $params[$i + 1];
				if (!file_exists(__DIR__ . "/" . $fname) || empty($fname)) exit("Неверное имя файла.\r\n");
				$sid = file_get_contents(__DIR__ . "/" . $fname);
				$i++;
				break;
			case "-g":
			case "--groups":
				$groups = explode(",", $params[$i + 1]);
				if (0 == count($groups)) exit("Не указаны группы для мониторинга.\r\n");
				foreach ($groups as $v) {
					if (!is_numeric($v)) exit("Неверный ID группы: $v.");
				}
				$i++;
				break;
			case "-i":
			case "--interval":
				$int = intval($params[$i + 1]);
				if ($int <= 0 || $int >= 3600) exit("Интервал - целое число от 1 до 3600.\r\n");
				$interval = $int;
				$i++;
				break;
			case "-ngs":
			case "--no-group-sound":
				$group_sound = 0;
				break;
			default:
				exit("Неизвестный параметр $params[$i].\r\n");
		}
	}

	if (empty($argv[1]) && !$sid) exit("Укажите SID первым параметром.\r\n");
	if (!$sid) $sid = $argv[1];
	$spaces = new spaces($sid, $default_settings, $default_sounds, $groups, $group_sound);

	$spaces->check();
	sleep(1);
	$spaces->init_groups();
	sleep($interval);
	while (true) {
		$spaces->check();
		usleep(500);
		if (count($groups) > 0) $spaces->check_groups();
		sleep($interval);
	}
?>
