---
title: VPN info
---
### To access the IISc network from outside :

#### For Windows

1. Download (and install) the [Cisco Anyconnect VPN client](https://digits.iisc.ac.in/wp-content/uploads/2019/05/How_to_download_VPN.pdf).

2. Follow the instructions for [IISc Cisco VPN](https://digits.iisc.ac.in/iisc-vpn/).

#### For Linux (Ubuntu)

1. Please install openconnect vpn client.
```bash
apt-get install openconnect  network-manager-openconnect
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


#### The IISc network IP addresses/aliases :

* DNS servers : 10.16.25.15, 10.16.25.13

* Netmask: 255.255.255.0

* Math: math.iisc.ac.in

* Sparrow: sparrow.math.iisc.ac.in

* Drongo: drongo.math.iisc.ac.in
