Spaces-Checker
Checks for new events on Spaces.ru.

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