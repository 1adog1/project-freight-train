import inspect
import os
import configparser
import json
import time
import traceback

from datetime import datetime, timezone
from pathlib import Path

import mysql.connector as DatabaseConnector

#If you've moved your config.ini file, set this variable to the path of the folder containing it (no trailing slash).
CONFIG_PATH_OVERRIDE = None

def dataFile(extraFolder):

    filename = inspect.getframeinfo(inspect.currentframe()).filename
    path = os.path.join(os.path.dirname(os.path.abspath(filename)), "../..")

    dataLocation = str(path) + extraFolder

    return(dataLocation)

configPath = (CONFIG_PATH_OVERRIDE) if (CONFIG_PATH_OVERRIDE is not None) else (dataFile("/config"))

if Path(configPath + "/config.ini").is_file():

    config = configparser.ConfigParser()
    config.read(dataFile("/config") + "/config.ini")

    databaseInfo = config["Database"]

else:

    try:

        databaseInfo = {}
        databaseInfo["DatabaseServer"] = os.environ["ENV_FREIGHT_DATABASE_SERVER"]
        databaseInfo["DatabasePort"] = os.environ["ENV_FREIGHT_DATABASE_PORT"]
        databaseInfo["DatabaseUsername"] = os.environ["ENV_FREIGHT_DATABASE_USERNAME"]
        databaseInfo["DatabasePassword"] = os.environ["ENV_FREIGHT_DATABASE_PASSWORD"]
        databaseInfo["DatabaseName"] = os.environ["ENV_FREIGHT_DATABASE_NAME"]

    except:

        raise Warning("No Configuration File or Required Environment Variables Found!")
    
databaseInfo["DatabaseServer"] = databaseInfo["DatabaseServer"].replace("'", "").replace('"', "")

def getTimeMark():

        currentTime = datetime.now(timezone.utc)
        return currentTime.strftime("%d %B, %Y - %H:%M:%S EVE")

def makeLogEntry(passedDatabase, logType, logStatement):

    loggingCursor = passedDatabase.cursor(buffered=True)

    logInsert = "INSERT INTO logs (timestamp, type, actor, details) VALUES (%s, %s, %s, %s)"
    loggingCursor.execute(logInsert, (int(time.time()), logType, "[System Importer]", logStatement))
    passedDatabase.commit()

    loggingCursor.close()

def determineClass(data):
    if "region_id" in data and data["region_id"] == 10000070:
        return "Pochven"
    elif "stargates" not in data or len(data["stargates"]) == 0:
        return "Wormhole"
    elif data["security_status"] < 0:
        return "Nullsec"
    elif data["security_status"] < 0.5:
        return "Lowsec"
    else:
        return "Highsec"

def determineToAdd(data):
    return (
        "security_status" in data
        and "position" in data
        and (
            ("stargates" in data and len(data["stargates"]) != 0) #K-Space
            or data["name"].startswith("J") #J-Space
            or data["system_id"] == 31000005 #Thera
        )
        and "region_id" in data
        and data["region_id"] not in [10000019, 10000017, 10000004] #Jove Regions
    )

print("[{Time}] Starting Update...\n".format(Time=getTimeMark()))

sq1Database = DatabaseConnector.connect(
    user=databaseInfo["DatabaseUsername"],
    password=databaseInfo["DatabasePassword"],
    host=databaseInfo["DatabaseServer"],
    port=int(databaseInfo["DatabasePort"]),
    database=databaseInfo["DatabaseName"]
)

try:

    with open(dataFile("/scripts/Python/Static") + "/geographicInformationV3.json") as systemsFile:
        systemsData = json.load(systemsFile)

    systemsTuple = [
        (id, data["name"], data["region_id"], data["region"], determineClass(data), data["security_status"], data["position"]["x"], data["position"]["y"], data["position"]["z"]) 
        for id, data in systemsData.items() 
        if determineToAdd(data)
    ]

    deletionCursor = sq1Database.cursor(buffered=True)
    checkCursor = sq1Database.cursor(buffered=True)
    updateCursor = sq1Database.cursor(buffered=True)

    print("[{Time}] Deleting Systems...".format(Time=getTimeMark()))
    deletionCursor.execute("DELETE FROM evesystems")

    print("[{Time}] Inserting Systems...".format(Time=getTimeMark()))
    systemsUpdate = "INSERT INTO evesystems (id, name, regionid, regionname, class, security, x, y, z) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)"
    updateCursor.executemany(systemsUpdate, systemsTuple)

    optionsCheck = "SELECT iteration FROM options"
    checkCursor.execute(optionsCheck)

    if checkCursor.rowcount == 0:

        print("[{Time}] Inserting Default Options...".format(Time=getTimeMark()))
        optionsUpdate = """INSERT INTO options (
            contractcorporation, 
            onlyapprovedroutes, 
            allowhighsectohighsec, 
            allowlowsec, 
            allownullsec, 
            allowwormholes, 
            allowpochven, 
            allowrush, 
            contractexpiration,
            contracttimetocomplete,
            rushcontractexpiration,
            rushcontracttimetocomplete,
            rushmultiplier, 
            nonstandardmultiplier, 
            maxvolume, 
            maxcollateral, 
            blockaderunnercutoff, 
            maxthresholdprice, 
            highsectohighsecmaxvolume, 
            gateprice, 
            maxwormholevolume, 
            wormholeprice, 
            maxpochvenvolume, 
            pochvenprice, 
            collateralpremium,
            highcollateralcutoff,
            highcollateralpenalty,
            highcollateralblockaderunnerpenalty
        ) VALUES (
            'CCP Alliance', 0, 0, 1, 1, 1, 1, 1, 7, 3, 3, 1, 1.5, 2, 320000, 10000000000, 13000, 1000, 1000000, 10, 40000, 1500, 40000, 2000, 0.25, 3000000000, 50000000, 10000000)"""
        updateCursor.execute(optionsUpdate)
    else:
        print("[{Time}] Default Options were not added due to Options already being present.".format(Time=getTimeMark()))

    print("[{Time}] Committing Transaction...\n".format(Time=getTimeMark()))
    sq1Database.commit()

    deletionCursor.close()
    checkCursor.close()
    updateCursor.close()

except:

    traceback.print_exc()

    error = traceback.format_exc()

    makeLogEntry(sq1Database, "Unknown System Importer Error", error)

sq1Database.close()

print("\n[{Time}] Concluded!".format(Time=getTimeMark()))