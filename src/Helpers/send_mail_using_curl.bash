#!/bin/bash
cd /home/nic/vipin

rtmp_url="smtp://relay.nic.in:25"
rtmp_from="Sandes - Support <support-gims@nic.in>"
rtmp_to="SIO List <sio-list@lsmgr.nic.in>, HOG List <hog-list@lsmgr.nic.in>"
rtmp_cc="Ms. Sapna Kapoor <sapna.kapoor@nic.in>, Manoj PA <manoj.pa@nic.in>"
rtmp_bcc="Arun K Varghese <arun.kv@nic.in>, Sunish <sunish@nic.in>, Abby Murali <abby.murali@nic.in>, Syamkrishna B G <syam.krishna@nic.in>, Vipin Bose <vipin.bose@gov.in>"
rtmp_credentials="support-gims:Gims2019@support"

file_upload="data.txt"
echo "From: $rtmp_from
To: $rtmp_to
Subject: Sandes - Instant Review Statistics (AUTO)
Cc: $rtmp_cc
Bcc: $rtmp_bcc
MIME-Version: 1.0
Content-Type: multipart/mixed; boundary=\"MULTIPART-MIXED-BOUNDARY\"

--MULTIPART-MIXED-BOUNDARY
Content-Type: multipart/alternative; boundary=\"MULTIPART-ALTERNATIVE-BOUNDARY\"

--MULTIPART-ALTERNATIVE-BOUNDARY
Content-Type: text/plain; charset=utf-8
Content-Disposition: inline
Dear Sir,

This is an automated e-mail generated for instant review of  GIMS. 

Please find the attached screenshots, that  shows Daily Total Messages, Daily Registrations.

This mail has been sent with approval of HOG

Regards
Team GIMS
--MULTIPART-ALTERNATIVE-BOUNDARY--
--MULTIPART-MIXED-BOUNDARY
Content-Transfer-Encoding: base64
Content-Type: image/png; name="screenshot1.png"
Content-Disposition: attachment; filename="screenshot1.png"
Content-Id: <screenshot1.png>
" > "$file_upload"
cat 1.png | base64 >> "$file_upload"

echo "
--MULTIPART-MIXED-BOUNDARY--" >> "$file_upload"


echo "sending ...."
curl -s "$rtmp_url" \
     --mail-from "$rtmp_from" \
     --mail-rcpt "sio-list@lsmgr.nic.in" \
     --mail-rcpt "hog-list@lsmgr.nic.in" \
     --mail-rcpt "sapna.kapoor@nic.in" \
     --mail-rcpt "manoj.pa@nic.in" \
     --mail-rcpt "arun.kv@nic.in" \
     --mail-rcpt "sunish@nic.in" \
     --mail-rcpt "syam.krishna@nic.in" \
     --mail-rcpt "abby.murali@nic.in" \
     --mail-rcpt "vipin.bose@gov.in" \
     -T "$file_upload" 
res=$?
if test "$res" != "0"; then
   echo "sending failed with: $res"
else
    echo "OK"
fi