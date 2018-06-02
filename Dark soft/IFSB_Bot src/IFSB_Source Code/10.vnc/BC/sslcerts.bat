rem Create clean environment

md newcerts
cd newcerts


rem Create CA certificate

openssl genrsa 2048 > ca-key.pem
openssl req -new -x509 -nodes -days 3600 -key ca-key.pem -out ca-cert.pem


rem Create server certificate, remove passphrase, and sign it
rem server-cert.pem = public key, server-key.pem = private key

openssl req -newkey rsa:2048 -days 3600 -nodes -keyout server-key.pem -out server-req.pem
openssl rsa -in server-key.pem -out server-key.pem
openssl x509 -req -in server-req.pem -days 3600 -CA ca-cert.pem -CAkey ca-key.pem -set_serial 01 -out server-cert.pem


rem Create client certificate, remove passphrase, and sign it
rem client-cert.pem = public key, client-key.pem = private key

openssl req -newkey rsa:2048 -days 3600 -nodes -keyout client-key.pem -out client-req.pem
openssl rsa -in client-key.pem -out client-key.pem
openssl x509 -req -in client-req.pem -days 3600 -CA ca-cert.pem -CAkey ca-key.pem -set_serial 01 -out client-cert.pem


rem After generating the certificates, verify them:

openssl verify -CAfile ca-cert.pem server-cert.pem client-cert.pem
