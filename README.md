# Welcome to Rottenmeier!

Rottermeier is a PHP script that helps you to delete all these comments that you doesn't want in your videos. Give Rottenmeier a list of words and all comments that contain these words will be deleted.

Who's RottenMeier?
Fräulein Rottenmeier is Herr Sesemann's stern, unbending housekeeper. She is firmly in charge of running the household where Heidi has been invited to stay as companion to Herr Sesemann's invalid daughter, Clara.

Rottenmeier was created by BorjaGalisteo.
[Visit my channel ](http://www.youtube.com/borjagalisteo)

# Configuration
0. Install dependencies using `composer install`
 1. Create a New Project on [Google Console Developer](https://console.developers.google.com/)
 2. Create new credentials on your project.
 
     2.1. Create an API KEY and paste it on config.php  as `$developer_key = YOU_API_KEY`
     
    2.2 Create an Outh2Credentials (in application type you can use DesktopApplication) an download the .csv file. Move this file into the project and rename it to `secrets.json`
    
3. Use the ID of your channel and place it in config.php as `$channel_id = YOUR_CHANNNEL_ID`
4. Choose the words that will mark a comment to be deleted, and place it config.php as `$curse = [BAD_WORD,BAD_WORD2]` by default the value is mierda... It means shit in spanish.

# How to use it

If you want to see all comments that match, just use

·`php punish.php` 

·`php punish.php delete` will delete all comments that match (after confirmation)

·`php punish.php delete ban` will delete all comments that match and BAN the author to avoid future comments.(after confirmation)

# Disclaimer
Depends on you channel size, the default API quota is not enough, Google just give 10.000 Request every 24H, if you want to increase the number you should send a form via [Google Console Developer](https://console.developers.google.com/)
