/**
 * Based on these examples:
 * https://create.arduino.cc/projecthub/jeffpar0721/add-wifi-to-arduino-uno-663b9e
 * http://yaab-arduino.blogspot.nl/p/wifiesp.html
 * 
 * https://github.com/bportaluri/WiFiEsp and extract the Zip-file to Documents\Arduino\libraries\WiFiEsp
 */

#include <DHT.h>
#include <DHT_U.h>
#include "WiFiEsp.h"

// Emulate EspSerial on pins 2/3 if not present
#ifndef HAVE_HWEspSerial
#include "SoftwareSerial.h"
SoftwareSerial EspSerial(2, 3); // RX, TX
#endif

//****************************Constants********************
#define MPPT_CHG_PIN 4
#define MPPT_FAULT_PIN 5
#define HALLPIN 6
#define DHTPIN1 7         //short run, inside
#define DHTPIN2 8         //long run, outside

#define DONEPIN 10
#define DHTTYPE DHT22
#define SERIALBAUD 9600

//****************************Debugging********************
//#define DEBUG           //uncomment for serial messages.
#ifdef DEBUG
 #define DEBUG_PRINT(x)     Serial.print (x)
 #define DEBUG_PRINTLN(x)   Serial.println (x)
#else
 #define DEBUG_PRINT(x)
 #define DEBUG_PRINTLN(x)
#endif


DHT dht1(DHTPIN1, DHTTYPE);   //inside
DHT dht2(DHTPIN2, DHTTYPE);

//************************variables******************************
//origionally FLOAT
int hum1;   //Sensor at PIN 7
int tempF1;
//int tempC1;
//int heatIndex1; 
int hum2;   //sensor at PIN 8
int tempF2;
//int tempC2;
//int heatIndex2;

int batteryStatus; 
//  1 = Not Charging - Standby or shutdown mode
//  2 = Bad Battery Fault (Precondition Timeout/EOC failure)
//  3 = Normal Charging at C/10 or Greater
//  4 = NTC FAULT (Pause)
int lockClosed; //

//*****************************NETWORK INFO*****************************
char ssid[] = "YOUR NETWORK ID HERE";           // your network SSID (name)
char pass[] = "YOUR NETWORK PASSWORD HERE";        // your network password (change it)
int status = WL_IDLE_STATUS;     // the Wifi radio's status

WiFiEspClient client;

char server[] = "192.168.0.100";
char sensor1Data[40];
char sensor2Data[40];
char lockData[15];
char batteryData[12];
char yourData[150];
char api_key[] = "api_key=YOUR API KEY HERE";


void setup() {
  
  //**********************************Set pins***********************
  pinMode(DONEPIN, OUTPUT);  //Power Timer Done Pin
  pinMode(MPPT_CHG_PIN, INPUT_PULLUP);
  pinMode(MPPT_FAULT_PIN, INPUT_PULLUP);
  pinMode(HALLPIN, INPUT);

  // initialize serial for debugging
  Serial.begin(SERIALBAUD);

  connectWifi();
  
  // initialize DHT library
  dht1.begin();
  dht2.begin();
  
  
  //Display Wifi and network information
  DEBUG_PRINTLN();
  //printCurrentNet();
  //printWifiData();
  
  
}

void loop()
{
  checkDoorLock();
  //printLockStatus();
  getSensorData();    //Reads two DHT sensors, prints data to Serial 
  getBatteryData();
  postData();         //sends data in header of HTTP POST request
  sleep();
  delay(8000);     //10 second delay for debugging without sleep.
}



void connectWifi(){
  // initialize serial for ESP module
  EspSerial.begin(SERIALBAUD);
  // initialize ESP module
  WiFi.init(&EspSerial);

  // check for the presence of the shield
  if (WiFi.status() == WL_NO_SHIELD) {
    DEBUG_PRINTLN("WiFi shield not present");
    // don't continue
    while (true);
  }

  // attempt to connect to WiFi network
  while ( status != WL_CONNECTED) {
    DEBUG_PRINT("Attempting to connect to WPA SSID: ");
    DEBUG_PRINTLN(ssid);
    // Connect to WPA/WPA2 network
    status = WiFi.begin(ssid, pass);
  }
  
  // you're connected now, so print out the data
  DEBUG_PRINTLN("You're connected to the network");
}

void getSensorData (){  //Read sensor data from DHT22, convert to Deg F and print to serial.
  //read data and store it to variables hum and temp
  hum1 = round(dht1.readHumidity());
  tempF1 = round(dht1.readTemperature(true));  //Passing true to readTemperature converts to Deg F   https://forum.arduino.cc/index.php?topic=171299.0
  //tempC1 = round(dht1.readTemperature());    //read in celsius
  //heatIndex1 = round(dht1.computeHeatIndex(tempF1, hum1, true)); //pass true if temp is in Deg F
  
  hum2 = round(dht2.readHumidity());
  tempF2 = round(dht2.readTemperature(true));  //Passing true to readTemperature converts to Deg F   https://forum.arduino.cc/index.php?topic=171299.0
  //tempC2 = round(dht2.readTemperature());    //read in celsius
  //heatIndex2 = round(dht2.computeHeatIndex(tempF2, hum2, true)); //pass true if temp is in Deg F
  
  //Bad Float data from DHT cast to int = 0. Humidity should never be 0, so recheck if so. 
  if (hum1 == 0) {
    for (int i=0; i < 4; i++){
      DEBUG_PRINTLN("sensor 1 trying again.");
      delay(2000);
      hum1 = round(dht1.readHumidity());
      tempF1 = round(dht1.readTemperature(true));
      if (hum1 != 0) {
        break;
      }
    }
  }
  if (hum2 == 0) {
    for (int i=0; i < 4; i++){
      DEBUG_PRINTLN("sensor 2 trying again.");
      delay(2000);
      hum2 = round(dht2.readHumidity());
      tempF2 = round(dht2.readTemperature(true));
      if (hum2 != 0) {
        break;
      }
    }
  }
  
  //print_data_srl(hum1, tempF1);     //For troubleshooting
  //print_data_srl(hum2, tempF2);
  
  sprintf(sensor1Data, "humidity_1=%d&tempF_1=%d", hum1, tempF1);
  sprintf(sensor2Data, "humidity_2=%d&tempF_2=%d", hum2, tempF2);

} 

void getBatteryData() {
  if (digitalRead(MPPT_CHG_PIN) == HIGH) {
    if (digitalRead(MPPT_FAULT_PIN) == HIGH) {
      batteryStatus = 1;
    } else if (digitalRead(MPPT_FAULT_PIN) == LOW) {
      batteryStatus = 2;
    }
  } else if (digitalRead(MPPT_CHG_PIN) == LOW) {
    if (digitalRead(MPPT_FAULT_PIN) == HIGH) {
      batteryStatus = 3;
    } else if (digitalRead(MPPT_FAULT_PIN) == LOW) {
      batteryStatus = 4;
    }
  } else {
    batteryStatus = 0;
  }
  
  sprintf(batteryData, "battery=%d", batteryStatus); 
  
  DEBUG_PRINTLN(batteryStatus);
  
  //Status Pins State       OFF = HIGH!
  //CHRG | FAULT
  //-------------
  //OFF  | OFF  ~ Not Charging - Standby or shutdown mode                   1
  //OFF  | ON   ~ Bad Battery Fault (Precondition Timeout/EOC failure)      2
  //ON   | OFF  ~ Normal Charging at C/10 or Greater                        3
  //ON   | ON   ~ NTC FAULT (Pause)                                         4
}

void checkDoorLock() {
  
  lockClosed = digitalRead(HALLPIN);    //
  sprintf(lockData, "door_locked=%d", lockClosed);
}

void printLockStatus () {
  DEBUG_PRINTLN();
  DEBUG_PRINTLN();
  DEBUG_PRINT("The door is currently ");
  if (lockClosed == 1){
    DEBUG_PRINTLN("locked.");
  } else if (lockClosed == 0) {
    DEBUG_PRINTLN("unlocked.");
  } else {
    DEBUG_PRINTLN("Unknown.");
  }
  DEBUG_PRINTLN();
}

void print_data_srl (int hum, int temp){  //Print temp and humidity values to serial monitor
  DEBUG_PRINT("Humidity: ");                       //Using float inputs for troubleshooting
  DEBUG_PRINT(hum);
  DEBUG_PRINT(" %, Temp: ");
  DEBUG_PRINT(temp);
  DEBUG_PRINTLN(" Fahrenheit");
}

void postData() { // This method makes a HTTP connection to the server and POSTs data
  //DEBUG_PRINTLN(sensor1Data);
  //DEBUG_PRINTLN(sensor2Data);
  //DEBUG_PRINTLN(lockData);
  //DEBUG_PRINTLN();
  sprintf(yourData, "%s&%s&%s&%s&%s", api_key, sensor1Data, sensor2Data, lockData, batteryData);
  char dataLength[3];
  sprintf(dataLength, "%d", strlen(yourData));
  DEBUG_PRINTLN(yourData);
  DEBUG_PRINTLN(dataLength);

  //https://stackoverflow.com/questions/41354652/post-request-on-arduino-with-esp8266-using-wifiesp-library
  String PostHeader = "POST /test/insert_mysql.php HTTP/1.1\r\n";
  PostHeader += "Host: 192.168.0.100\r\n";
  PostHeader += "User-Agent: Arduino/1.0\r\n";
  PostHeader += "Connection: keep-alive\r\n"; //try keep-alive\\r\n   close\r\n
  PostHeader += "Content-Type: application/x-www-form-urlencoded;\r\n";
  PostHeader += "Content-Length: ";
  PostHeader += dataLength;
  PostHeader += "\r\n\r\n";
  PostHeader += yourData;  

  // If there's a successful connection, send the HTTP POST request
  if (client.connect(server, 80)) {
    DEBUG_PRINTLN("connecting...");
    DEBUG_PRINTLN(PostHeader);
    client.println(PostHeader);
    client.stop();

  } 
  else {
    // If you couldn't make a connection:
    if (client.available()) {
      char c = client.read();
      DEBUG_PRINT("Client.read is: ");
      DEBUG_PRINT(c);
      }
    DEBUG_PRINTLN("Connection failed");
    DEBUG_PRINTLN("Disconnecting.");
    client.stop();
  }
}

void printWifiData() {
  // print your WiFi shield's IP address
  IPAddress ip = WiFi.localIP();
  DEBUG_PRINT("IP Address: ");
  DEBUG_PRINTLN(ip);

  // print your MAC address
  byte mac[6];
  WiFi.macAddress(mac);
  char buf[20];
  sprintf(buf, "%02X:%02X:%02X:%02X:%02X:%02X", mac[5], mac[4], mac[3], mac[2], mac[1], mac[0]);
  DEBUG_PRINT("MAC address: ");
  DEBUG_PRINTLN(buf);
}

void printCurrentNet() {
  // print the SSID of the network you're attached to
  DEBUG_PRINT("SSID: ");
  DEBUG_PRINTLN(WiFi.SSID());

  // print the MAC address of the router you're attached to
  byte bssid[6];
  WiFi.BSSID(bssid);
  char buf[20];
  sprintf(buf, "%02X:%02X:%02X:%02X:%02X:%02X", bssid[5], bssid[4], bssid[3], bssid[2], bssid[1], bssid[0]);
  DEBUG_PRINT("BSSID: ");
  DEBUG_PRINTLN(buf);

  // print the received signal strength
  long rssi = WiFi.RSSI();
  DEBUG_PRINT("Signal strength (RSSI): ");
  DEBUG_PRINTLN(rssi);
}


void sleep (){    //Sends pulse to nano power timer
  
  digitalWrite(DONEPIN,LOW);
  digitalWrite(DONEPIN,HIGH);
  DEBUG_PRINTLN("Restarting loop in 5 seconds");
  delay(5000);

}
