Spaces-Checker - checks for new events on Spaces.ru.

Installing:

	1. Download .zip archive.
	2. In file check.php edit paths to picture and sound in lines 19 and 20.
	3. (not necessarily) Create symlink for check.php:
			> ln -s /path/to/check.php /bin/spaces
		(you should have root access)
	4. Let check.php to be runned
	 		> chmod +x /path/to/check.php
	5. Run script:
		If you have created symlink
		 	> spaces 123
		If you not
			> cd /path/to/checkphp
			> ./check.php 123
		Where 123 is your SID on Spaces.ru.
	6. PROFIT!

Usage:

	If you simply want to check new events, you can just run script with SID parameter:
		> spaces YOUR_SID (suppose that we created symlink "spaces" to /bin)
	If you want to check only specified events, use -c parameter. Example:
		> spaces YOUR_SID -c mail,journal (feed will not checking on)
		Use "mail", "journal" and "feed" keywords.
	If you want to play sound only when specified event is happened, use -s parameter:
		> spaces YOUR_SID -s mail (sound will not player when journal or feed comes)
	Default settings:
		-c mail,journal,feed
		-s mail,journal,feed

Enjoy!