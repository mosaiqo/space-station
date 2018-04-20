#!/usr/bin/expect

set password [lindex $argv 0];
puts "$password";
spawn mysql_config_editor set --login-path=local --host=localhost --user=root --password

expect {
    "assword" {
        send "$password\r"
    }
}

interact