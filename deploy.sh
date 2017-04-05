#!/bin/bash

docker pull quay.io/keboola/aws-cli
eval $(docker run --rm -i -e AWS_ACCESS_KEY_ID=$AWS_ACCESS_KEY_ID -e AWS_SECRET_ACCESS_KEY=$AWS_SECRET_ACCESS_KEY quay.io/keboola/aws-cli ecr get-login --region us-east-1)
docker tag keboola/docker-demo-app:latest 147946154733.dkr.ecr.us-east-1.amazonaws.com/keboola/sapi-merged-export:$TRAVIS_TAG
docker tag keboola/docker-demo-app:latest 147946154733.dkr.ecr.us-east-1.amazonaws.com/keboola/sapi-merged-export:latest
docker push 147946154733.dkr.ecr.us-east-1.amazonaws.com/keboola/sapi-merged-export:$TRAVIS_TAG
docker push 147946154733.dkr.ecr.us-east-1.amazonaws.com/keboola/sapi-merged-export:latest
