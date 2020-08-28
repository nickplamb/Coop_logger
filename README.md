# Coop_logger
## Temperature, humidity, and door lock status of my chicken coop

Main Parts list
'
Arduino Pro Mini 3.3v
2 DHT22
SparkFun Nano Power Timer - TPL5110
SparkFun Sunny Buddy - MPPT Solar Charger
SparkFun Lithium Ion Battery - 2Ah 3.7V
SparkFun Solar Panel - 6W
Hall-Effect Sensor - AH1815 (Non-Latching)
Schottky Diode to isolate power when programming
'
  
The Arduino script
'
1. connects to WIFI
2. Checks hall-effect sensor
3. Gets datd from both BHT22 sensors
4. Checks CHRG and FAULT pins on Sunny Buddy
5. Consolidates data
6. POST to home server through PHP script.
7. Sends sleep signal to Timer to cut power.
'

PHP insert Script
'
1. Retrieves and sanatizes data from POST request
2. Checks current time
3. Sends email with "locked" or "not locked" info if between certain time.
4. Sends data to Thingspeak channel
5. Writes data to mysql database
' 

PHP Display Script
'
1. Writes Table Headers
2. Pulls data from database
3. Writes data to HTML table.
'
