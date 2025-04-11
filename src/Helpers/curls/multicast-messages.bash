 curl -X POST \
  https://apigateway.gimkerala.nic.in/v2/api/message/multicast \
  -H 'Content-Type: application/json' \
  -H 'clientid: 80fbd095-633f-4e12-aa61-017529ea467d' \
  -H 'clientsecret: c66e45f5303c94abe70cc116a0c3b771' \
  -H 'hmac: Ba+lLdvlffMSM+ihkK7mqc8fO4LVYGnK00FJaxWQJf4=' \
  -d '{
"message":"GIM meeting scheduled",
"type":"chat",
"title":"Meeting Reminder",
"category": "info",
"created_on": 1549953711,
"expire_on": 1550126511,
"receivers":[
"bose.vipin@nic.in"]
}'



[2021-01-08T12:36:37.302262+05:30] broadcast.INFO: MULTICAST SENDER ID - GIMS Portal [] []
[2021-01-08T12:36:37.304524+05:30] broadcast.INFO: MULTICAST CLIENTID-80fbd095-633f-4e12-aa61-017529ea467d [] []
[2021-01-08T12:36:37.304565+05:30] broadcast.INFO: MULTICAST CLIENTSECRET-c66e45f5303c94abe70cc116a0c3b771 [] []
[2021-01-08T12:36:37.304584+05:30] broadcast.INFO: MULTICAST GIMS-GW-HMACKEY-bd22dba50c589acc7e138d373069eb81d43f91839e60e309ec7db462a57b46d3 [] []
[2021-01-08T12:36:37.305016+05:30] broadcast.INFO: MULTICAST GW URLhttps://apigateway.gimkerala.nic.in/v2/api/message/multicast [] []
[2021-01-08T12:36:37.305071+05:30] broadcast.INFO: MULTICAST PARAMS {"message":"test","type":"chat","title":"GIMSIMTest","category":"info","created_on":1610089597,"expire_on":1610953597,"receivers":["beena.g@nic.in"]} [] []
[2021-01-08T12:36:37.305097+05:30] broadcast.INFO: MULTICAST HMAC-KEY Ba+lLdvlffMSM+ihkK7mqc8fO4LVYGnK00FJaxWQJf4= [] []
[2021-01-08T12:36:37.377887+05:30] broadcast.INFO: MULTICAST RETURN MESSAGE {"status":"danger","message":"General Exception->Client error: `POST https:\/\/apigateway.gimkerala.nic.in\/v2\/api\/message\/multicast` resulted in a `400 Bad Request` response"} [] []