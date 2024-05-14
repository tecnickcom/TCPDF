openssl ocsp -text -url http://localhost/ocsp/ -issuer "PDF Signing CA.crt" -CApath "." -nonce -signer "longChain.pem" -cert "longChain.pem" 
pause
ocspTest.bat