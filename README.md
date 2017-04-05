# Storage API Merged Export

[![Build Status](https://travis-ci.org/keboola/sapi-merged-export.svg?branch=master)](https://travis-ci.org/keboola/sapi-merged-export)


This component is used for table download/export in Storage UI. 
It allows download exported files as one gzipped CSV. CSV exports are otherwise splitted into multiple files.

## Usage example
Component expects tables list on input. Generated CSV is stored to Storage Files and can be later found by `runId`.

```
{
 "configData": {
   "storage": {
     "input": {
       "tables": [
            {
                "source": "out.c-snflk.gzip-debug-2",
                "destination": "out.c-snflk.gzip-debug-2.csv"
            } 
       ]
     }
   }
 }
}
```

## Development


`docker-compose run --rm dev`