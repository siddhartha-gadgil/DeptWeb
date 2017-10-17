---
---
### To access the IISc network from outside :

#### For Windows

1. Download (and install) the [Cisco Anyconnect VPN client](https://www1.aps.anl.gov/information-technology/remote-access/vpn-downloads).

2. Follow the instructions for [IISc Cisco VPN](http://nitss.iisc.ac.in/?p=256).

#### For Linux (Ubuntu)

1. Please install openconnect vpn client.
```bash
sudo apt-get install openconnect
```

2. execute it from command terminal (eg. gnome-terminal)
```bash
sudo openconnect vpn.iisc.ernet.in
```

3. Accept the self-signed certificate

4. Provide credentials (username/password)

5. Once the vpn tunnel is opened, keep it running in the background

6. ctrl-C to break the vpn connection

### To access the mathematics network from outside  :

#### For Windows

0. Download and install an FTP client such as [winscp](https://winscp.net/eng/index.php)

1. Login to the IISc network using the Anyconnect VPN client (as described above).

2. Choose the "SFTP" protocol. The "host name" is 10.134.1.11 (the IP address of the mathematics server). The port number is 22. Click on the login button.

3. Enter your username (for instance, if your IISc email is einstein@iisc.ac.in, then  your username is einstein) and your password. Now you can start transferring files.

#### For Linux

* Use the ssh command line application.


#### The IISc network IP addresses :

* DNS servers : 10.16.25.15, 10.16.25.13

* Gateway: 10.134.13.1

* Netmask: 255.255.255.0

* Math: 10.134.1.11

* Sparrow: 10.134.13.102
