#!/bin/bash

# Load the Heartbleed flag into memory
export HEARTBLEED_FLAG="DIABLE{Heartbleed_Vulnerable_Server_2026}"

# Aggressively load the flag into memory
FLAG_DATA=""
for i in {1..100000}; do
  FLAG_DATA="$FLAG_DATA $HEARTBLEED_FLAG"
done &

# Keep the flag in memory in a loop for maximum visibility
for i in {1..10000}; do
  FLAG_DATA="$HEARTBLEED_FLAG"
done &

# Keep the flag in a persistent variable to ensure it's in process memory
FLAG_DATA="$HEARTBLEED_FLAG"

# Optional: Write to a temp file to ensure it's in memory
echo "$FLAG_DATA" > /tmp/heartbleed_flag.txt

# Write the flag to a file in /dev/shm (RAM disk)
echo "$HEARTBLEED_FLAG" > /dev/shm/heartbleed_flag.txt

# Keep the flag in memory persistently and actively
while true; do
  FLAG_DATA="$HEARTBLEED_FLAG $HEARTBLEED_FLAG $HEARTBLEED_FLAG $HEARTBLEED_FLAG $HEARTBLEED_FLAG $HEARTBLEED_FLAG $HEARTBLEED_FLAG $HEARTBLEED_FLAG $HEARTBLEED_FLAG $HEARTBLEED_FLAG"
  sleep 0.1
done &

# Start Apache in foreground
/usr/sbin/apache2ctl -DFOREGROUND