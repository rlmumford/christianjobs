#!/usr/bin/env bash
set -e

# Ensure "dev" core config is always up to date even after the
# core has been created. This does not execute the first time,
# when solr-precreate has not yet run.
CORENAME=dev
if [ -d /opt/solr/server/solr/mycores/${CORENAME}/conf ]; then
    cp -r /solr-conf/conf/* /opt/solr/server/solr/mycores/${CORENAME}/conf
fi
