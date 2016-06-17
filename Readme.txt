Remote->Client:
Telnet
	Do Echo
		Command: Do (253)
		Subcommand: Echo
	Do Negotiate About Window Size
		Command: Do (253)
		Subcommand: Negotiate About Window Size
  Will Echo
		Command: Will (251)
		Subcommand: Echo
  Will Suppress Go Ahead
		Command: Will (251)
		Subcommand: Suppress Go Ahead
0000   ff fd 01 ff fd 1f ff fb 01 ff fb 03              ............

Client->Remote:
0000   ff fb 1f ff fb 20 ff fb 18 ff fb 27 ff fd 01 ff  ..... .....'....
0010   fb 03 ff fd 03                                   .....

0000   ff fc 01                                         ...

0000   ff fa 1f 00 50 00 18 ff f0                      ....P....

:::::CISCO:::::
WinShark: 
Client->Cisco:
0000   ff fb 1f ff fb 20 ff fb 18 ff fb 27 ff fd 01 ff  ..... .....'....
0010   fb 03 ff fd 03                                   .....
Cisco->Client:
0000   ff fb 01 ff fb 03 ff fd 18 ff fd 1f              ............
Client-Cisco:
0000   ff fa 1f 00 50 00 18 ff f0                       ....P....
Cisco->Client:
0000   0d 0a 0d 0a 55 73 65 72 20 41 63 63 65 73 73 20  ....User Access 
0010   56 65 72 69 66 69 63 61 74 69 6f 6e 0d 0a 0d 0a  Verification....
0020   55 73 65 72 6e 61 6d 65 3a 20                    Username: 

:::::PHP:::::
Cisco->Client:
FF FB 01 FF FB 03 FF FD 18 FF FD 1F 0D 0A
