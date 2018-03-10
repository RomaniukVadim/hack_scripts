#!/bin/bash

nmap_result=$(sudo nmap -sP 192.168.1.0/24) #checks who's responding to ping 192.168.1.0-256
own_ip=$(ifconfig eth0 | grep inet | awk '{print $2}' | cut -d':' -f2) #gets your own ip

temp_mac=$(echo "$nmap_result" | grep "MAC Address:" | awk '{print $3;}') #gets the mac addresses list
temp_ip=$(echo "$nmap_result" | grep "192.168." | awk '{print $5;}' | grep -v "$own_ip") #gets the ip list
temp_vendor=$(echo "$nmap_result" | grep "MAC Address:" | awk '{print $4;}') #gets the vendor list

readarray -t mac <<<"$temp_mac" #converts it to array named mac
readarray -t ip <<<"$temp_ip" #converts it to array named ip
readarray -t vendor <<<"$temp_vendor"

len=${#mac[@]} # length of mac addresses array

echo "List of connected devices (vendor: ip - mac):"
echo "Your own ip address is $own_ip"
for (( i=0; i<${len}; i++ ));
do
	echo ${vendor[i]}": "${ip[i]}" - "${mac[i]}
done
