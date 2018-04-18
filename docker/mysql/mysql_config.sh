#!/usr/bin/expect

set pwd [lindex $argv 0];

spawn mysql_config_editor set --login-path=local --host=localhost --user=root --password
expect -nocase "Enter password:" {send "${pwd}\r"; interact}