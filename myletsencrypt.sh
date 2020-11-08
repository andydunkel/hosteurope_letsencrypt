#!/bin/sh

#change working directory that cronjob runs
#cd letsencrypt2

#generate all certificates
/usr/bin/env php7.4 main.php your@email.de account_key.pem cert_private_key.pem \
\
-w /is/htdocs/wp1XXXXXXXX_YYYYYYYYYY/www/yourFolder \
-d www.your_domain.de \
-d your_domain.de \
\
-w /is/htdocs/wp1XXXXXXXX_YYYYYYYYYY/www/yourFolder2 \
-d www.your_domain2.de \
-d your_domain2.de \
\
--csr csr.pem \
--cert cert.pem \
--chain chain.pem \
--fullchain fullchain.pem   #do not chain this one

#upload them
#/usr/bin/env php7.2 certificate_upload.php
# not needed anymore as not working; will be send to the email
