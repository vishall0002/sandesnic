GIMS Gateway Client Service V 1.0 (beta)
========================================

Quick Setup 
-----------
1. Update client id, client secret and HMAC key of your app acount in cmd.json
2. Run the service (See GIMS API Specification 2.0 for more details)
3. To send a message open a browser and submit the following request to the service 
   http://localhost:8021/send?receiverid={email or mobile}&msg={message}
4. A success response will be received if the message was dispatched successfully.

