#include <OneWire.h>
#include <DallasTemperature.h>

#define TdsSensorPin A0
#define PhSensorPin A1
#define ONE_WIRE_BUS 4   

OneWire oneWire(ONE_WIRE_BUS);
DallasTemperature sensors(&oneWire);
float temperature = 25.0;

// --- SETUP SENSOR PH ---
float calibration_value = 23.58;
unsigned long int avgPhValue; 

// --- SETUP SENSOR TDS ---
#define VREF 5.0      
#define SCOUNT  30    
int analogBuffer[SCOUNT];    
int analogBufferTemp[SCOUNT];
int analogBufferIndex = 0, copyIndex = 0;
float averageVoltage = 0, tdsValue = 0;

void setup() {
  Serial.begin(9600);  
  Serial1.begin(9600); 
  
  pinMode(TdsSensorPin, INPUT);
  sensors.begin();     
  
  Serial.println("--- SISTEM MONITORING AIR KOLAM (MEGA) ---");
  Serial.println("Menunggu sensor stabil...");
  delay(2000);
}

void loop() {
  static unsigned long analogSampleTimepoint = millis();
  if(millis()-analogSampleTimepoint > 40U) {
    analogSampleTimepoint = millis();
    analogBuffer[analogBufferIndex] = analogRead(TdsSensorPin);
    analogBufferIndex++;
    if(analogBufferIndex == SCOUNT) 
      analogBufferIndex = 0;
  }
  
  static unsigned long printTimepoint = millis();
  if(millis()-printTimepoint > 1000U) {
    printTimepoint = millis();

    sensors.requestTemperatures(); 
    float tempReading = sensors.getTempCByIndex(0);
    if(tempReading != -127.00 && tempReading != 85.00) {
      temperature = tempReading; 
    }

    for(copyIndex=0;copyIndex<SCOUNT;copyIndex++)
      analogBufferTemp[copyIndex]= analogBuffer[copyIndex];
    averageVoltage = getMedianNum(analogBufferTemp,SCOUNT) * (float)VREF / 1024.0; 
    float compensationCoefficient=1.0+0.02*(temperature-25.0); 
    float compensationVolatge=averageVoltage/compensationCoefficient; 
    tdsValue=(133.42*compensationVolatge*compensationVolatge*compensationVolatge - 255.86*compensationVolatge*compensationVolatge + 857.39*compensationVolatge)*0.5;

    float phOffset = 2.0;
    avgPhValue = 0;
    for(int i = 0; i < 10; i++) {
      avgPhValue += analogRead(PhSensorPin);
      delay(10);
    }
    float phVoltage = (float)avgPhValue / 10 * (5.0 / 1024.0);
    float phValue = -5.70 * phVoltage + calibration_value;

    phValue - phValue - phOffset;

    String statusAir = "AMAN"; 

    if (phValue < 6.5) {
      statusAir = "ASAM (BAHAYA)";
    } 
    if (phValue > 8.0) {
      statusAir = "BASA (BAHAYA)";
    }
    if (tdsValue > 500) { // Angka 1000 bisa diubah sesuai standar kolam
      statusAir = "KERUH / KOTOR";
    }
    if (temperature > 38.0) {
      statusAir = "TERLALU PANAS";
    }

    Serial.print("pH: "); Serial.print(phValue, 2);
    Serial.print(" | TDS: "); Serial.print(tdsValue, 0);
    Serial.print(" | Suhu: "); Serial.print(temperature, 1);
    Serial.print(" | Status: "); Serial.println(statusAir);

    Serial1.print(phValue, 2);
    Serial1.print(",");
    Serial1.print(tdsValue, 0);
    Serial1.print(",");
    Serial1.print(temperature, 1);
    Serial1.print(",");
    Serial1.println(statusAir);
  }
}

int getMedianNum(int bArray[], int iFilterLen) {
  int bTab[iFilterLen];
  for (byte i = 0; i < iFilterLen; i++)
    bTab[i] = bArray[i];
  int i, j, bTemp;
  for (j = 0; j < iFilterLen - 1; j++) {
    for (i = 0; i < iFilterLen - j - 1; i++) {
      if (bTab[i] > bTab[i + 1]) {
        bTemp = bTab[i];
        bTab[i] = bTab[i + 1];
        bTab[i + 1] = bTemp;
      }
    }
  }
  if ((iFilterLen & 1) > 0)
    bTemp = bTab[(iFilterLen - 1) / 2];
  else
    bTemp = (bTab[iFilterLen / 2] + bTab[iFilterLen / 2 - 1]) / 2;
  return bTemp;
}