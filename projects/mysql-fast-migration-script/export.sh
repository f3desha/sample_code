echo "Making dump..."
mysqldump -h *HOST* --single-transaction -u*LOGIN* -p *DBNAME* > *PATH_TO_DUMP*/DUMP.sql

echo "Archiving the dump..."
tar -czvf dump.tar.gz DUMP.sql

echo "Removing the dump..."
rm DUMP.sql
